<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    #[Route('/accueil', name: 'app_accueil')]
    public function index(BookRepository $bookRepository, EntityManagerInterface $em): Response
    {
        // Afficher les coups de coeur de la communauté
        $favoritesQuery = $em->createQuery(
            'SELECT b, AVG(r.rating) AS avgRating
            FROM App\Entity\Book b
            JOIN App\Entity\Review r WITH r.book = b
            GROUP BY b.id
            ORDER BY avgRating DESC'
        )->setMaxResults(20);

        $favorites = $favoritesQuery->getResult();

        // Fixe la timezone
        date_default_timezone_set('Europe/Paris');
        $now = new \DateTimeImmutable('now');
        
        // Afficher les sorties du mois précédent
        $lastMonthStart = $now->modify('first day of last month')->setTime(0,0,0);
        $lastMonthEnd   = $now->modify('last day of last month')->setTime(23,59,59);

        $lastMonthBooks = $bookRepository->createQueryBuilder('b') // Requête SQL via Doctrine
            ->where('b.publicationDate BETWEEN :start AND :end') // On récupère les livres dont la publication est comprise entre les param :start et :end
            ->setParameter('start', $lastMonthStart->format('Y-m-d'))
            ->setParameter('end', $lastMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC') // On les sélectionne dans l'ordre de sortie (décroissant)
            ->setMaxResults(20) // Limite de 20 livres
            ->getQuery() // Exécution
            ->getResult(); // Renvoie le résultat

        // Afficher les sorties du mois courant
        $currentMonthStart = $now->modify('first day of this month')->setTime(0,0,0);
        $currentMonthEnd   = $now->modify('last day of this month')->setTime(23,59,59);

        $currentMonthBooks = $bookRepository->createQueryBuilder('b') // Requête SQL via Doctrine
            ->where('b.publicationDate BETWEEN :start AND :end') // On récupère les livres dont la publication est comprise entre les param :start et :end
            ->setParameter('start', $currentMonthStart->format('Y-m-d'))
            ->setParameter('end', $currentMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC') // On les sélectionne dans l'ordre de sortie (décroissant)
            ->setMaxResults(20) // Limite de 20 livres
            ->getQuery() // Exécution
            ->getResult(); // Renvoie le résultat

        // Afficher les sorties du mois prochain
        $nextMonthStart = $now->modify('first day of next month')->setTime(0,0,0);
        $nextMonthEnd   = $now->modify('last day of next month')->setTime(23,59,59);

        $nextMonthBooks = $bookRepository->createQueryBuilder('b') // Requête SQL via Doctrine
            ->where('b.publicationDate BETWEEN :start AND :end') // On récupère les livres dont la publication est comprise entre les param :start et :end
            ->setParameter('start', $nextMonthStart->format('Y-m-d'))
            ->setParameter('end', $nextMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC') // On les sélectionne dans l'ordre de sortie (décroissant)
            ->setMaxResults(20) // Limite de 20 livres
            ->getQuery() // Exécution
            ->getResult(); // Renvoie le résultat
        

        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'favorites' => $favorites,
            'lastMonthBooks' => $lastMonthBooks,
            'currentMonthBooks' => $currentMonthBooks,
            'nextMonthBooks' => $nextMonthBooks,
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('accueil/mentions_legales.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('accueil/politique_confidentialite.html.twig');
    }
}
