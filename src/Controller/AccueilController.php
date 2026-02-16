<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\ClubRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    #[Route('/accueil', name: 'app_accueil')]
    public function index(BookRepository $bookRepository, EntityManagerInterface $em, ClubRepository $clubRepository, ReviewRepository $reviewRepository): Response
    {
        // Afficher les coups de coeur de la communauté avec note moyenne
        $favoritesQuery = $em->createQuery(
            'SELECT b, AVG(r.rating) AS avgRating
             FROM App\Entity\Book b
             JOIN App\Entity\Review r WITH r.book = b
             GROUP BY b.id
             ORDER BY avgRating DESC'
        )->setMaxResults(20);

        $favoritesRaw = $favoritesQuery->getResult();

        // Transformer le résultat pour Twig
        $favorites = [];
        foreach ($favoritesRaw as $row) {
            $favorites[] = [
                'book' => $row[0],       // objet Book
                'avgRating' => $row['avgRating'], // note moyenne
            ];
        }

        // Fixe la timezone
        date_default_timezone_set('Europe/Paris');
        $now = new \DateTimeImmutable('now');
        
        // Livres du mois précédent
        $lastMonthStart = $now->modify('first day of last month')->setTime(0,0,0);
        $lastMonthEnd   = $now->modify('last day of last month')->setTime(23,59,59);

        $lastMonthBooks = $bookRepository->createQueryBuilder('b')
            ->where('b.publicationDate BETWEEN :start AND :end')
            ->setParameter('start', $lastMonthStart->format('Y-m-d'))
            ->setParameter('end', $lastMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Livres du mois courant
        $currentMonthStart = $now->modify('first day of this month')->setTime(0,0,0);
        $currentMonthEnd   = $now->modify('last day of this month')->setTime(23,59,59);

        $currentMonthBooks = $bookRepository->createQueryBuilder('b')
            ->where('b.publicationDate BETWEEN :start AND :end')
            ->setParameter('start', $currentMonthStart->format('Y-m-d'))
            ->setParameter('end', $currentMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Livres du mois prochain
        $nextMonthStart = $now->modify('first day of next month')->setTime(0,0,0);
        $nextMonthEnd   = $now->modify('last day of next month')->setTime(23,59,59);

        $nextMonthBooks = $bookRepository->createQueryBuilder('b')
            ->where('b.publicationDate BETWEEN :start AND :end')
            ->setParameter('start', $nextMonthStart->format('Y-m-d'))
            ->setParameter('end', $nextMonthEnd->format('Y-m-d'))
            ->orderBy('b.publicationDate', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Derniers clubs créés
        $lastClubs = $clubRepository->findBy([], ['creationDate' => 'DESC'], 5);

        // Derniers avis déposés
        $lastReviews = $reviewRepository->findBy([], ['date' => 'DESC'], 5);

        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'favorites' => $favorites,
            'lastMonthBooks' => $lastMonthBooks,
            'currentMonthBooks' => $currentMonthBooks,
            'nextMonthBooks' => $nextMonthBooks,
            'lastClubs' => $lastClubs,
            'lastReviews' => $lastReviews,
        ]);
    }

    #[Route('/a-propos', name: 'a_propos')]
    public function aPropos(): Response
    {
        return $this->render('accueil/a_propos.html.twig');
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

    #[Route('/nous-contacter', name: 'nous_contacter')]
    public function nousContacter(): Response
    {
        return $this->render('accueil/contact.html.twig');
    }
}
