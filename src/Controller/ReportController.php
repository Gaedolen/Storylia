<?php

namespace App\Controller;

use App\Entity\Report;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/report', name: 'report_')]
class ReportController extends AbstractController
{
    #[Route('/review', name: 'review', methods: ['POST'])]
    public function reportReview(Request $request, ReviewRepository $reviewRepo, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['success' => false], 401);
        }

        $csrfToken = $request->headers->get('X-CSRF-TOKEN');

        if (
            !$csrfToken ||
            !$csrfTokenManager->isTokenValid(
                new CsrfToken('report_review', $csrfToken)
            )
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'CSRF invalide.'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (
            !$data ||
            empty($data['reviewId']) ||
            empty($data['reason']) ||
            empty($data['message'])
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides.'
            ], 400);
        }

        $review = $reviewRepo->find($data['reviewId']);

        if (!$review) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Avis introuvable.'
            ], 404);
        }

        $report = new Report();
        $report->setAuthor($user);
        $report->setReported($review->getUtilisateur());
        $report->setReview($review);
        $report->setReason($data['reason']);
        $report->setMessage($data['message']);

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
