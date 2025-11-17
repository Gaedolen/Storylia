<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\BookApiService;
use App\Service\BookCreationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Book;
use App\Entity\Author;



#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/add-book-api', name: 'add_book_api', methods: ['GET'])]
    public function addBookFromApi(Request $request, BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): JsonResponse
    {
        // Récupération des paramètres
        $title = $request->query->get('title');
        $author = $request->query->get('author');

        // Il ne se passe rien s'il n'y a pas de titre
        if(!$title) {
            return $this->json([
                'success' => false,
                'message' => 'Titre manquant'
            ]);
        }

        // Vérifier si le livre existe déjà
        $existingBook = $em->getRepository(Book::class)->findOneBy(['title' => $title]);

        if ($existingBook) {
        return $this->json([
            'success' => false,
            'message' => 'Le livre existe déjà en base.'
        ]);
    }


        // Appel du service BookApiService pour récupérer le livre dans l'API
        $bookData = $apiService->fetchBook($title, $author);

        // Si aucun livre trouvé = erreur JSON
        if(!$bookData) {
            return $this->json([
                'success' => false,
                'message' => 'Pas de nouveau livre dans l\'API.'
            ]);
        }

        // Création du livre en BDD à partir des données récupérées
        $book = $creationService->createOrUpdateBookFromApi($bookData);

        // On renvoie une réponse JSON
        return $this->json([
            'success' => true,
            'book' => [
                'title' => $book->getTitle(),
                'author' => $book->getAuthor()->getName(),
                'cover' => $book->getCover() ?: '/images/default_cover.jpg',
                'summary' => $book->getSummary(),
                'publicationDate' => $book->getPublicationDate()?->format('Y-m-d'),
                'edition' => $book->getPublishers(),
                'genre' => $book->getGenres()
            ]
        ]);
    }

    #[Route('/import-books', name: 'import_books', methods: ['GET'])]
    public function importBooks(BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): Response
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $batchSize = 50;
        $totalImported = 0;

        // --- Récupère tous les livres existants avec title + author name ---
        $existingBooks = $em->getRepository(Book::class)
                            ->createQueryBuilder('b')
                            ->select('b.title, IDENTITY(b.author) AS author_id')
                            ->getQuery()
                            ->getArrayResult();

        // --- Crée un tableau pour vérifier rapidement l’existence ---
        $existingMap = [];
        foreach ($existingBooks as $b) {
            $author = $em->getRepository(Author::class)->find($b['author_id']);
            $authorName = $author ? strtolower(trim($author->getName())) : '';
            $titleKey = strtolower(trim($b['title']));
            $key = $titleKey . '||' . $authorName; // uniquement titre + auteur
            $existingMap[$key] = true;
        }

        $subjects = $apiService->fetchAllSubjects();

        foreach ($subjects as $subject) {
            $startIndex = 0;

            while (true) {
                $booksData = $apiService->fetchBookList($startIndex, 40, [$subject]);
                if (empty($booksData)) break;

                foreach ($booksData as $bookData) {
                    $title = trim($bookData['title'] ?? '');
                    $authorName = strtolower(trim($bookData['author'] ?? 'Auteur inconnu'));
                    $key = strtolower($title) . '||' . $authorName;

                    // Si le livre existe déjà, on saute
                    if (isset($existingMap[$key])) continue;

                    $book = $creationService->createOrUpdateBookFromApi($bookData);
                    if ($book) {
                        $em->persist($book);
                        $existingMap[$key] = true; // marque comme existant
                        $totalImported++;
                    }

                    // Flush par batch
                    if ($totalImported % $batchSize === 0) {
                        $em->flush();
                        $em->clear();
                    }
                }

                $startIndex += 40;
            }
        }

        // Flush final
        $em->flush();
        $em->clear();

        $this->addFlash('success', "$totalImported livre(s) importé(s).");
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/update-books', name: 'update_books', methods: ['GET'])]
    public function updateBooks(BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): Response
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $batchSize = 50;
        $count = 0;

        $qb = $em->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');

        $iterableBooks = $qb->getQuery()->toIterable();

        foreach ($iterableBooks as $book) {
            $author = $book->getAuthor();
            if (!$author) continue;

            $bookData = $apiService->fetchBook($book->getTitle(), $author->getName());
            if (!$bookData) continue;

            $creationService->updateBookFields($book, $bookData);

            $em->persist($book);
            $count++;

            if ($count % $batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        $this->addFlash('success', "$count livre(s) mis à jour.");
        return $this->redirectToRoute('admin_dashboard');
    }
}
