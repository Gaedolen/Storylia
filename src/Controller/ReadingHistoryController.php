<?php

namespace App\Controller;

use App\Entity\ReadingHistory;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReadingHistoryController extends AbstractController
{
    #[Route('/ajouter-date-lecture', name: 'add_reading_date', methods: ['POST'])]
    public function addReadingDate(
        Request $request,
        BookRepository $bookRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $readingDate = $data['readingDate'] ?? null;

        if (!$bookId || !$readingDate) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
        }

        $book = $bookRepo->find($bookId);
        if (!$book) {
            return new JsonResponse(['success' => false, 'message' => 'Livre introuvable'], 404);
        }

        $date = new \DateTime($readingDate);
        $today = new \DateTime();

        if ($date > $today) {
            return new JsonResponse(['success' => false, 'message' => 'Date future impossible'], 400);
        }

        // Enregistrer l’historique
        $history = new ReadingHistory();
        $history->setBook($book);
        $history->setUtilisateur($user);
        $history->setReadingDate($date);

        $em->persist($history);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
