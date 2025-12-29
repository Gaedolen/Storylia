<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe')]
#[IsGranted('ROLE_EMPLOYE')] // accès réservé aux employés
class EmployeController extends AbstractController
{
    #[Route('/dashboard', name: 'employe_dashboard')]
    public function dashboard(ReviewRepository $reviewRepo): Response
    {
        // Avis en attente de modération
        $pendingReviews = $reviewRepo->findBy(['status' => 'en_attente']);

        return $this->render('employe/dashboard.html.twig', [
            'pendingReviews' => $pendingReviews,
        ]);
    }
}
