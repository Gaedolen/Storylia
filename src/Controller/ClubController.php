<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Book;
use App\Form\ClubType;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\BookRepository;
use App\Repository\ClubReadingMonthRepository;
use App\Repository\ClubReviewRepository;
use App\Repository\ClubRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/clubs')]
class ClubController extends AbstractController
{
   #[Route('/', name: 'club_index', methods: ['GET'])]
    public function index(Request $request, ClubRepository $clubRepository, UtilisateurRepository $userRepository): Response
    {
        $search = $request->query->get('q');
        $preference = $request->query->get('preference');
        $sort = $request->query->get('sort');

        // --- Clubs ---
        $qb = $clubRepository->createQueryBuilder('c');

        if ($search) {
            $qb->leftJoin('c.creator', 'u')
            ->andWhere('LOWER(c.name) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');

            // Note : on ne filtre pas les clubs par utilisateur ici,
            // les utilisateurs seront récupérés séparément pour la colonne de droite
        }

        // Filtre préférences
        if ($preference) {
            $qb->andWhere(':pref MEMBER OF c.preferences')
            ->setParameter('pref', $preference);
        }

        // Tri
        switch ($sort) {
            case 'recent':
                $qb->orderBy('c.creationDate', 'DESC');
                break;
            case 'old':
                $qb->orderBy('c.creationDate', 'ASC');
                break;
            case 'participants_max':
                $qb->orderBy('SIZE(c.membres)', 'DESC');
                break;
            case 'participants_min':
                $qb->orderBy('SIZE(c.membres)', 'ASC');
                break;
            default:
                $qb->orderBy('c.creationDate', 'DESC');
        }

        $clubs = $qb->getQuery()->getResult();

        // --- Utilisateurs ---
        $users = [];
        if ($search) {
            $users = $userRepository->createQueryBuilder('u')
                ->andWhere('LOWER(u.pseudo) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%')
                ->getQuery()
                ->getResult();
        }

        return $this->render('club/club_listing.html.twig', [
            'clubs' => $clubs,
            'users' => $users,
            'search' => $search,
            'selectedPreference' => $preference,
            'selectedSort' => $sort,
        ]);
    }


    #[Route('/create', name: 'club_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $club = new Club();
        $club->setCreator($this->getUser()); // Créateur = utilisateur connecté

        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid().'_'.time().'.'.$photoFile->guessExtension();
                $photoFile->move($this->getParameter('clubs_photos_directory'), $newFilename);
                $club->setPhoto($newFilename);
            }

            $em->persist($club);
            $em->flush();

            $this->addFlash('success', 'Le club a été créé avec succès !');

            return $this->redirectToRoute('club_index');
        }

        return $this->render('club/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clubs/{id}', name: 'club_show', methods: ['GET'])]
        public function show(
        Club $club,
        ClubReadingMonthRepository $clubReadingMonthRepository,
        ClubReviewRepository $clubReviewRepository,
        BookRepository $bookRepository
    ): Response {
        // Récupérer le mois en cours et le mois prochain pour ce club
        $bookOfMonthReading = $clubReadingMonthRepository->findCurrentMonthByClub($club);
        $bookOfNextMonthReading = $clubReadingMonthRepository->findNextMonthByClub($club);

        $bookOfMonth = $bookOfMonthReading?->getBook();        // Livre du mois courant
        $bookOfNextMonth = $bookOfNextMonthReading?->getBook(); // Livre du mois prochain

        $currentMonthName = $bookOfMonthReading ? date('F', strtotime($bookOfMonthReading->getMonth() . '-01')) : '';
        $nextMonthName = $bookOfNextMonthReading ? date('F', strtotime($bookOfNextMonthReading->getMonth() . '-01')) : '';

        // Récupérer les derniers avis pour ce club (limité à 5)
        $lastReviews = $clubReviewRepository->findBy(
            ['readingMonth' => $bookOfMonthReading],
            ['createdAt' => 'DESC'],
            3
        );

        // Récupérer les 20 derniers livres proposés
        $recentBooks = $clubReadingMonthRepository->findRecentBooks();

        return $this->render('club/club_show.html.twig', [
            'club' => $club,
            'bookOfMonth' => $bookOfMonth,
            'bookOfNextMonth' => $bookOfNextMonth,
            'currentMonthName' => $currentMonthName,
            'nextMonthName' => $nextMonthName,
            'lastReviews' => $lastReviews,
            'recentBooks' => $recentBooks,
        ]);
    }

    #[Route('/club/{id}/participer', name: 'club_participer', methods: ['GET','POST'])]
    public function participer(Club $club, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user && !$club->getMembres()->contains($user)) {
            $club->addMembre($user);
            $em->flush();
            $this->addFlash('success', 'Vous participez maintenant à ce club !');
        }

        return $this->redirectToRoute('club_index');
    }
}
