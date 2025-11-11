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
        set_time_limit(0); // Permet un import long

        // Récupération de la liste de livres depuis Google Books
        $booksData = $apiService->fetchBookList();

        // Debug rapide pour vérifier ce que renvoie l'API
        // dump($booksData);
        // dd('stop');

        $imported = [];
        $updated = [];
        $count = 0;
        $batchSize = 50;

        foreach ($booksData as $bookData) {
            // Création ou mise à jour du livre
            $book = $creationService->createOrUpdateBookFromApi($bookData);

            if ($book) {
                // Vérifier si le livre est déjà en base
                $isNew = $em->getUnitOfWork()->isScheduledForInsert($book);

                if ($isNew) {
                    $imported[] = $book->getTitle();
                } else {
                    $updated[] = $book->getTitle();
                }

                $count++;

                // Flush par lots pour optimiser la mémoire
                if ($count % $batchSize === 0) {
                    $em->flush();
                    $em->clear();
                }
            }
        }

        // Flush final pour les derniers éléments
        $em->flush();

        // Messages flash
        if (count($imported) > 0) {
            $this->addFlash('success', count($imported) . ' livre(s) importé(s) avec succès !');
        }

        if (count($updated) > 0) {
            $this->addFlash('info', count($updated) . ' livre(s) mis à jour avec succès !');
        }

        if (count($imported) === 0 && count($updated) === 0) {
            $this->addFlash('warning', 'Aucun livre n’a été importé depuis l’API.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/import-all-books', name: 'import_all_books', methods: ['GET'])]
    public function importAllBooks(
        BookApiService $apiService,
        BookCreationService $creationService,
        EntityManagerInterface $em
    ): Response
    {
        set_time_limit(0); // Évite le timeout
        ini_set('memory_limit', '2048M'); // Augmente la mémoire si nécessaire

        $totalImported = 0;
        $totalUpdated = 0;
        $batchSize = 50; 
        $maxResultsPerRequest = 40; 
        $startIndex = 0;

        // Liste des ISBN déjà traités pour ce batch
        $processedIsbns = [];

        while (true) {
            $booksData = $apiService->fetchBookList($startIndex, $maxResultsPerRequest);

            if (empty($booksData)) break; // Plus de résultats, fin de l'import

            $count = 0;

            foreach ($booksData as $bookData) {
                $isbn = $bookData['isbn'] ?? null;
                if (!$isbn) continue; // Ignore si pas d'ISBN
                if (in_array($isbn, $processedIsbns)) continue; // Ignore les doublons dans le batch

                // Vérifie si le livre existe déjà en BDD
                $existingBook = $em->getRepository(Book::class)->findOneBy(['isbn' => $isbn]);

                if ($existingBook) {
                    // Livre déjà en BDD → on ne le recrée pas, mais on peut le mettre à jour si tu veux
                    $creationService->updateBookFromApi($existingBook, $bookData);
                    $totalUpdated++;
                } else {
                    // Livre non présent → création
                    $creationService->createOrUpdateBookFromApi($bookData);
                    $totalImported++;
                }

                $processedIsbns[] = $isbn;
                $count++;

                // Flush par lots pour éviter surcharge mémoire
                if ($count % $batchSize === 0) {
                    $em->flush();
                    $em->clear();
                    $processedIsbns = []; // réinitialise la liste après clear()
                }
            }

            // Flush final pour le batch
            $em->flush();
            $em->clear();
            $processedIsbns = [];

            $startIndex += $maxResultsPerRequest;
        }

        $this->addFlash(
            'success',
            "Import terminé : $totalImported livre(s) importé(s), $totalUpdated livre(s) mis à jour."
        );

        return $this->redirectToRoute('admin_dashboard');
    }
}
