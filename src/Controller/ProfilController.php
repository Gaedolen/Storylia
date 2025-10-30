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

        return $this->render('profil/bibliotheque.html.twig', [
            'bookshelves' => $bookshelves,
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

        // Récupère tous les livres lus avec la date dans ReadingHistory
        $readingHistory = $utilisateur->getReadingHistory();

        return $this->render('profil/historique.html.twig', [
            'readingHistory' => $readingHistory,
        ]);
    }
}