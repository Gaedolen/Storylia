<?php

namespace App\Controller;

use App\Repository\RoleRepository;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class RegistrationController extends AbstractController
{
    private RoleRepository $roleRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(RoleRepository $roleRepository, EntityManagerInterface $entityManager)
    {
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $utilisateur = new Utilisateur();

        // Création du formulaire avec les URL
        $form = $this->createForm(RegistrationFormType::class, $utilisateur, [
            'mentions_url' => $this->generateUrl('mentions_legales'),
            'confidentialite_url' => $this->generateUrl('politique_confidentialite'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du MDP
            $plainPassword = $form->get('plainPassword')->getData();
            $utilisateur->setPassword(
                $passwordHasher->hashPassword($utilisateur, $plainPassword)
            );

            // Rôle par défaut
            $roleUser = $this->roleRepository->findOneBy(['label' => 'ROLE_USER']);
            $utilisateur->setRole($roleUser);

            // Gestion de la photo de profil si elle est upload
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $newFileName = uniqid(). '.'.$profilePictureFile->guessExtension(); // Création d'un nom unique pour éviter les collisions avec d'autres fichiers
                $profilePictureFile->move($this->getParameter('profiles_directory'), $newFileName);
                $utilisateur->setProfilePicture($newFileName);
            } else {
                $utilisateur->setProfilePicture('default.png');
            }

            // Sauvegarde en BDD
            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès !');
            return $this->redirectToRoute('login');
        }
        return $this->render('registration/register.html.twig',[
            'registrationForm' => $form->createView(),
        ]);
    }
}