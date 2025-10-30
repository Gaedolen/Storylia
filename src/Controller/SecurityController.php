<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils)
    {
        // Récupère la dernière erreur d'authentification, s'il y en a une
        $error = $authUtils->getLastAuthenticationError();

        // Récupère le dernier username saisi par l'utilisateur
        $lastUsername = $authUtils->getLastAuthenticationError();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \Exception('Cete méthode ne doit jamais être appelée manuellement !');
    }

}