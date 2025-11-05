<?php

namespace App\Controller;

use App\Entity\Book;
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
    public function search(Request $request, BookRepository $bookRepository) : Response
    {
        // On récupère la valeur du champ de recherche 'q'
        $query = $request->query->get('q',''); // Si rien n'est envoyé, on met une chaîne vide
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        // Tableau des résultats de la recherche
        $livres = [];
        $authors = [];

        // Si une recherche est effectuée
        if(!empty($query)) {
            // Recherche dans la BDD des livres dont l'auteur ou le titre contient le texte recherché
            $qb = $bookRepository->createQueryBuilder('b')
                ->where('b.title LIKE :query OR b.author LIKE :query')
                ->setParameter('query', '%'.$query.'%')
                ->orderBy('b.title', 'ASC'); // Tri par titre
            // Pagination
            $livres = $qb
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            // Récupérer le total pour créer les pages
            $total = $qb->select('COUNT(b.id)') // On compte le nb total de résultats
                ->getQuery()
                ->getSingleScalarResult();
            $totalPages = ceil($total / $limit);

            // Récupérer les auteurs
            $authors = $bookRepository->createQueryBuilder('b')
                ->select('DISTINCT b.author')
                ->where('b.title LIKE :query OR b.author LIKE :query')
                ->setParameter('query', '%'.$query.'%')
                ->getQuery()
                ->getResult();
        } else {
            $totalPages = 0;
        }

        // On renvoie la vue Twig
        return $this->render('book/search.html.twig', [
            'livres' => $livres, //résultats de recherche
            'auteurs' => $authors,
            'query' => $query, // texte recherché
            'page' =>$page,
            'totalPages' => $totalPages,
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
}