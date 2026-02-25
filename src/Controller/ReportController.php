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
        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        // Vérification CSRF
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

        // Décodage JSON sécurisé
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Payload invalide.'
            ], 400);
        }

        // Validation des champs
        $reviewId = $data['reviewId'] ?? null;
        $reason   = trim($data['reason'] ?? '');
        $message  = trim($data['message'] ?? '');

        if (
            !$reviewId ||
            !ctype_digit((string) $reviewId) ||
            empty($reason) ||
            empty($message)
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides.'
            ], 400);
        }

        // Limite serveur
        if (strlen($message) > 300) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Message trop long (300 caractères max).'
            ], 400);
        }

        // Vérification existence avis
        $review = $reviewRepo->find((int) $reviewId);

        if (!$review) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Avis introuvable.'
            ], 404);
        }

        // Interdiction auto-signalement
        if ($review->getUtilisateur() === $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas signaler votre propre avis.'
            ], 403);
        }

        // Blocage double signalement
        $existingReport = $em->getRepository(Report::class)->findOneBy([
            'author' => $user,
            'review' => $review,
        ]);

        if ($existingReport) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà signalé cet avis.'
            ], 409);
        }

        // Création signalement
        $report = new Report();
        $report->setAuthor($user);
        $report->setReported($review->getUtilisateur());
        $report->setReview($review);
        $report->setReason($reason);
        $report->setMessage($message);
        $report->setStatus('en_cours');

        $em->persist($report);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Signalement envoyé.'
        ], 201);
    }

    #[Route('/book', name: 'book', methods: ['POST'])]
    public function reportBook(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse {

        /** @var \App\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        // CSRF
        $csrfToken = $request->headers->get('X-CSRF-TOKEN');

        if (
            !$csrfToken ||
            !$csrfTokenManager->isTokenValid(
                new CsrfToken('report_book', $csrfToken)
            )
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'CSRF invalide.'
            ], 403);
        }

        // Décodage JSON sécurisé
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Payload invalide.'
            ], 400);
        }

        $bookId  = $data['book_id'] ?? null;
        $reason  = trim($data['reason'] ?? '');
        $message = trim($data['message'] ?? '');

        if (
            !$bookId ||
            !ctype_digit((string)$bookId) ||
            empty($reason) ||
            empty($message)
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides.'
            ], 400);
        }

        // Limite serveur
        if (strlen($message) > 300) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Message trop long (300 max).'
            ], 400);
        }

        // Whitelist des motifs autorisés
        $allowedReasons = [
            'titre_incorrect',
            'mauvais_auteur',
            'themes_incorrects',
            'resume_inexact',
            'autre'
        ];

        if (!in_array($reason, $allowedReasons, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Motif invalide.'
            ], 400);
        }

        // Vérification livre
        $book = $em->getRepository(Book::class)->find((int)$bookId);

        if (!$book) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Livre introuvable.'
            ], 404);
        }

        // Blocage double signalement
        $existingReport = $em->getRepository(Report::class)->findOneBy([
            'author' => $user,
            'reportedBook' => $book,
        ]);

        if ($existingReport) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà signalé ce livre.'
            ], 409);
        }

        // Création report
        $report = new Report();
        $report->setAuthor($user);
        $report->setReportedBook($book);
        $report->setReason($reason);
        $report->setMessage($message);
        $report->setStatus('en_cours');

        $em->persist($report);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Signalement envoyé.'
        ], 201);
    }

    #[Route('/user/{id}', name: 'user', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reportUser(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            if ($user === $utilisateur) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas vous signaler vous-même.'
                ], 400);
            }
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

        $allowedReasons = [
            'harcelement',
            'usurpation',
            'spam',
            'contenu_inapproprie',
            'autre'
        ];

        if (!in_array($data['reason'], $allowedReasons, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Motif invalide.'
            ], 400);
        }

        $message = trim($data['message']);

        if (strlen($message) < 10 || strlen($message) > 500) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Message invalide.'
            ], 400);
        }

        $report = new Report();
        $report->setAuthor($user);
        $report->setReported($utilisateur);
        $report->setReason($data['reason']);
        $report->setMessage($message);
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
