<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\ReadingHistory;
use App\Entity\Bookshelf;
use App\Repository\ReadingStatusRepository;
use App\Repository\UtilisateurRepository;
use App\Form\ProfilType;
use App\Repository\BookRepository;
use App\Repository\ClubRepository;
use App\Repository\ReadingHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(UtilisateurRepository $userRepo, BookRepository $bookRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $currentUser = $utilisateur;
        $isSelf = true;


        // Mapping des préférences
        $preferenceLabels = [
            'scifi' => 'Science-Fiction',
            'fantastique' => 'Fantastique',
            'fantasy' => 'Fantasy',
            'dystopie' => 'Dystopie',
            'steampunk' => 'Steampunk',
            'policier' => 'Policier',
            'thriller' => 'Thriller',
            'espionnage' => 'Espionnage',
            'horreur' => 'Horreur',
            'aventure' => 'Aventure',
            'young_adult' => 'Young Adult',
            'romance' => 'Romance',
            'erotique' => 'Érotique',
            'chicklit' => 'Chick-lit',
            'essai' => 'Essai',
            'biographie' => 'Biographie',
            'philosophie' => 'Philosophie',
            'historique' => 'Historique',
            'science' => 'Science',
            'sociologie' => 'Sociologie',
            'poesie' => 'Poésie',
            'theatre' => 'Théâtre',
            'conte_legend' => 'Contes et légendes',
            'mythologie' => 'Mythologie',
            'graphic_novel' => 'Roman Graphique',
            'bd' => 'Bande dessinée',
            'manga' => 'Manga',
        ];

        // Récupérer les coups de coeur
        $coupsDeCoeur = $utilisateur->getBookshelves()
                            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'coup_de_coeur');
        
        // Lecture en cours (statut = 'en_train_de_lire')
        $lectureEnCours = $utilisateur->getBookshelves()
            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'en_train_de_lire')
            ->first(); // retourne le premier élément ou false

        if (!$lectureEnCours) {
            $lectureEnCours = null;
        }

        // Récupérer l'historique pour les dernières lectures
        $lastRead = $em->getRepository(ReadingHistory::class)
        ->createQueryBuilder('rh')
        ->where('rh.utilisateur = :user')
        ->andWhere('rh.readingDate IS NOT NULL')
        ->orderBy('rh.readingDate', 'DESC')
        ->setParameter('user', $utilisateur)
        ->setMaxResults(20)
        ->getQuery()
        ->getResult();

        $uniqueLastRead = [];
        foreach ($lastRead as $rh) {
            if (!isset($uniqueLastRead[$rh->getBook()->getId()])) {
                $uniqueLastRead[$rh->getBook()->getId()] = $rh;
            }
        }
        $lastRead = array_values($uniqueLastRead);
        $lastRead = array_slice($lastRead, 0, 20);


        return $this->render('profil/profil.html.twig', [
            'coupsDeCoeur' => $coupsDeCoeur,
            'lectureEnCours' => $lectureEnCours,
            'lastRead' => $lastRead,
            'preferenceLabels' => $preferenceLabels,
            'utilisateur' => $utilisateur,
            'currentUser' => $this->getUser(),
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/profil/lecture/{id}/pages', name: 'app_update_pages_read', methods: ['POST'])]
    public function updatePagesRead(Request $request, Bookshelf $bookshelf, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pagesRead = (int) $request->request->get('pagesRead', 0);
        $bookshelf->setPagesRead($pagesRead);

        if (!$this->isCsrfTokenValid('update_pages_read'.$bookshelf->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF');
        }

        $em->persist($bookshelf);
        $em->flush();

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/modifier', name: 'app_profil_edit')]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UtilisateurRepository $utilisateurRepository ): Response 
    {
        // Empêche l'accès si non connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        // Création du formulaire
        $form = $this->createForm(ProfilType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --- Vérification du pseudo unique ---
            $nouveauPseudo = $form->get('pseudo')->getData();
            if ($nouveauPseudo !== $utilisateur->getPseudo()) {
                $exist = $utilisateurRepository->findOneBy(['pseudo' => $nouveauPseudo]);
                if ($exist) {
                    $form->get('pseudo')->addError(new FormError('Ce pseudo est déjà utilisé.'));
                    return $this->render('profil/modifier.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
                $utilisateur->setPseudo($nouveauPseudo);
            }

            // --- Hash du mot de passe si renseigné ---
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                // RepeatedType renvoie directement la valeur string si remplie
                $hashedPassword = $passwordHasher->hashPassword($utilisateur, $plainPassword);
                $utilisateur->setPassword($hashedPassword);
            }

            // --- Upload de la photo de profil ---
            $pictureFile = $form->get('profilePicture')->getData();
            if ($pictureFile) {
                // Supprimer l'ancien fichier si différent du default
                if ($utilisateur->getProfilePicture() && $utilisateur->getProfilePicture() !== 'default.png') {
                    $oldFile = $this->getParameter('profiles_directory') . '/' . $utilisateur->getProfilePicture();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $newFileName = uniqid() . '.' . $pictureFile->guessExtension();
                $pictureFile->move(
                    $this->getParameter('profiles_directory'),
                    $newFileName
                );
                $utilisateur->setProfilePicture($newFileName);
            }

            // --- Mise à jour des préférences ---
            $preferences = $form->get('preferences')->getData();
            $utilisateur->setPreferences($preferences);

            // --- Enregistrement en base ---
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès');

            // --- Redirection vers la page profil ---
            return $this->redirectToRoute('app_profil');
        }

        // Affichage du formulaire avec erreurs éventuelles
        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profil/bibliotheque', name: 'app_profil_bibliotheque')]
    public function bibliotheque(ReadingStatusRepository $readingStatusRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $currentUser = $utilisateur;
        $isSelf = true;

        // Récupère tous les livres liés à cet utilisateur dans Bookshelf
        $bookshelves = $utilisateur->getBookshelves();

        // Trie par catégorie
        $categories = [
            'en_train_de_lire' => [],
            'coup_de_coeur' => [],
            'adore' => [],
            'apprecie' => [],
            'mitige' => [],
            'pas_aime' => [],
            'lu_aussi' => [],
            'pal' => [],
            'envies' => [],
        ];

        /** @var \App\Entity\Bookshelf[] $bookshelves */
        foreach($bookshelves as $bookshelf) {
            $status = $bookshelf->getReadingStatus()?->getLabel();
            $book = $bookshelf->getBook(); // récupère le livre associé

            if (!$status || !array_key_exists($status, $categories)) {
                $status = 'envies'; // catégorie par défaut
            }

            $categories[$status][] = $bookshelf; // on passe le Bookshelf, pas juste le Book
        }

        // Récupère tous les statuts de lecture pour le modal
        $readingStatuses = $readingStatusRepository->findAll();

        return $this->render('profil/bibliotheque.html.twig', [
            'categories' => $categories,
            'readingStatuses' => $readingStatuses,
            'utilisateur' => $utilisateur,
            'currentUser' => $this->getUser(),
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/profil/clubs', name:'app_profil_clubs')]
    public function clubs(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $currentUser = $utilisateur;
        $isSelf = true;

        // Récupère les clubs créés par l'utilisateur
        $clubsCrees = $utilisateur->getClubsCrees();

        // Récupère les clubs dont l'utilisateur est membre
        $clubsMembre = $utilisateur->getClubsMembre();

        return $this->render('profil/clubs.html.twig',[
            'clubsCrees' => $clubsCrees,
            'clubsMembre' => $clubsMembre,
            'utilisateur' => $utilisateur,
            'currentUser' => $this->getUser(),
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/profil/historique', name: 'app_profil_historique')]
    public function historique(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $currentUser = $utilisateur;
        $isSelf = true;

        $historique = []; // tableau regroupant les livres lus

        // Traduction des mois en français
        $moisFrancais = [
            'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
            'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
            'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
            'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
        ];

        foreach ($utilisateur->getReadingHistory() as $reading) {
            $date = $reading->getReadingDate();
            if (!$date) {
                continue; // ignore si pas de date
            }

            // S'assurer que c'est un objet DateTime
            if (!$date instanceof \DateTimeInterface) {
                $date = new \DateTime($date);
            }

            $year = $date->format('Y');
            $month = $moisFrancais[$date->format('F')];

            // Evite les doublons en utilisant l'ID du livre comme clé
            $historique[$year][$month][$reading->getBook()->getId()] = $reading;
        }

        // Trier les années par ordre décroissant
        krsort($historique);

        return $this->render('profil/historique.html.twig', [
            'utilisateur' => $utilisateur,
            'historique' => $historique,
            'currentUser' => $this->getUser(),
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/utilisateur/{id}', name: 'app_utilisateur_profil')]
    public function publicProfil(Utilisateur $utilisateur, EntityManagerInterface $em): Response 
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur $currentUser */
        $currentUser = $this->getUser();
        $isSelf = ($currentUser === $utilisateur);

        // Mapping des préférences
        $preferenceLabels = [
            'scifi' => 'Science-Fiction',
            'fantastique' => 'Fantastique',
            'fantasy' => 'Fantasy',
            'dystopie' => 'Dystopie',
            'steampunk' => 'Steampunk',
            'policier' => 'Policier',
            'thriller' => 'Thriller',
            'espionnage' => 'Espionnage',
            'horreur' => 'Horreur',
            'aventure' => 'Aventure',
            'young_adult' => 'Young Adult',
            'romance' => 'Romance',
            'erotique' => 'Érotique',
            'chicklit' => 'Chick-lit',
            'essai' => 'Essai',
            'biographie' => 'Biographie',
            'philosophie' => 'Philosophie',
            'historique' => 'Historique',
            'science' => 'Science',
            'sociologie' => 'Sociologie',
            'poesie' => 'Poésie',
            'theatre' => 'Théâtre',
            'conte_legend' => 'Contes et légendes',
            'mythologie' => 'Mythologie',
            'graphic_novel' => 'Roman Graphique',
            'bd' => 'Bande dessinée',
            'manga' => 'Manga',
        ];

        // Coups de cœur
        $coupsDeCoeur = $utilisateur->getBookshelves()
            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'coup_de_coeur');

        // Lecture en cours
        $lectureEnCours = $utilisateur->getBookshelves()
            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'en_train_de_lire')
            ->first() ?: null;

        // Dernières lectures
        $lastRead = $em->getRepository(ReadingHistory::class)
            ->createQueryBuilder('rh')
            ->where('rh.utilisateur = :user')
            ->andWhere('rh.readingDate IS NOT NULL')
            ->orderBy('rh.readingDate', 'DESC')
            ->setParameter('user', $utilisateur)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $uniqueLastRead = [];
        foreach ($lastRead as $rh) {
            $uniqueLastRead[$rh->getBook()->getId()] = $rh;
        }

        $lastRead = array_slice(array_values($uniqueLastRead), 0, 20);

        return $this->render('profil/profil.html.twig', [
            'utilisateur' => $utilisateur,
            'currentUser' => $currentUser,
            'isSelf' => $isSelf,
            'coupsDeCoeur' => $coupsDeCoeur,
            'lectureEnCours' => $lectureEnCours,
            'lastRead' => $lastRead,
            'preferenceLabels' => $preferenceLabels,
        ]);
    }

    #[Route('/utilisateur/{id}/bibliotheque', name: 'app_utilisateur_bibliotheque')]
    public function publicBibliotheque(Utilisateur $utilisateur, ReadingStatusRepository $readingStatusRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $currentUser = $this->getUser();
        $isSelf = ($currentUser === $utilisateur);

        // Récupérer les livres et catégories comme pour la bibliothèque normale
        $bookshelves = $utilisateur->getBookshelves();

        $categories = [
            'en_train_de_lire' => [],
            'coup_de_coeur' => [],
            'adore' => [],
            'apprecie' => [],
            'mitige' => [],
            'pas_aime' => [],
            'lu_aussi' => [],
            'pal' => [],
            'envies' => [],
        ];

        foreach($bookshelves as $bookshelf) {
            $status = $bookshelf->getReadingStatus()?->getLabel();
            if (!$status || !array_key_exists($status, $categories)) $status = 'envies';
            $categories[$status][] = $bookshelf;
        }

        $readingStatuses = $readingStatusRepository->findAll();

        return $this->render('profil/bibliotheque.html.twig', [
            'utilisateur' => $utilisateur,
            'categories' => $categories,
            'readingStatuses' => $readingStatuses,
            'currentUser' => $currentUser,
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/utilisateur/{id}/clubs', name: 'app_utilisateur_clubs')]
    public function publicClubs(Utilisateur $utilisateur, ClubRepository $clubRepository): Response
    {
        $currentUser = $this->getUser();
        $isSelf = ($currentUser === $utilisateur);

        $clubsCrees = $clubRepository->findBy(['creator' => $utilisateur]);
        $clubsMembre = $clubRepository->findByMembre($utilisateur);

        return $this->render('profil/clubs.html.twig', [
            'utilisateur' => $utilisateur,
            'clubsCrees' => $clubsCrees,
            'clubsMembre' => $clubsMembre,
            'currentUser' => $currentUser,
            'isSelf' => $isSelf,
        ]);
    }

    #[Route('/utilisateur/{id}/historique', name: 'app_utilisateur_historique')]
    public function utilisateurHistorique(Utilisateur $utilisateur, ReadingHistoryRepository $historiqueRepository): Response {
        $currentUser = $this->getUser();
        $isSelf = ($currentUser === $utilisateur);

        /** @var Utilisateur $utilisateur */
        $historique = $historiqueRepository->getHistoriqueParUtilisateur($utilisateur);

        return $this->render('profil/historique.html.twig', [
            'utilisateur' => $utilisateur,
            'historique' => $historique,
            'currentUser' => $currentUser,
            'isSelf' => $isSelf,
        ]);
    }
}