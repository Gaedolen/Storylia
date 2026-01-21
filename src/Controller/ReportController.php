<?php

namespace App\Controller;

use App\Entity\Report;
use App\Entity\Book;
use App\Entity\Utilisateur;
use App\Entity\Club;
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

    #[Route('/book', name: 'book', methods: ['POST'])]
    public function reportBook(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['success' => false], 401);

        $csrfToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$csrfToken || !$csrfTokenManager->isTokenValid(new CsrfToken('report_book', $csrfToken))) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF invalide.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['book_id']) || empty($data['reason'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $book = $em->getRepository(Book::class)->find($data['book_id']);
        if (!$book) {
            return new JsonResponse(['success' => false, 'message' => 'Livre introuvable.'], 404);
        }

        $report = new Report();
        $report->setAuthor($user);
        $report->setReportedBook($book);
        $report->setReason($data['reason']);
        $report->setMessage($data['message'] ?? '');
        $report->setStatus('en_cours');

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/user/{id}', name: 'user', methods: ['POST'])]
    public function reportUser(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $csrfToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$csrfToken || !$csrfTokenManager->isTokenValid(new CsrfToken('report_user', $csrfToken))) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF invalide.'], 403);
        }

        // Eviter le double signalement
        $existingReport = $em->getRepository(Report::class)->findOneBy([
            'author' => $user,
            'reported' => $utilisateur,
        ]);

        if ($existingReport) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà signalé cet utilisateur.'
            ], 409);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['reason']) || empty($data['message'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $report = new Report();
        $report->setAuthor($user);
        $report->setReported($utilisateur);
        $report->setReason($data['reason']);
        $report->setMessage($data['message']);
        $report->setStatus('en_cours');

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Utilisateur signalé.']);
    }

    #[Route('/club/{id}', name: 'club', methods: ['POST'])]
    public function reportClub(Club $club, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse 
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);

        // On récupère le token soit depuis JSON soit depuis header
        $csrfToken = $data['_token'] ?? $request->headers->get('X-CSRF-TOKEN');
        $reason = $data['reason'] ?? null;
        $message = $data['message'] ?? null;

        // Vérification CSRF
        if (!$csrfToken || !$csrfTokenManager->isTokenValid(new CsrfToken('report_club', $csrfToken))) {
            return new JsonResponse(['success' => false, 'message' => 'CSRF invalide.']);
        }

        // Vérifier que l'utilisateur est participant mais pas créateur
        $isParticipant = $club->getMembres()->contains($user);
        $isCreator = $club->getCreator() === $user;
        if (!$isParticipant || $isCreator) {
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas signaler ce club.'], 403);
        }

        // Validation des données
        if (!$reason || !$message) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        // Eviter le double signalement pour ce club
        $existingReport = $em->getRepository(Report::class)->findOneBy([
            'author' => $user,
            'reportedClub' => $club,
        ]);

        if ($existingReport) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà signalé ce club.'
            ], 409);
        }

        $report = new Report();
        $report->setAuthor($user);
        $report->setReportedClub($club);
        $report->setReason($reason);
        $report->setMessage($message);
        $report->setStatus('en_cours');

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Signalement envoyé !']);
    }
}
