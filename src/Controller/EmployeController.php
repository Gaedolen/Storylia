<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeController extends AbstractController
{
    #[Route('/dashboard', name: 'employe_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('employe/dashboard.html.twig');
    }

    #[Route('/employe/avis-signales/{id}', name: 'employe_avis_detail')]
    public function avisDetail(int $id, ReviewRepository $reviewRepository, EntityManagerInterface $em): Response
    {
        $review = $reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException("Avis introuvable.");
        }

        // On récupère les reports en cours pour cet avis
        $reports = $review->getReports()->filter(fn($r) => $r->getStatus() === Report::STATUS_EN_COURS);

        return $this->render('employe/avis_detail.html.twig', [
            'review' => $review,
            'reports' => $reports
        ]);
    }
}
