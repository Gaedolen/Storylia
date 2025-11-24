<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\ReadingStatusRepository;
use App\Repository\UtilisateurRepository;
use App\Form\ProfilType;
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
    public function index(UtilisateurRepository $userRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        // Récupérer les coups de coeur
        $coupsDeCoeur = $utilisateur->getBookshelves()
                            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'coup_de_coeur');
        
        // Lecture en cours (statut = 'en_train_de_lire')
        $lectureEnCours = $utilisateur->getBookshelves()
            ->filter(fn($shelf) => $shelf->getReadingStatus()->getLabel() === 'en_train_de_lire')
            ->first(); // retourne le premier élément ou false

        // Si false, mettre null pour Twig
        if (!$lectureEnCours) {
            $lectureEnCours = null;
        }

        return $this->render('profil/profil.html.twig', [
            'utilisateur' => $utilisateur,
            'coupsDeCoeur' => $coupsDeCoeur,
            'lectureEnCours' => $lectureEnCours,
        ]);
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
            'utilisateur' => $utilisateur,
            'readingStatuses' => $readingStatuses,
        ]);
    }

    #[Route('/profil/clubs', name:'app_profil_clubs')]
    public function clubs(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        // Récupère les clubs créés par l'utilisateur
        $clubsCrees = $utilisateur->getClubsCrees();

        // Récupère les clubs dont l'utilisateur est membre
        $clubsMembre = $utilisateur->getClubsMembre();

        return $this->render('profil/clubs.html.twig',[
            'clubsCrees' => $clubsCrees,
            'clubsMembre' => $clubsMembre,
        ]);
    }

    #[Route('/profil/historique', name: 'app_profil_historique')]
    public function historique(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        $historique = []; // regroupe les livres lus

        $moisFrancais = [ // traduction des mois en français
            'January'=>'Janvier','February'=>'Février','March'=>'Mars',
            'April'=>'Avril','May'=>'Mai','June'=>'Juin',
            'July'=>'Juillet','August'=>'Août','September'=>'Septembre',
            'October'=>'Octobre','November'=>'Novembre','December'=>'Décembre'
        ];

        foreach($utilisateur->getReadingHistory() as $reading) { // renvoie tous les livres lus par l'utilisateur
            $date = $reading->getReadingDate(); // On récupère la date de lecture
            if(!$date)continue; // si la date est null, on ignore le livre en passant au suivant

            $year = $date->format('Y'); // extrait l'année
            $month = $moisFrancais[$date->format('F')]; // extrait le mois

            $historique[$year][$month][] = $reading; // Range le tableau dans l'historique
        }

        // Trier par année décroissante
        ksort($historique);

        return $this->render('profil/historique.html.twig', [
            'utilsiateur' => $utilisateur,
            'historique' => $historique,
        ]);
    }
}