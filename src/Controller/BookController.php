<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Bookshelf;
use App\Entity\Author;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\BookRepository;
use App\Repository\BookshelfRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/livres')]
class BookController extends AbstractController
{
    private string $googleBooksApiKey;

    public function __construct(string $googleBooksApiKey)
    {
        $this->googleBooksApiKey = $googleBooksApiKey;
    }

    #[Route('/recherche', name:'app_recherche_livre')]
    public function search(Request $request, BookRepository $bookRepository, EntityManagerInterface $em): Response
    {
        // Récupération des paramètres de recherche et filtres
        $query = trim($request->query->get('q', ''));      // Recherche texte sur titre ou auteur
        $genre = $request->query->get('category');        // Filtre par genre
        $noteMin = $request->query->get('note_min');      // Filtre par note minimale
        $dateFilter = $request->query->get('date');      // Tri par date (recent/ancien)
        $page = $request->query->getInt('page', 1);      // Page actuelle pour la pagination
        $limit = 20;                                     // Nombre de résultats par page

        // Variables par défaut
        $livres = [];
        $authors = [];
        $totalPages = 0;

        // Si l'utilisateur a saisi quelque chose dans le champ de recherche
        if (!empty($query)) {
            // Création du QueryBuilder
            $qb = $em->createQueryBuilder()
                ->select('b', 'a')                           // On sélectionne les livres et leurs auteurs
                ->from(Book::class, 'b')
                ->leftJoin('b.author', 'a')                  // Jointure pour filtrer sur le nom de l'auteur
                ->where('LOWER(b.title) LIKE LOWER(:query) OR LOWER(a.name) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$query.'%');

            // Filtre par date / tri
            if ($dateFilter === 'recent') {
                $qb->orderBy('b.publicationDate', 'DESC');
            } elseif ($dateFilter === 'ancien') {
                $qb->orderBy('b.publicationDate', 'ASC');
            } else {
                $qb->orderBy('b.title', 'ASC'); // Tri par défaut par titre
            }

            // Exécution de la requête principale avec pagination
            $livres = $qb
                ->setFirstResult(($page - 1) * $limit) // Décalage pour pagination
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            // Filtre note minimale côté PHP (évite GROUP BY PostgreSQL)
            if (!empty($noteMin)) {
                $livres = array_filter($livres, function($book) use ($noteMin) {
                    $avg = $book->getAverageRating(); // Méthode dans Book qui calcule la moyenne des reviews
                    return $avg !== null && $avg >= (float)$noteMin;
                });
                $livres = array_values($livres); // Réindexe le tableau après array_filter
            }

            // Récupération des auteurs distincts pour les résultats
            $authors = $em->createQueryBuilder()
                ->select('DISTINCT a')
                ->from(Author::class, 'a')
                ->join('a.books', 'b')                       // Relation inverse : Author → Book
                ->where('LOWER(b.title) LIKE LOWER(:query) OR LOWER(a.name) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$query.'%')
                ->getQuery()
                ->getResult();

            // Calcul du nombre total de pages après filtres
            $total = count($livres);
            $totalPages = ceil($total / $limit);
        }

        // Rendu du template avec les variables
        return $this->render('book/search.html.twig', [
            'livres' => $livres,
            'auteurs' => $authors,
            'query' => $query,
            'page' => $page,
            'totalPages' => $totalPages,
            'genre' => $genre,
            'noteMin' => $noteMin,
            'dateFilter' => $dateFilter
        ]);
    }

    #[Route('/recherche-rapide', name: 'livres_recherche_rapide')]
    public function rechercheRapide(Request $request, BookRepository $bookRepository): JsonResponse
    {
        $titleQuery = $request->query->get('q', '');
        $authorQuery = $request->query->get('author', '');

        if (strlen($titleQuery) < 2) {
            return $this->json([]);
        }

        $books = $bookRepository->rechercheRapideSQL($titleQuery, $authorQuery);

        return $this->json($books);
    }

    #[Route('/creation', name: 'livres_creation', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, BookRepository $bookRepository, HttpClientInterface $client): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $bookTitle = trim($data['title'] ?? '');
        $authorName = trim($data['author'] ?? '');

        if (!$bookTitle || !$authorName) {
            return $this->json([
                'success' => false,
                'message' => "Le titre et le nom de l'auteur sont obligatoires."
            ]);
        }

        // Gestion de l'auteur
        $author = $em->getRepository(Author::class)->findOneBy(['name' => $authorName]);
        if (!$author) {
            $author = new Author();
            $author->setName($authorName);
            $em->persist($author);
        }

        // Requête Google Books
        $googleResponse = $client->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
            'query' => [
                'q' => $bookTitle . ' ' . $authorName,
                'maxResults' => 1,
                'key' => $this->googleBooksApiKey,
                'langRestrict' => 'fr'
            ]
        ]);

