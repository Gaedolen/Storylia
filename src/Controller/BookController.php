<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/livres')]
class BookController extends AbstractController
{
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

    #[Route ('/{id}', name: 'app_book_show', requirements: ['id' => '\d+'])]
    public function show(Book $book): Response
    {
        return $this->render('book/details.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/ajouter', name: 'app_book_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        return $this->render('book/add.html.twig');
    }

    #[Route ('/creation', name:'livres_creation', methods:['POST'])]
    public function creation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On récupère le corps de la requête HTTP en JSON et on le transforme en tableau PHP
        $data = json_decode($request->getContent(), true);

        // Tous les champs sont obligatoires
        if (empty($data['title']) || empty($data['author']) || empty($data['genre']) || empty($data['parutionDate'])) {
            return new JsonResponse(['success' => false, 'message' => 'Veuillez remplir tous les champs.'], 400);
        }

        // On crée une nouvelle instance Book
        $book = new Book();

        // On remplit l'objet avec les valeurs envoyées par le formulaire
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setGenres($data['genre']);
        $book->setPublicationDate(new DateTime($data['parutionDate']));

        // Enregistrement en BDD
        $em->persist($book); // Préparation de l'objet
        $em->flush(); // Exécution

        // Réponse JSON
        return new JsonResponse([
            'success' => true,
            'message' => 'Livre créé avec succès !',
            'bookId' => $book->getId(),
        ]);
    }
}