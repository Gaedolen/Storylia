<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Book;
use App\Entity\BookProposal;
use App\Entity\Utilisateur;
use App\Entity\ClubReadingMonth;
use App\Entity\ReadingMonthBook;
use App\Entity\Vote;
use App\Entity\ClubReview;
use App\Form\VoteType;
use App\Form\ClubType;
use Symfony\Component\Form\FormError;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ClubReadingMonthRepository;
use App\Repository\BookProposalRepository;
use App\Repository\ClubReviewRepository;
use App\Repository\ClubRepository;
use App\Repository\ReportRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

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
    public function show(Club $club, ClubReadingMonthRepository $clubReadingMonthRepository, ClubReviewRepository $clubReviewRepository, BookProposalRepository $bookProposalRepository, ReportRepository $reportRepository): Response 
    {
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            'LLLL' // nom complet du mois
        );

        // Mois courant
        $bookOfMonthReading = $clubReadingMonthRepository->findCurrentMonthByClub($club);
        $bookOfMonth = $bookOfMonthReading?->getBook();
        $currentMonthName = $bookOfMonthReading
            ? ucfirst($formatter->format(new DateTime($bookOfMonthReading->getMonth() . '-01')))
            : '';

        // Mois prochain
        $bookOfNextMonthReading = $clubReadingMonthRepository->findNextMonthByClub($club);
        $bookOfNextMonth = $bookOfNextMonthReading?->getBook();
        $nextMonthName = $bookOfNextMonthReading
            ? ucfirst($formatter->format(new DateTime($bookOfNextMonthReading->getMonth() . '-01')))
            : '';

        // Mois +2
        $bookOfNextNextMonthReading = $clubReadingMonthRepository->findOneBy([
            'club' => $club,
            'month' => (new \DateTimeImmutable('first day of +2 month'))->format('Y-m'),
        ]);
        $nextNextMonthName = $bookOfNextNextMonthReading
            ? ucfirst($formatter->format(new DateTime($bookOfNextNextMonthReading->getMonth() . '-01')))
            : '';

        // Avis 
        $lastReviews = [];
            if ($bookOfMonthReading instanceof ClubReadingMonth) {
            $lastReviews = $clubReviewRepository->findLastReviewsByMonth($bookOfMonthReading, 3);
        }

        // Bloque le btn d'avis si l'utilisateur en a déjà laissé un
        $userHasReviewedMonth = false;
        $user = $this->getUser();

        if ($bookOfMonthReading instanceof ClubReadingMonth && $user instanceof Utilisateur) {
            $monthId = $bookOfMonthReading->getId(); // Ici, getId() n'est jamais sur un null
            $userId = $user->getId();

            $existingReview = $clubReviewRepository->findByUserAndMonth($userId, $monthId);
            $userHasReviewedMonth = ($existingReview !== null);
        }

        // --- Récupération du mois des propositions (+2 mois) ---
        $clubReadingMonth = $clubReadingMonthRepository->findOneBy([
            'club' => $club,
            'month' => (new \DateTimeImmutable('first day of +2 month'))->format('Y-m'),
        ]);

        // --- Livres récemment proposés ---
        $recentBookProposals = $bookProposalRepository->findRecentBooksByClub($club->getId());

        // Condition de vote
        $user = $this->getUser();
        $userHasProposed = false;
        $userHasVoted = false;
        $userCanVote = false;

        // --- Déterminer le livre en tête des votes (+2 mois) ---
        $leadingProposals = [];
        $maxVotes = -1;

        if ($clubReadingMonth instanceof ClubReadingMonth) {
            foreach ($clubReadingMonth->getBookProposals() as $proposal) {
                $voteCount = count($proposal->getVotes());

                if ($voteCount > $maxVotes) {
                    // Nouveau max → on réinitialise
                    $maxVotes = $voteCount;
                    $leadingProposals = [$proposal->getId()];
                } elseif ($voteCount === $maxVotes) {
                    // Égalité → on ajoute
                    $leadingProposals[] = $proposal->getId();
                }
            }
        }

        /** @var Utilisateur $user */
        if ($user instanceof Utilisateur && $clubReadingMonth instanceof ClubReadingMonth) {

            // --- Vérifier si l’utilisateur a proposé un livre ---
            foreach ($clubReadingMonth->getBookProposals() as $proposal) {
                $proposer = $proposal->getProposer(); // Utilisateur qui a proposé
                if ($proposer instanceof Utilisateur && $proposer === $user) {
                    $userHasProposed = true;
                    break;
                }
            }

            // --- Vérifier si l’utilisateur a déjà voté ---
            foreach ($clubReadingMonth->getVotes() as $vote) {
                $voter = $vote->getUtilisateur();
                if (!$voter instanceof Utilisateur) {
                    continue; // Ignore si pas d’utilisateur
                }

                if ($voter->getId() === $user->getId()) {
                    $userHasVoted = true;
                    break;
                }
            }

            // --- Peut-il voter ? ---
            $userCanVote = $userHasProposed && !$userHasVoted;
        }

        /** @var \App\Entity\Report|null $hasReportedClub */
        $hasReportedClub = null;

        if ($this->getUser()) {
            $hasReportedClub = $reportRepository->findOneBy([
                'author' => $this->getUser(),
                'reportedClub' => $club,
            ]);
        }

        return $this->render('club/club_show.html.twig', [
            'club' => $club,
            'bookOfMonth' => $bookOfMonth,
            'bookOfNextMonth' => $bookOfNextMonth,
            'currentMonthName' => $currentMonthName,
            'bookOfMonthReading' => $bookOfMonthReading,
            'nextMonthName' => $nextMonthName,
            'nextNextMonthName' => $nextNextMonthName,
            'lastReviews' => $lastReviews,
            'clubReadingMonth' => $clubReadingMonth,
            'userHasReviewedMonth' => $userHasReviewedMonth,
            'recentBookProposals' => $recentBookProposals,
            'userHasProposed' => $userHasProposed,
            'userHasVoted' => $userHasVoted,
            'userCanVote' => $userCanVote,
            'leadingProposals' => $leadingProposals,
            'hasReportedClub' => $hasReportedClub
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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function info(Club $club): Response
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser(); // Utilisateur connecté garanti

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

        $booksWithVoteStatus = [];

        foreach ($club->getReadingMonths() as $month) {
            foreach ($month->getBookProposals() as $proposal) {
                $hasVoted = false;

                foreach ($proposal->getVotes() as $vote) {
                    if ($vote->getUtilisateur() === $user) {
                        $hasVoted = true;
                        break;
                    }
                }

                $booksWithVoteStatus[] = [
                    'proposal' => $proposal,
                    'month' => $month,
                    'hasVoted' => $hasVoted,
                ];
            }
        }

        return $this->render('club/informations.html.twig', [
            'club' => $club,
            'creator' => $creator,
            'members' => $members,
            'totalBooksProposed' => $totalBooksProposed,
            'totalBooksRead' => $totalBooksRead,
            'booksWithVoteStatus' => $booksWithVoteStatus,
        ]);
    }

    #[Route('/club/{id}/propositions', name: 'club_propositions')]
    public function propositions(Club $club, EntityManagerInterface $em, Utilisateur $user): Response
    {
        $readingMonths = $club->getReadingMonths()->toArray();

        // Trier du plus récent au plus ancien
        usort($readingMonths, fn($a, $b) => strtotime($b->getMonth()) - strtotime($a->getMonth()));

        // Mois cible : mois +2
        $targetMonth = (new \DateTimeImmutable('first day of +2 month'))->format('Y-m');

        // Nom du mois cible en français
        $dt = new \DateTimeImmutable($targetMonth . '-01');
        $fmt = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            $dt->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            'MMMM yyyy'
        );
        $targetMonthName = ucfirst($fmt->format($dt));

        // Récupérer le readingMonth correspondant au mois cible
        $existingReadingMonth = null;
        foreach ($readingMonths as $rm) {
            if ($rm->getMonth() === $targetMonth) {
                $existingReadingMonth = $rm;
                break;
            }
        }

        if (!$existingReadingMonth) {
            $rmNext = new ClubReadingMonth();
            $rmNext->setMonth($targetMonth);
            $rmNext->setClub($club);
            $em->persist($rmNext);
            $em->flush();
            $readingMonths[] = $rmNext;
            $existingReadingMonth = $rmNext;
        }

        // --- Vérifier si l’utilisateur a proposé un livre ---
        $userHasProposed = false;
        foreach ($existingReadingMonth->getBookProposals() as $proposal) {
            $proposer = $proposal->getProposer(); // Utilisateur qui a proposé le livre
            if ($proposer instanceof Utilisateur && $proposer === $user) {
                $userHasProposed = true;
                break;
            }
        }

        // Déterminer si le bouton général peut être actif
        $canPropose = !$userHasProposed;

        // Ajouter nom français pour chaque mois
        foreach ($readingMonths as $rm) {
            $dtMonth = new \DateTimeImmutable($rm->getMonth() . '-01');
            $fmtMonth = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::NONE,
                $dtMonth->getTimezone(),
                \IntlDateFormatter::GREGORIAN,
                'MMMM yyyy'
            );
            $rm->monthNameFr = ucfirst($fmtMonth->format($dtMonth));
        }

        return $this->render('club/propositions.html.twig', [
            'club' => $club,
            'readingMonths' => $readingMonths,
            'targetMonth' => $targetMonth,
            'targetMonthName' => $targetMonthName,
            'canPropose' => $canPropose,
            'userHasProposed' => $userHasProposed, // <- pour Twig
        ]);
    }

    #[Route('/club/{clubId}/proposer-livre/{month}', name: 'club_proposer_livre')]
    public function proposerLivre(Request $request, int $clubId, string $month, EntityManagerInterface $em, BookRepository $bookRepo, ClubRepository $clubRepo): Response {

        /** @var Utilisateur $user */
        $user = $this->getUser();


        $club = $clubRepo->find($clubId);
        if (!$club) throw $this->createNotFoundException('Club non trouvé');

        $readingMonth = $club->getReadingMonths()->filter(fn($rm) => $rm->getMonth() === $month)->first();
        if (!$readingMonth) {
            $readingMonth = new ClubReadingMonth();
            $readingMonth->setMonth($month);
            $readingMonth->setClub($club);
            $em->persist($readingMonth);
        }

        // Vérifier si l'utilisateur a déjà proposé ce mois
        $existingProposal = $readingMonth->getBookProposals()
            ->filter(fn($p) => $p->getProposer()->getId() === $user->getId())
            ->first();

        $canPropose = !$readingMonth->getBookProposals()
            ->exists(fn($key, $p) => $p->getProposer()->getId() === $user->getId());


        if ($request->isMethod('POST')) {
            if (!$canPropose) {
                $this->addFlash('error', 'Vous avez déjà proposé un livre ce mois.');
                return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
            }

            $bookId = $request->request->get('bookId');
            $book = $bookRepo->find($bookId);
            if (!$book) {
                $this->addFlash('error', 'Livre introuvable.');
                return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
            }

            $proposal = new BookProposal();
            $proposal->setBook($book)
                    ->setProposer($user)
                    ->setReadingMonth($readingMonth);

            $em->persist($proposal);
            $em->flush();

            $this->addFlash('success', 'Livre proposé avec succès !');
            return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
        }

        // AJAX modal
        if ($request->isXmlHttpRequest()) {
            $books = $bookRepo->findBy([], ['title' => 'ASC'], 10);
            return $this->render('club/proposition_modal.html.twig', [
                'club' => $club,
                'month' => $month,
                'books' => $books,
                'canPropose' => $canPropose
            ]);
        }

        return $this->redirectToRoute('club_propositions', ['id' => $clubId]);
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

    #[Route('/club/{clubId}/vote', name: 'club_vote', methods: ['POST'])]
    public function vote(int $clubId, Request $request, EntityManagerInterface $em): Response 
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voter.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le club
        $club = $em->getRepository(Club::class)->find($clubId);
        if (!$club) {
            throw $this->createNotFoundException("Club introuvable.");
        }

        // Mois +2
        $month = (new \DateTimeImmutable('first day of +2 months'))->format('Y-m');
        $clubReadingMonth = $em->getRepository(ClubReadingMonth::class)
            ->findOneBy(['club' => $club, 'month' => $month]);

        if (!$clubReadingMonth) {
            $this->addFlash('error', 'Le mois de lecture n’a pas encore été créé.');
            return $this->redirectToRoute('club_show', ['id' => $clubId]);
        }

        // Déjà voté ?
        $existingVote = $em->getRepository(Vote::class)
            ->findOneBy(['utilisateur' => $user, 'clubReadingMonth' => $clubReadingMonth]);
        if ($existingVote) {
            $this->addFlash('warning', 'Vous avez déjà voté pour ce mois.');
            return $this->redirectToRoute('club_show', ['id' => $clubId]);
        }

        // A proposé un livre ?
        $userProposed = false;
        foreach ($clubReadingMonth->getBookProposals() as $proposal) {
            if ($proposal->getProposer() === $user) {
                $userProposed = true;
                break;
            }
        }
        if (!$userProposed) {
            $this->addFlash('warning', 'Vous devez proposer un livre avant de voter.');
            return $this->redirectToRoute('club_show', ['id' => $clubId]);
        }

        // Récupérer l'ID de la proposition sélectionnée
        $voteData = $request->request->all('vote');
        $bookProposalId = $voteData['bookProposal'] ?? null;

        if ($bookProposalId) {
            $proposal = $em->getRepository(BookProposal::class)->find($bookProposalId);
            if ($proposal) {
                $vote = new Vote();
                $vote->setUtilisateur($user);
                $vote->setClubReadingMonth($clubReadingMonth);
                $vote->setBookProposal($proposal);
                $em->persist($vote);
                $em->flush();

                $this->addFlash('success', 'Votre vote a été enregistré !');
            }
        }

        // Calculer le livre en tête des votes pour ce mois
        $proposals = $clubReadingMonth->getBookProposals();
        $leadingProposalId = null;
        $maxVotes = -1;

        foreach ($proposals as $proposal) {
            $votesCount = count($proposal->getVotes()); // Assure-toi que getVotes() existe
            if ($votesCount > $maxVotes) {
                $maxVotes = $votesCount;
                $leadingProposalId = $proposal->getId();
            }
        }

        // Rediriger vers le show du club en passant leadingProposalId
        return $this->redirectToRoute('club_show', [
            'id' => $clubId,
            'leadingProposalId' => $leadingProposalId
        ]);
    }

    #[Route('/club/review/add', name: 'club_review_add', methods: ['POST'])]
    public function addReview(Request $request, EntityManagerInterface $em, Security $security, ClubReadingMonthRepository $monthRepo): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $security->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'], $data['readingMonthId'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $month = $monthRepo->find($data['readingMonthId']);
        if (!$month) {
            return new JsonResponse(['error' => 'Month not found'], 404);
        }

        $review = new ClubReview();
        $review->setComment($data['comment']);
        $review->setRating($data['rating'] ?? null);
        $review->setUser($user);
        $review->setReadingMonth($month);

        $em->persist($review);
        $em->flush();

        return new JsonResponse(['success' => true], 201);
    }

    #[Route('/clubs/{id}/reviews', name: 'club_all_reviews', methods: ['GET'])]
    public function allReviews(
        Club $club,
        ClubReadingMonthRepository $clubReadingMonthRepository,
        ClubReviewRepository $clubReviewRepository
    ): Response {

        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            'LLLL'
        );

        // Mois courant
        $readingMonth = $clubReadingMonthRepository->findCurrentMonthByClub($club);

        if (!$readingMonth) {
            throw $this->createNotFoundException("Aucun livre prévu pour ce mois.");
        }

        $book = $readingMonth->getBook();

        $currentMonthName = ucfirst(
            $formatter->format(new DateTime($readingMonth->getMonth() . '-01'))
        );

        // Tous les avis du mois courant
        $reviews = $clubReviewRepository->findBy(
            ['readingMonth' => $readingMonth],
            ['createdAt' => 'DESC']
        );

        return $this->render('club/all_reviews.html.twig', [
            'club'            => $club,
            'book'            => $book,
            'readingMonth'    => $readingMonth,
            'currentMonthName'=> $currentMonthName,
            'reviews'         => $reviews,
        ]);
    }
}
