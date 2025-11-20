<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(): Response
    {
        // Empêcher l'accès si non connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupération de l'utilisateur connecté
        $utilisateur = $this->getUser();

        return $this->render('profil/profil.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/modifier', name: 'app_profil_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        // Empêche l'accès si non connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        // Création du formulaire basé sur ProfilType
        $form = $this->createForm(ProfilType::class, $utilisateur);
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide, on sauvegarde les modifications en base
        if($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form->get('profilePicture')->getData();
            if ($pictureFile) {
                // Supprimer l'ancien fichier si existant et différent du default
                if ($utilisateur->getProfilePicture() && $utilisateur->getProfilePicture() !== 'default.png') {
                    $oldFile = $this->getParameter('profiles_directory').'/'.$utilisateur->getProfilePicture();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $newFileName = uniqid().'.'.$pictureFile->guessExtension();
                $pictureFile->move(
                    $this->getParameter('profiles_directory'),
                    $newFileName
                );
                $utilisateur->setProfilePicture($newFileName);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès');
            return $this->redirectToRoute('app_profil');
        }
        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profil/bibliotheque', name: 'app_profil_bibliotheque')]
    public function bibliotheque(): Response
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

        /** 
         * @var \App\Entity\Utilisateur $utilisateur
         * @var \App\Entity\Bookshelf[] $bookshelves
         */
        foreach($bookshelves as $bookshelf) {
            $status = $bookshelf->getReadingStatus()?->getLabel();
            $book = $bookshelf->getBook(); // récupère le livre associé

            if (!$status || !array_key_exists($status, $categories)) {
                $status = 'envies'; // catégorie par défaut
            }

            $categories[$status][] = $book;
        }

        return $this->render('profil/bibliotheque.html.twig', [
            'categories' => $categories,
            'utilisateur' => $utilisateur,
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