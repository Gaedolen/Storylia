<?php

namespace App\Controller;

use App\Entity\Bookshelf;
use App\Entity\Book;
use App\Entity\ReadingStatus;
use App\Repository\BookshelfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bibliotheque')]
class BookshelfController extends AbstractController
{
    #[Route('/ajouter-ou-mettre-a-jour', name: 'bookshelf_add_or_update', methods: ['POST'])]
    public function addOrUpdate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecté'], 403);
        }

        // --- CSRF ---
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'] ?? null;

        if (!$this->isCsrfTokenValid('book_create', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
        // --- Récupération des données ---
        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $statusId = $data['readingStatusId'] ?? null;

        if (!$bookId || !$statusId) {
            return $this->json(['success' => false, 'message' => 'Données manquantes.'], 400);
        }

        $book = $em->getRepository(Book::class)->find($bookId);
        $status = $em->getRepository(ReadingStatus::class)->find($statusId);

        if (!$book || !$status) {
            return $this->json(['success' => false, 'message' => 'Livre ou statut introuvable.'], 404);
        }

        // --- Validation du label de statut ---
        $validLabels = [
            'en_train_de_lire','coup_de_coeur','adore','apprecie',
            'mitige','pas_aime','lu_aussi','pal','envies'
        ];

        if (!in_array($status->getLabel(), $validLabels)) {
            return $this->json(['success' => false, 'message' => 'Statut invalide.'], 400);
        }

        // --- Vérifie si le livre est déjà dans la bibliothèque ---
        $bookshelf = $em->getRepository(Bookshelf::class)->findOneBy([
            'utilisateur' => $user,
            'book' => $book
        ]);

        if (!$bookshelf) {
            $bookshelf = new Bookshelf();
            $bookshelf->setUtilisateur($user);
            $bookshelf->setBook($book);
        }

        $bookshelf->setReadingStatus($status);
        $em->persist($bookshelf);
        $em->flush();

        return $this->json([
            'success' => true,
            'bookshelfId' => $bookshelf->getId(),
            'readingStatusLabel' => $status->getLabel(),
        ]);
    }

    #[Route('/supprimer', name: 'bookshelf_remove', methods: ['POST'])]
    public function remove(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecté'], 403);
        }

        // Vérifie le token CSRF
        $csrfToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('bookshelf_add_or_update', $csrfToken)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;

        if (!$bookId) {
            return $this->json(['success' => false, 'message' => 'ID du livre manquant']);
        }

        $book = $em->getRepository(Book::class)->find($bookId);
        if (!$book) {
            return $this->json(['success' => false, 'message' => 'Livre introuvable']);
        }

        $entry = $em->getRepository(Bookshelf::class)->findOneBy([
            'utilisateur' => $user,
            'book' => $book
        ]);

        if (!$entry) {
            // Livre déjà supprimé => considérer comme succès
            return $this->json(['success' => true]);
        }

        $em->remove($entry);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/livres/{id}/deplacer', name: 'livres_deplacer', methods: ['POST'])]
    public function moveBook(int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecté'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $token = $data['_token'] ?? null;

        if (!$this->isCsrfTokenValid('bookshelf_add_or_update', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $statusId = $data['readingStatusId'] ?? null;

        // Vérification Bookshelf + appartenance utilisateur
        $bookshelf = $em->getRepository(Bookshelf::class)->findOneBy([
            'id' => $id,
            'utilisateur' => $user
        ]);

        if (!$bookshelf) {
            return $this->json(['success' => false, 'message' => 'Livre introuvable dans votre bibliothèque.']);
        }

        $status = $em->getRepository(ReadingStatus::class)->find($statusId);
        if (!$status) {
            return $this->json(['success' => false, 'message' => 'Statut introuvable.']);
        }

        $validLabels = ['en_train_de_lire','coup_de_coeur','adore','apprecie','mitige','pas_aime','lu_aussi','pal','envies'];
        if (!in_array($status->getLabel(), $validLabels)) {
            return $this->json(['success' => false, 'message' => 'Statut invalide.']);
        }

        $bookshelf->setReadingStatus($status);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Livre déplacé avec succès !',
            'bookshelfId' => $bookshelf->getId(),
            'readingStatusLabel' => match($status->getLabel()) {
                'en_train_de_lire' => 'En train de lire',
                'coup_de_coeur' => 'Coup de coeur',
                'adore' => "J'adore",
                'apprecie' => 'Apprécié',
                'mitige' => 'Mitigé',
                'pas_aime' => 'Pas aimé',
                'lu_aussi' => 'Lu',
                'pal' => 'Pile à lire',
                'envies' => 'Mes envies',
                default => $status->getLabel(),
            }
        ]);
    }
}