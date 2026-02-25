<?php

namespace App\Controller;

use App\Repository\RoleRepository;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class RegistrationController extends AbstractController
{
    private RoleRepository $roleRepository;
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(RoleRepository $roleRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $utilisateur = new Utilisateur();

        $form = $this->createForm(RegistrationFormType::class, $utilisateur, [
            'mentions_url' => $this->generateUrl('mentions_legales'),
            'confidentialite_url' => $this->generateUrl('politique_confidentialite'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔹 Vérification doublon email
            $existingEmail = $this->entityManager->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $utilisateur->getEmail()]);
            if ($existingEmail) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                return $this->redirectToRoute('register');
            }

            // 🔹 Vérification doublon pseudo
            $existingPseudo = $this->entityManager->getRepository(Utilisateur::class)
                ->findOneBy(['pseudo' => $utilisateur->getPseudo()]);
            if ($existingPseudo) {
                $this->addFlash('error', 'Ce pseudo est déjà pris.');
                return $this->redirectToRoute('register');
            }

            // 🔹 Hashage du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $plainPassword));

            // 🔹 Rôle par défaut
            $roleUser = $this->roleRepository->findOneBy(['label' => 'ROLE_USER']);
            $utilisateur->setRole($roleUser);

            // 🔹 Gestion photo de profil
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();

                // Vérification du type MIME réel
                $mimeType = mime_content_type($profilePictureFile->getPathname());
                if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                    $this->addFlash('error', 'Fichier invalide : seules les images JPG ou PNG sont acceptées.');
                    return $this->redirectToRoute('register');
                }

                try {
                    $profilePictureFile->move(
                        $this->getParameter('profiles_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                    return $this->redirectToRoute('register');
                }

                $utilisateur->setProfilePicture($newFilename);
            } else {
                $utilisateur->setProfilePicture('default.png');
            }

            // 🔹 Sauvegarde en BDD
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}