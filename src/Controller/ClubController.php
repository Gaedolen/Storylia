<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\ClubReadingMonth;
use App\Entity\ReadingMonthBook;
use App\Form\ClubType;
use Symfony\Component\Form\FormError;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $recentBooks = $clubReadingMonthRepository->findRecentBooks($club);

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

    #[Route('/clubs/{id}/edit', name: 'club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur connecté est le créateur
        $this->denyAccessUnlessGranted('ROLE_USER'); 
        if ($this->getUser() !== $club->getCreator()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos clubs.');
        }

        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du nom du club
            $existingClub = $em->getRepository(Club::class)->findOneBy(['name' => $club->getName()]);
            if ($existingClub && $existingClub !== $club) {
                $form->get('name')->addError(new FormError('Ce nom de club existe déjà.'));
            } else {
                // Gérer la photo avant le flush
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $newFilename = uniqid() . '.' . $photoFile->guessExtension();
                    $photoFile->move(
                        $this->getParameter('clubs_photos_directory'),
                        $newFilename
                    );
                    $club->setPhoto($newFilename);
                }

                $em->persist($club);
                $em->flush();

                $this->addFlash('success', 'Club modifié avec succès.');
                return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
            }
        }

        return $this->render('club/edit.html.twig', [
            'club' => $club,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clubs/{id}/info', name: 'club_info', methods: ['GET'])]
    public function info(Club $club): Response
    {
        // Créateur
        $creator = $club->getCreator();

        // Membres
        $members = $club->getMembres();

        // Nombre total de livres proposés pour ce club
        $totalBooksProposed = $club->getReadingMonths()->count();

        // Nombre de livres lus (livres sélectionnés pour chaque mois)
        $totalBooksRead = 0;
        foreach ($club->getReadingMonths() as $month) {
            if ($month->getBook() !== null) {
                $totalBooksRead++;
            }
        }

        return $this->render('club/informations.html.twig', [
            'club' => $club,
            'creator' => $creator,
            'members' => $members,
            'totalBooksProposed' => $totalBooksProposed,
            'totalBooksRead' => $totalBooksRead,
        ]);
    }

    #[Route('/club/{id}/propositions', name: 'club_propositions')]
    public function propositions(Club $club): Response
    {
        $readingMonths = $club->getReadingMonths()->toArray();

        // Trier du plus récent au plus ancien
        usort($readingMonths, fn($a, $b) => strtotime($b->getMonth()) - strtotime($a->getMonth()));

        // --- Calculer le mois cible (mois +2) ---
        $targetMonth = (new \DateTimeImmutable('first day of +2 month'))->format('Y-m');

        // Vérifier si un livre a déjà été proposé pour ce mois
        $existingReadingMonth = null;
        foreach ($readingMonths as $rm) {
            if ($rm->getMonth() === $targetMonth) {
                $existingReadingMonth = $rm;
                break;
            }
        }

        // Créer un ClubReadingMonth "virtuel" si le mois n'existe pas encore
        if (!$existingReadingMonth) {
            $rmNext = new ClubReadingMonth();
            $rmNext->setMonth($targetMonth);
            $rmNext->setClub($club);
            $readingMonths[] = $rmNext;
            $existingReadingMonth = $rmNext;
        }

        // Déterminer si le bouton peut être actif
        $canPropose = $existingReadingMonth->getBook() === null;

        // Re-trier après ajout
        usort($readingMonths, fn($a, $b) => strtotime($b->getMonth()) - strtotime($a->getMonth()));

        return $this->render('club/propositions.html.twig', [
            'club' => $club,
            'readingMonths' => $readingMonths,
            'targetMonth' => $targetMonth,
            'canPropose' => $canPropose,
        ]);
    }

    #[Route('/club/{clubId}/proposer-livre/{month}', name: 'club_proposer_livre')]
    public function proposerLivre(
        Request $request,
        int $clubId,
        string $month,
        EntityManagerInterface $em,
        BookRepository $bookRepo,
        ClubRepository $clubRepo
    ): Response {
        // --- Récupération du club ---
        $club = $clubRepo->find($clubId);
        if (!$club) {
            throw $this->createNotFoundException('Club non trouvé');
        }

        // -------------------------------
        // AJOUTER ICI LA VÉRIFICATION DU MOIS
        // -------------------------------
        $expectedMonth = (new \DateTimeImmutable('first day of +2 month'))->format('Y-m');

        if ($month !== $expectedMonth) {
            $this->addFlash('error', 'Vous ne pouvez proposer un livre que pour le mois prévu.');
            return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
        }

        $readingMonth = $club->getReadingMonths()->filter(fn($rm) => $rm->getMonth() === $month)->first();
        if ($readingMonth && $readingMonth->getBook() !== null) {
            $this->addFlash('error', 'Un livre a déjà été proposé pour ce mois.');
            return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
        }

        // --- Gestion POST (proposition de livre) ---
        if ($request->isMethod('POST')) {
            $bookId = $request->request->get('bookId');

            if (!$bookId) {
                $this->addFlash('error', 'Aucun livre sélectionné.');
                return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
            }

            $book = $bookRepo->find($bookId);
            if (!$book) {
                $this->addFlash('error', 'Livre introuvable.');
                return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
            }

            // --- Créer ou mettre à jour le mois ---
            if (!$readingMonth) {
                $readingMonth = new ClubReadingMonth();
                $readingMonth->setClub($club);
                $readingMonth->setMonth($month);
                $em->persist($readingMonth);
            }

            // Assigner le livre
            $readingMonth->setBook($book);
            $em->flush();

            $this->addFlash('success', 'Livre proposé avec succès !');
            return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
        }

        // --- Gestion AJAX pour la modal ---
        if ($request->isXmlHttpRequest()) {
            $books = $bookRepo->findBy([], ['title' => 'ASC'], 10);
            return $this->render('club/proposition_modal.html.twig', [
                'club' => $club,
                'month' => $month,
                'books' => $books,
            ]);
        }

        // --- Affichage normal ---
        return $this->render('club/propositions.html.twig', [
            'club' => $club,
            'readingMonths' => $club->getReadingMonths()->toArray(),
            'nextMonth' => $month,
        ]);
    }

    #[Route('/clubs/{clubId}/recherche-livre', name: 'club_recherche_livre', methods: ['GET'])]
    public function rechercheLivre(Request $request, BookRepository $bookRepo, int $clubId): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $books = $bookRepo->createQueryBuilder('b')
            ->where('LOWER(b.title) LIKE :q OR LOWER(b.author) LIKE :q')
            ->setParameter('q', '%'.strtolower($query).'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($b) => [
            'id' => $b->getId(),
            'title' => $b->getTitle(),
            'author' => $b->getAuthor()->getName(),
            'cover' => $b->getCover()
        ], $books);

        return new JsonResponse($data);
    }
}
