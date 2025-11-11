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
        set_time_limit(0); // désactive la limite de temps pour l'import massif
        ini_set('memory_limit', '2048M'); // augmente la mémoire disponible

        $batchSize = 50;
        $totalImported = 0;

        // Récupère tous les ISBN existants en une seule requête pour éviter les doublons
        $existingIsbns = $em->getRepository(Book::class)
                            ->createQueryBuilder('b')
                            ->select('b.isbn')
                            ->getQuery()
                            ->getArrayResult();
        $existingIsbns = array_column($existingIsbns, 'isbn');

        // Liste complète des sujets pour diversifier les livres
        $subjects = $apiService->fetchAllSubjects();

        foreach ($subjects as $subject) {
            $startIndex = 0;

            while (true) {
                // Google Books API ne renvoie que max 40 résultats par requête
                $booksData = $apiService->fetchBookList($startIndex, 40, [$subject]);

                if (empty($booksData)) break;

                foreach ($booksData as $bookData) {
                    // Si ISBN vide ou déjà présent, on saute
                    if (empty($bookData['isbn']) || in_array($bookData['isbn'], $existingIsbns)) {
                        continue;
                    }

                    $book = $creationService->createOrUpdateBookFromApi($bookData);
                    if ($book) {
                        $em->persist($book);
                        $existingIsbns[] = $bookData['isbn']; // évite les doublons dans ce batch
                        $totalImported++;
                    }

                    // Flush par batch pour éviter les problèmes mémoire
                    if ($totalImported % $batchSize === 0) {
                        $em->flush();
                        $em->clear();
                    }
                }

                // Flush final pour ce lot
                $em->flush();
                $em->clear();

                $startIndex += 40; // passe au batch suivant pour ce sujet
            }
        }

        $this->addFlash('success', "$totalImported livre(s) importé(s).");
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/update-books', name: 'update_books', methods: ['GET'])]
    public function updateBooks(BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): Response
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $books = $em->getRepository(Book::class)->findAll();
        $batchSize = 50;
        $count = 0;

        foreach ($books as $book) {
            // Récupère les données depuis Google Books API pour mise à jour
            $bookData = $apiService->fetchBook($book->getTitle(), $book->getAuthor()?->getName());
            if (!$bookData) continue;

            // Met à jour le livre existant
            $creationService->updateBookFromApi($book, $bookData);
            $em->persist($book);
            $count++;

            // Flush par batch pour éviter les problèmes mémoire
            if ($count % $batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        // Flush final
        $em->flush();
        $em->clear();

        $this->addFlash('success', "$count livre(s) mis à jour.");
        return $this->redirectToRoute('admin_dashboard');
    }
}
