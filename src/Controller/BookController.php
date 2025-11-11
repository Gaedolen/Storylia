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
    public function search(Request $request, BookRepository $bookRepository, EntityManagerInterface $em) : Response
    {
        // On récupère la valeur du champ de recherche 'q'
        $query = trim($request->query->get('q', ''));

        // Page actuelle pour la pagination
        $page = $request->query->getInt('page', 1);

        // Nombre dr résultats par page
        $limit = 20;

        // Initilisation des variables par défaut
        $livres = [];
        $authors = [];
        $totalPages = 0;
        
        // Si une valeur est entrée dans le champ de saisie
        if(!empty($query)) {
            // Requête initiale
            $qb = $em->createQueryBuilder()
                ->select('b', 'a') // On sélectionne les livres et les auteurs
                ->from(Book::class, 'b')
                ->leftJoin('b.author', 'a') // Jointure pour filtrer sur le nom de l'auteur
                ->where('LOWER(b.title) LIKE LOWER(:query)') // Recherche insensible à la casse sur le titre
                ->orWhere('LOWER(a.name) LIKE LOWER (:query)') // Recherche insensible à la casse sur le nom de l'auteur
                -> setParameter('query', '%'. $query .'%')
                ->orderBy('b.title', 'ASC'); // Tri par ordre alphabétique
            
            // Clone de la requête principale pour compter le total sans réécrire la logique
            $countQb = clone $qb;

            // On retire le ORDER BY du clone
            $countQb->resetDQLPart('orderBy');

            // expr() pour éviter les erreurs de syntaque DQL
            $countQb->select(
                $countQb->expr()->countDistinct('b.id')
            );

            // Exécution de la requête de comptage
            $total = (int) $countQb->getQuery()->getSingleScalarResult();

            // Calcul du nb total de pages selon la limite choisie
            $totalPages = ceil($total / $limit);

            // Requête finale
            $livres = $qb
                ->setFirstResult(($page - 1) * $limit) // Décalage pour la pagination
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            // Requête pour récupérer les auteurs distincts des livres trouvés
            $authors = $em->createQueryBuilder()
                ->select('DISTINCT a')
                ->from(Author::class, 'a')                  // racine = Author
                ->join('a.books', 'b')                      // relation inverse : Author → Book
                ->where('LOWER(b.title) LIKE LOWER(:query) OR LOWER(a.name) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$query.'%')
                ->getQuery()
                ->getResult();
        } else {
            $totalPages = 0;
        }

        // Affichage des résultats
        return $this->render('book/search.html.twig', [
            'livres' => $livres,
            'auteurs' => $authors,
            'query' => $query,
            'page' => $page,
            'totalPages' => $totalPages
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