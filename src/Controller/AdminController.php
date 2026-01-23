<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\BookApiService;
use App\Service\BookCreationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Form\EmployeType;
use App\Entity\Role;
use App\Entity\Club;
use App\Entity\Utilisateur;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Book;
use App\Entity\Author;
use App\Repository\UtilisateurRepository;
use App\Repository\BookRepository;
use App\Repository\ClubRepository;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(BookRepository $bookRepository, ClubRepository $clubRepository, UtilisateurRepository $userRepository): Response {
        // Livres totaux
        $totalBooks = $bookRepository->count([]);

        // Clubs créés
        $totalClubs = $clubRepository->count([]);

        // Utilisateurs "classiques" (pas admin ni employés)
        $totalUsers = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->join('u.role', 'r')
            ->where('r.label = :label')
            ->setParameter('label', 'ROLE_USER')
            ->getQuery()
            ->getSingleScalarResult();

        // Participants aux clubs (chaque utilisateur compte 1 seule fois)
        $allUsersInClubs = $clubRepository->createQueryBuilder('c')
            ->select('m.id')
            ->join('c.membres', 'm')
            ->getQuery()
            ->getScalarResult();

        $userIds = array_unique(array_column($allUsersInClubs, 'id'));
        $totalParticipants = count($userIds);

        // Livres créés par les utilisateurs
        $totalUserBooks = $bookRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.utilisateur IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'totalBooks' => $totalBooks,
            'totalClubs' => $totalClubs,
            'totalUsers' => $totalUsers,
            'totalParticipants' => $totalParticipants,
            'totalUserBooks' => $totalUserBooks,
        ]);
    }

    #[Route('/add-book-api', name: 'add_book_api', methods: ['GET'])]
    public function addBookFromApi(Request $request, BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): JsonResponse
    {
        // Récupération des paramètres
        $title = $request->query->get('title');
        $author = $request->query->get('author');

        // Il ne se passe rien s'il n'y a pas de titre
        if(!$title) {
            return $this->json([
                'success' => false,
                'message' => 'Titre manquant'
            ]);
        }

        // Vérifier si le livre existe déjà
        $existingBook = $em->getRepository(Book::class)->findOneBy(['title' => $title]);

        if ($existingBook) {
        return $this->json([
            'success' => false,
            'message' => 'Le livre existe déjà en base.'
        ]);
    }


        // Appel du service BookApiService pour récupérer le livre dans l'API
        $bookData = $apiService->fetchBook($title, $author);

        // Si aucun livre trouvé = erreur JSON
        if(!$bookData) {
            return $this->json([
                'success' => false,
                'message' => 'Pas de nouveau livre dans l\'API.'
            ]);
        }

        // Création du livre en BDD à partir des données récupérées
        $book = $creationService->createOrUpdateBookFromApi($bookData);

        // On renvoie une réponse JSON
        return $this->json([
            'success' => true,
            'book' => [
                'title' => $book->getTitle(),
                'author' => $book->getAuthor()->getName(),
                'cover' => $book->getCover() ?: '/images/default_cover.jpg',
                'summary' => $book->getSummary(),
                'publicationDate' => $book->getPublicationDate()?->format('Y-m-d'),
                'edition' => $book->getPublishers(),
                'genre' => $book->getGenres()
            ]
        ]);
    }

    #[Route('/import-books', name: 'import_books', methods: ['GET'])]
    public function importBooks(Request $request, BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): Response
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $batchSize = 50;
        $totalImported = 0;

        // --- Récupère tous les livres existants avec title + author name ---
        $existingBooks = $em->getRepository(Book::class)
                            ->createQueryBuilder('b')
                            ->select('b.title, IDENTITY(b.author) AS author_id')
                            ->getQuery()
                            ->getArrayResult();

        // --- Crée un tableau pour vérifier rapidement l’existence ---
        $existingMap = [];
        foreach ($existingBooks as $b) {
            $author = $em->getRepository(Author::class)->find($b['author_id']);
            $authorName = $author ? strtolower(trim($author->getName())) : '';
            $titleKey = strtolower(trim($b['title']));
            $key = $titleKey . '||' . $authorName; // uniquement titre + auteur
            $existingMap[$key] = true;
        }

        $subjects = $apiService->fetchAllSubjects();

        foreach ($subjects as $subject) {
            $startIndex = 0;

            while (true) {
                $booksData = $apiService->fetchBookList($startIndex, 40, [$subject]);
                if (empty($booksData)) break;

                foreach ($booksData as $bookData) {
                    $title = trim($bookData['title'] ?? '');
                    $authorName = strtolower(trim($bookData['author'] ?? 'Auteur inconnu'));
                    $key = strtolower($title) . '||' . $authorName;

                    // Si le livre existe déjà, on saute
                    if (isset($existingMap[$key])) continue;

                    $book = $creationService->createOrUpdateBookFromApi($bookData);
                    if ($book) {
                        $em->persist($book);
                        $existingMap[$key] = true; // marque comme existant
                        $totalImported++;
                    }

                    // Flush par batch
                    if ($totalImported % $batchSize === 0) {
                        $em->flush();
                        $em->clear();
                    }
                }

                $startIndex += 40;
            }
        }

        // Flush final
        $em->flush();
        $em->clear();

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => "$totalImported livre(s) importé(s)."
            ]);
        }

        $this->addFlash('success', "$totalImported livre(s) importé(s).");
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/update-books', name: 'update_books', methods: ['GET'])]
    public function updateBooks(Request $request, BookApiService $apiService, BookCreationService $creationService, EntityManagerInterface $em): Response
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $batchSize = 50;
        $count = 0;

        $qb = $em->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');

        $iterableBooks = $qb->getQuery()->toIterable();

        foreach ($iterableBooks as $book) {
            $author = $book->getAuthor();
            if (!$author) continue;

            $bookData = $apiService->fetchBook($book->getTitle(), $author->getName());
            if (!$bookData) continue;

            $creationService->updateBookFields($book, $bookData);

            $em->persist($book);
            $count++;

            if ($count % $batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => "$count livre(s) mis à jour."
            ]);
        }

        $this->addFlash('success', "$count livre(s) mis à jour.");
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/employes', name: 'admin_employes')]
    #[IsGranted('ROLE_ADMIN')]
    public function employes(UtilisateurRepository $userRepository): Response
    {
        $employes = $userRepository->findByRoleLibelle('ROLE_EMPLOYE');

        return $this->render('admin/employes.html.twig', [
            'employes' => $employes
        ]);
    }

    #[Route('/employes/creer', name: 'admin_creer_employe')]
    #[IsGranted('ROLE_ADMIN')]
    public function creerEmploye(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $employe = new Utilisateur();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier ou créer le rôle EMPLOYE
            $role = $em->getRepository(Role::class)->findOneBy(['label' => 'ROLE_EMPLOYE']);
            if (!$role) {
                $role = new Role();
                $role->setLabel('ROLE_EMPLOYE'); // <-- bien mettre ROLE_ devant
                $em->persist($role);
                $em->flush(); // pour être sûr que l'id est généré
            }

            $employe->setRole($role);
            $employe->setIsVerified(true);

            // Récupérer le mot de passe depuis le formulaire (non mappé)
            $plainPassword = $form->get('password')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est obligatoire.');
                return $this->render('admin/nouveau_employe.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hashage sécurisé
            $hashedPassword = $hasher->hashPassword($employe, $plainPassword);
            $employe->setPassword($hashedPassword);

            $em->persist($employe);
            $em->flush();

            $this->addFlash('success', 'Employé créé avec succès !');

            return $this->redirectToRoute('admin_employes');
        }

        return $this->render('admin/new_employe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/employe/supprimer/{id}', name: 'admin_supprimer_employe', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimerEmploye(int $id, Request $request, UtilisateurRepository $userRepository, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $user = $userRepository->find($id);

        if (!$user || $user->getRole()->getLabel() !== 'EMPLOYE') {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('delete-employe-' . $id, $submittedToken))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Employé supprimé avec succès.');
        return $this->redirectToRoute('admin_employes');
    }

    #[Route('/utilisateurs', name: 'admin_gestion_utilisateurs')]
    public function gestionUtilisateurs(EntityManagerInterface $em, UtilisateurRepository $userRepository): Response {
        $users = $userRepository->findByRoleLibelle('USER');

        $reports = $em->getRepository(Report::class)->findAll();

        $reportsByUser = [];
        foreach ($reports as $report) {
            $reportedBy = $report->getAuthor();
            $reportedUser = $report->getReported();

            if (!$reportedBy || !$reportedUser) continue;
            if ($reportedBy->getRole()->getLabel() !== 'EMPLOYE') continue;

            $userId = $reportedUser->getId();
            $reportsByUser[$userId][] = $report;
        }

        return $this->render('admin/gestion_utilisateurs.html.twig', [
            'users' => $users,
            'reportsByUser' => $reportsByUser,
        ]);
    }

    #[Route('/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function suspendreUtilisateur(int $id, Request $request, UtilisateurRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $csrfToken = $data['_token'] ?? null;

        if (!$csrfToken || !$this->isCsrfTokenValid('suspend_user_' . $user->getId(), $csrfToken)) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        $reason = $data['reason'] ?? '';
        if ($reason === 'autres') $reason = $data['otherReason'] ?? '';

        $user->setStatus(Utilisateur::STATUS_SUSPENDU);
        $user->setSuspendReason($reason);
        $em->flush();

        if ($user->getEmail()) {
            $email = (new TemplatedEmail())
                ->from('storylia@gmail.com')
                ->to($user->getEmail())
                ->subject('Votre compte a été suspendu')
                ->htmlTemplate('emails/suspension_utilisateur.html.twig')
                ->context(['user' => $user, 'reason' => $reason]);
            $mailer->send($email);
        }

        $unsuspendToken = $this->container->get('security.csrf.token_manager')
            ->getToken('unsuspend_user_' . $user->getId())
            ->getValue();

        return $this->json([
            'success' => true,
            'userId' => $user->getId(),
            'unsuspendToken' => $unsuspendToken
        ]);
    }

    #[Route('/utilisateur/unsuspendre/{id}', name: 'admin_unsuspendre_utilisateur', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unsuspendUser(int $id, Request $request, UtilisateurRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer, CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $csrfToken = $data['_token'] ?? null;

        if (!$csrfToken || !$this->isCsrfTokenValid('unsuspend_user_' . $user->getId(), $csrfToken)) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        $user->setStatus(Utilisateur::STATUS_ACTIF);
        $user->setSuspendReason(null);
        $em->flush();

        if ($user->getEmail()) {
            $email = (new TemplatedEmail())
                ->from('storylia@gmail.com')
                ->to($user->getEmail())
                ->subject('Votre compte a été réactivé')
                ->htmlTemplate('emails/reactivation_utilisateur.html.twig')
                ->context(['user' => $user]);
            $mailer->send($email);
        }

        $suspendToken = $csrfTokenManager->getToken('suspend_user_' . $user->getId())->getValue();

        return $this->json([
            'success' => true,
            'userId' => $user->getId(),
            'suspendToken' => $suspendToken
        ]);
    }

    #[Route('/clubs/signales', name: 'clubs_signales')]
    public function gestionClubsSignales(EntityManagerInterface $em): Response
    {
        // On récupère les reports avec le status "transmis_admin"
        $reports = $em->getRepository(Report::class)->findBy([
            'status' => 'transmis_admin',
        ]);

        // On garde seulement ceux qui concernent un club
        $reportsClubs = [];

        foreach ($reports as $report) {
            if ($report->getReportedClub() !== null) {
                $reportsClubs[] = $report;
            }
        }

        return $this->render('admin/gestion_clubs_signales.html.twig', [
            'reports' => $reportsClubs,
        ]);
    }

    #[Route('/club/suspendre/{id}', name: 'club_suspendre', methods: ['POST'])]
    public function suspendreClub(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        // --- Récupération du club ---
        $club = $em->getRepository(Club::class)->find($id);
        if (!$club) {
            return $this->json(['success' => false, 'error' => 'Club non trouvé'], 404);
        }

        // --- Récupération du report ---
        $reportId = $data['report_id'] ?? null;
        if (!$reportId) {
            return $this->json(['success' => false, 'error' => 'Report non trouvé'], 400);
        }
        $report = $em->getRepository(Report::class)->find($reportId);
        if (!$report) {
            return $this->json(['success' => false, 'error' => 'Report non trouvé'], 404);
        }

        // --- CSRF check ---
        $csrfToken = $data['_token'] ?? null;
        if (!$csrfToken || !$this->isCsrfTokenValid('suspend_club_' . $club->getId(), $csrfToken)) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        // --- Traitement du motif ---
        $reason = $data['reason'] ?? '';
        if ($reason === 'autres') {
            $reason = $data['otherReason'] ?? 'Signalement employé';
        }

        // --- Mise à jour du club ---
        $club->setStatus(Club::STATUS_INACTIF);
        $club->setSuspendReason($reason);

        // --- Mise à jour du report ---
        $report->setStatus('traite');
        $em->flush();

        // --- Récupération du créateur et des membres ---
        $connection = $em->getConnection();
        $sql = 'SELECT u.id FROM utilisateur u 
                INNER JOIN utilisateur_club uc ON u.id = uc.utilisateur_id 
                WHERE uc.club_id = :clubId';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['clubId' => $club->getId()]);
        $memberIds = $result->fetchAllAssociative();

        $recipients = [];
        if ($club->getCreator()) {
            $recipients[] = $club->getCreator();
        }

        foreach ($memberIds as $row) {
            $user = $em->getRepository(Utilisateur::class)->find($row['id']);
            if ($user && $user->getEmail() && $user !== $club->getCreator()) {
                $recipients[] = $user;
            }
        }

        // --- Envoi des mails ---
        foreach ($recipients as $user) {
            try {
                if ($user === $club->getCreator()) {
                    $template = 'email/club_suspendu_owner.html.twig';
                    $subject = 'Votre club a été suspendu : ' . $club->getName();
                } else {
                    $template = 'email/club_suspendu_member.html.twig';
                    $subject = 'Le club "' . $club->getName() . '" a été suspendu';
                }

                $email = (new TemplatedEmail())
                    ->from('noreply@storylia.com')
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->htmlTemplate($template)
                    ->context([
                        'club' => $club,
                        'user' => $user,
                        'reason' => $reason,
                    ]);

                $mailer->send($email);
                $logger->info('Mail envoyé à : ' . $user->getEmail());
            } catch (\Exception $e) {
                $logger->error('Erreur envoi mail suspendre club (user ' . $user->getId() . ') : ' . $e->getMessage());
            }
        }

        $unsuspendToken = $csrfTokenManager->getToken('unsuspend_club_' . $club->getId())->getValue();

        return $this->json([
            'success' => true,
            'clubId' => $club->getId(),
            'unsuspendToken' => $unsuspendToken
        ]);
    }

    #[Route('/club/unsuspendre/{id}', name: 'club_unsuspendre', methods: ['POST'])]
    public function unsuspendreClub(
        int $id,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager,
        MailerInterface $mailer
    ): JsonResponse
    {
        $club = $em->getRepository(Club::class)->find($id);
        if (!$club) {
            return $this->json(['success' => false, 'error' => 'Club non trouvé'], 404);
        }

        $club->setStatus(Club::STATUS_ACTIF);
        $club->setSuspendReason(null);
        $em->flush();

        // --- Mail commun pour le créateur et les membres ---
        $recipients = [];

        if ($club->getCreator() && $club->getCreator()->getEmail()) {
            $recipients[] = $club->getCreator();
        }

        foreach ($club->getMembres() as $member) {
            if ($member->getEmail()) {
                $recipients[] = $member;
            }
        }

        foreach ($recipients as $user) {
            $email = (new TemplatedEmail())
                ->from('noreply@storylia.com')
                ->to($user->getEmail())
                ->subject('Le club "' . $club->getName() . '" est de nouveau accessible')
                ->htmlTemplate('email/club_reaccessible.html.twig')
                ->context([
                    'club' => $club,
                    'user' => $user,
                ]);

            $mailer->send($email);
        }

        return $this->json([
            'success' => true,
            'clubId' => $club->getId(),
        ]);
    }

        #[Route('/report/ignore/{id}', name: 'report_ignore', methods: ['POST'])]
    public function ignoreReport(int $id, EntityManagerInterface $em): JsonResponse {
        $report = $em->getRepository(Report::class)->find($id);
        if (!$report) return $this->json(['success' => false, 'error' => 'Report non trouvé'], 404);

        $report->setStatus('refuse');
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/clubs/historique', name: 'admin_clubs_historique')]
    public function historiqueClubs(
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Récupération des filtres et tri depuis l'URL
        $clubName = $request->query->get('club', '');
        $sort = $request->query->get('sort', 'date_desc');
        $status = $request->query->get('status', 'traite_refuse'); 

        // Création de la query
        $qb = $em->getRepository(Report::class)->createQueryBuilder('r')
            ->join('r.reportedClub', 'c')
            ->join('r.author', 'a')
            ->addSelect('c, a');

        // Filtre nom de club
        if ($clubName) {
            $qb->andWhere('c.name LIKE :clubName')
                ->setParameter('clubName', "%$clubName%");
        }

        // Filtre statut
        if ($status === 'traite_refuse') {
            $qb->andWhere('r.status IN (:statuses)')
            ->setParameter('statuses', ['traite', 'refuse']);
        } elseif ($status) {
            $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
        }

        // Tri
        switch ($sort) {
            case 'name_asc':
                $qb->orderBy('c.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('c.name', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('r.date', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('r.date', 'DESC');
        }

        $reports = $qb->getQuery()->getResult();

        return $this->render('admin/historique_clubs.html.twig', [
            'reports' => $reports,
            'filters' => [
                'club' => $clubName,
                'sort' => $sort,
                'status' => $status,
            ],
        ]);
    }
}
