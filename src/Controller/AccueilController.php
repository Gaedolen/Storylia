<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    #[Route('/accueil', name: 'app_accueil')]
    public function index(): Response
    {
        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('accueil/mentions_legales.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('accueil/politique_confidentialite.html.twig');
    }
}