        $googleData = $googleResponse->toArray();
        $bookInfo = !empty($googleData['items']) ? $googleData['items'][0]['volumeInfo'] : [];

        $format = $bookInfo['printType'] ?? null;

        // Vérification si le livre existe déjà
        $existingBook = $em->getRepository(Book::class)->findOneBy([
            'title' => $bookTitle,
            'author' => $author,
            'format' => $format
        ]);

        if ($existingBook) {
            return $this->json([
                'success' => false,
                'message' => 'Ce livre existe déjà en base.'
            ]);
        }

        // Création du livre
        $book = new Book();
        $book->setTitle($bookTitle);
        $book->setAuthor($author);
        $book->setFormat($format);
        $book->setIsbn($bookInfo['industryIdentifiers'][0]['identifier'] ?? null);
        $book->setCover($bookInfo['imageLinks']['thumbnail'] ?? null);
        $book->setSummary($bookInfo['description'] ?? null);
        $book->setPages($bookInfo['pageCount'] ?? null);
        $book->setPublicationDate(!empty($bookInfo['publishedDate']) ? new DateTime($bookInfo['publishedDate']) : null);
        $book->setPublishers(!empty($bookInfo['publisher']) ? [$bookInfo['publisher']] : []);
        $book->setGenres($bookInfo['categories'] ?? []);
        $book->setSubjects($bookInfo['categories'] ?? []);

        $em->persist($book);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Livre créé avec succès !',
            'bookId' => $book->getId()
        ]);
    }

    #[Route('/{id}', name: 'app_livre_detail')]
    public function detail(Book $book, ReviewRepository $reviewRepo,  BookshelfRepository $bookshelfRepo, Request $request)
    {
        $user = $this->getUser();

        $maxSubjects = 10;
        $currentSubjects = $book->getSubjects();
        $availableSlots = $maxSubjects - count($currentSubjects);

        // Pagination reviews
        $page = $request->query->getInt('page', 1);
        $reviewsPerPage = 5;
        $totalReviews = count($book->getReviews());
        $totalPages = ceil($totalReviews / $reviewsPerPage);

        $reviews = $reviewRepo->findBy(
            ['book' => $book],
            ['date' => 'DESC'],
            $reviewsPerPage,
            ($page - 1) * $reviewsPerPage
        );

        // Vérifie si le livre est dans la bibliothèque de l'utilisateur
        $isInLibrary = false;
        if ($user) {
            $isInLibrary = $bookshelfRepo->findOneBy([
                'book' => $book,
                'utilisateur' => $user
            ]) !== null;
        }

        return $this->render('book/detail.html.twig', [
            'book' => $book,
            'reviews' => $reviews,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'isInLibrary' => $isInLibrary,
            'user' => $user,
            'availableSlots' => $availableSlots,
        ]);
    }

    #[Route('/{id}/edit-cover', name: 'app_livre_edit_cover', methods: ['POST'])]
    public function editCover(Request $request, Book $book, EntityManagerInterface $em): JsonResponse
    {
        $file = $request->files->get('cover');

        if ($file) {
            $imageData = file_get_contents($file->getPathname());
            $book->setCover('data:' . $file->getMimeType() . ';base64,' . base64_encode($imageData));
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'newCover' => $book->getCover()
                ]);
            }
        }

        return $this->json([
            'success' => false,
            'message' => 'Aucun fichier sélectionné'
        ]);
    }

    #[Route('/{id}/edit-subjects', name: 'app_livre_edit_subjects', methods: ['POST'])]
    public function editSubjects(Request $request, Book $book, EntityManagerInterface $em): JsonResponse
    {
        $maxSubjects = 10;
        $currentSubjects = $book->getSubjects();
        $availableSlots = $maxSubjects - count($currentSubjects);

        if ($availableSlots <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Le livre a déjà le nombre maximum de thèmes.'
            ]);
        }

        // Récupère les sujets via FormData
        $newSubjects = $request->request->all('subjects');

        if (!is_array($newSubjects)) $newSubjects = [];

        // Nettoyage
        $newSubjects = array_filter($newSubjects, fn($s) => trim($s) !== '');
        $newSubjects = array_map('trim', $newSubjects);

        // Supprimer doublons
        $newSubjects = array_unique($newSubjects);

        // Limite
        $newSubjects = array_slice($newSubjects, 0, $availableSlots);

        // Fusion propre
        $allSubjects = array_merge($currentSubjects, $newSubjects);

        // Enregistrer
        $book->setSubjects($allSubjects);
        $em->flush();

        return $this->json([
            'success' => true,
            'newSubjects' => $allSubjects
        ]);
    }
}