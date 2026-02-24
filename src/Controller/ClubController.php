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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;
use DateInterval;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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

        // On filtre les clubs actifs uniquement
        $qb->andWhere('c.status = :status')
        ->setParameter('status', 'actif');

        if ($search) {
            $qb->leftJoin('c.creator', 'u')
            ->andWhere('LOWER(c.name) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');
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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(
        Club $club,
        ClubReadingMonthRepository $clubReadingMonthRepository,
        ClubReviewRepository $clubReviewRepository,
        BookProposalRepository $bookProposalRepository,
        ReportRepository $reportRepository
    ): Response 
    {
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, null, 'LLLL');

        $user = $this->getUser();
        $userId = $user instanceof Utilisateur ? $user->getId() : null;

        // --- Livres du mois ---
        $bookOfMonthReading = $clubReadingMonthRepository->findCurrentMonthByClub($club);
        $bookOfMonth = $bookOfMonthReading?->getBook();
        $currentMonthName = $bookOfMonthReading
            ? ucfirst($formatter->format(new DateTime($bookOfMonthReading->getMonth() . '-01')))
            : '';

        $bookOfNextMonthReading = $clubReadingMonthRepository->findNextMonthByClub($club);
        $bookOfNextMonth = $bookOfNextMonthReading?->getBook();
        $nextMonthName = $bookOfNextMonthReading
            ? ucfirst($formatter->format(new DateTime($bookOfNextMonthReading->getMonth() . '-01')))
            : '';

        // --- Mois +2 (lecture & vote) ---
        $plus2MonthStr = (new \DateTimeImmutable('first day of +2 month'))->format('Y-m');
        $clubReadingMonth = $clubReadingMonthRepository->findOneBy([
            'club' => $club,
            'month' => $plus2MonthStr,
        ]);

        $bookOfNextNextMonth = $clubReadingMonth?->getBook();
        $nextNextMonthName = $clubReadingMonth
            ? ucfirst($formatter->format(new DateTime($clubReadingMonth->getMonth() . '-01')))
            : '';

        // --- Derniers avis ---
        $lastReviews = $bookOfMonthReading instanceof ClubReadingMonth
            ? $clubReviewRepository->findLastReviewsByMonth($bookOfMonthReading, 3)
            : [];

        // --- Vérifier si l’utilisateur a déjà laissé un avis ce mois ---
        $userHasReviewedMonth = false;
        if ($bookOfMonthReading instanceof ClubReadingMonth && $user instanceof Utilisateur) {
            $existingReview = $clubReviewRepository->findByUserAndMonth($userId, $bookOfMonthReading->getId());
            $userHasReviewedMonth = $existingReview !== null;
        }

        // --- Propositions récentes ---
        $recentBookProposals = $bookProposalRepository->findRecentBooksByClub($club->getId());

        // --- Votes ---
        $userHasProposed = false;
        $userHasVoted = false;
        $userCanVote = false;
        $leadingProposals = [];
        $maxVotes = -1;

        if ($clubReadingMonth instanceof ClubReadingMonth && $user instanceof Utilisateur) {
            foreach ($clubReadingMonth->getBookProposals() as $proposal) {
                // Déterminer leader
                $voteCount = count($proposal->getVotes());
                if ($voteCount > $maxVotes) {
                    $maxVotes = $voteCount;
                    $leadingProposals = [$proposal->getId()];
                } elseif ($voteCount === $maxVotes) {
                    $leadingProposals[] = $proposal->getId();
                }

                // Vérifier si l’utilisateur a proposé
                if ($proposal->getProposer()?->getId() === $userId) {
                    $userHasProposed = true;
                }
            }

            // Vérifier si l’utilisateur a voté
            foreach ($clubReadingMonth->getVotes() as $vote) {
                if ($vote->getUtilisateur()?->getId() === $userId) {
                    $userHasVoted = true;
                    break;
                }
            }

            $userCanVote = $userHasProposed && !$userHasVoted;
        }

        // --- Signalement ---
        $hasReportedClub = $user ? $reportRepository->findOneBy([
            'author' => $user,
            'reportedClub' => $club,
        ]) : null;

        // --- Créateur et participation ---
        $isCreator = $user instanceof Utilisateur && $club->getCreator()?->getId() === $userId;
        $isParticipant = $isCreator || ($user instanceof Utilisateur && $club->getMembres()->exists(fn($i, $m) => $m->getId() === $userId));

        return $this->render('club/club_show.html.twig', [
            'club' => $club,
            'bookOfMonth' => $bookOfMonth,
            'bookOfNextMonth' => $bookOfNextMonth,
            'currentMonthName' => $currentMonthName,
            'bookOfMonthReading' => $bookOfMonthReading,
            'nextMonthName' => $nextMonthName,
            'nextNextMonthName' => $nextNextMonthName,
            'bookOfNextNextMonth' => $bookOfNextNextMonth,
            'lastReviews' => $lastReviews,
            'clubReadingMonth' => $clubReadingMonth,
            'userHasReviewedMonth' => $userHasReviewedMonth,
            'recentBookProposals' => $recentBookProposals,
            'userHasProposed' => $userHasProposed,
            'userHasVoted' => $userHasVoted,
            'userCanVote' => $userCanVote,
            'leadingProposals' => $leadingProposals,
            'hasReportedClub' => $hasReportedClub,
            'isCreator' => $isCreator,
            'isParticipant' => $isParticipant,
        ]);
    }

    #[Route('/club/{id}/participer', name: 'club_participer', methods: ['GET','POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function participer(Club $club, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('participer_club_' . $club->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if (!$club->getMembres()->contains($user)) {
                $club->addMembre($user);
                $em->flush();
                $this->addFlash('success', 'Vous participez maintenant à ce club !');
            }
        }

        return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
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
    #[IsGranted('ROLE_USER')]
    public function proposerLivre(Request $request, int $clubId, string $month, EntityManagerInterface $em, BookRepository $bookRepo, ClubRepository $clubRepo): Response {

        /** @var Utilisateur $user */
        $user = $this->getUser();


        $club = $clubRepo->find($clubId);
        if (!$club) throw $this->createNotFoundException('Club non trouvé');
        if (!$club->getMembres()->contains($user) && $club->getCreator() !== $user) {
            throw $this->createAccessDeniedException('Vous devez être membre du club.');
        }

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
            $monthNormalized = $month;

            if (!$this->isCsrfTokenValid(
                'propose_book_' . $club->getId() . '_' . $monthNormalized,
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Token CSRF invalide');
            }

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

        if (!$this->isCsrfTokenValid('vote_' . $clubId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
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
    #[IsGranted('ROLE_USER')]
    public function addReview(
        Request $request,
        EntityManagerInterface $em,
        Security $security,
        ClubReadingMonthRepository $monthRepo,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {

        $user = $security->getUser();

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // Vérification CSRF
        $token = new CsrfToken('review', $data['_token'] ?? '');

        if (!$csrfTokenManager->isTokenValid($token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

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

    #[Route('/quitter/{id}', name: 'club_quitter', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function quitterClub(int $id, Request $request, EntityManagerInterface $em): Response 
    {
        $user = $this->getUser();

        $club = $em->getRepository(Club::class)->find($id);

        if (!$club) {
            throw $this->createNotFoundException('Club non trouvé');
        }

        if (!$this->isCsrfTokenValid('quit_club_' . $club->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }

        if ($club->getCreator() === $user) {
            $this->addFlash('danger', 'Le créateur ne peut pas quitter son club.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        $club->removeMembre($user);
        $em->flush();

        $this->addFlash('success', 'Vous avez quitté le club.');

        return $this->redirectToRoute('app_profil_clubs', ['id' => $club->getId()]);
    }

    #[Route('/club/supprimer/{id}', name: 'club_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteClub(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $club = $em->getRepository(Club::class)->find($id);

        if (!$club) {
            return $this->json(['success' => false, 'error' => 'Club non trouvé'], 404);
        }

        // Vérifier que c'est le créateur qui supprime
        $user = $this->getUser();
        if ($club->getCreator() !== $user) {
            return $this->json(['success' => false, 'error' => 'Vous n’êtes pas autorisé à supprimer ce club'], 403);
        }

        // CSRF
        $csrfToken = $request->request->get('_token');
        if (!$csrfToken || !$this->isCsrfTokenValid('delete_club_' . $club->getId(), $csrfToken)) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        // --- Envoi du mail aux membres avant suppression ---
        $recipients = $club->getMembres()->toArray();
        if ($club->getCreator() && $club->getCreator()->getEmail()) {
            $recipients[] = $club->getCreator(); // inclure le créateur
        }

        foreach ($recipients as $recipient) {
            if (!$recipient->getEmail()) {
                continue;
            }
            $email = (new TemplatedEmail())
                ->from('noreply@storylia.com')
                ->to($recipient->getEmail())
                ->subject('Le club "' . $club->getName() . '" a été supprimé')
                ->htmlTemplate('email/club_supprime.html.twig')
                ->context([
                    'club' => $club,
                    'user' => $recipient,
                ]);

            $mailer->send($email);
        }

        // --- Supprimer le club ---
        $em->remove($club);
        $em->flush();

        return $this->json([
            'success' => true,
            'clubId' => $club->getId(),
        ]);
    }
}
