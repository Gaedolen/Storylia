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
        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $statusId = $data['readingStatusId'] ?? null;

        $user = $this->getUser();

        if (!$user || !$bookId || !$statusId) {
            return $this->json(['success' => false, 'message' => 'Données manquantes.']);
        }

        $book = $em->getRepository(Book::class)->find($bookId);
        $status = $em->getRepository(ReadingStatus::class)->find($statusId);

        if (!$book || !$status) {
            return $this->json(['success' => false, 'message' => 'Livre ou statut introuvable.']);
        }

        // Vérifie si le livre est déjà dans la bibliothèque
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

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;

        if (!$bookId) {
            return $this->json(['success' => false, 'message' => 'ID du livre manquant']);
        }

        // Récupérer l'objet Book
        $book = $em->getRepository(Book::class)->find($bookId);
        if (!$book) {
            return $this->json(['success' => false, 'message' => 'Livre introuvable']);
        }

        $entry = $em->getRepository(Bookshelf::class)->findOneBy([
            'utilisateur' => $user,
            'book' => $book
        ]);

        if (!$entry) {
            return $this->json(['success' => false, 'message' => 'Livre non trouvé dans votre bibliothèque']);
        }

        $em->remove($entry);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/livres/{id}/deplacer', name: 'livres_deplacer', methods: ['POST'])]
    public function moveBook(int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $statusId = $data['readingStatusId'] ?? null;

        $logger->info('DEBUG moveBook', ['bookshelfId' => $id, 'data' => $data]);

        // Récupération du Bookshelf
        $bookshelf = $em->getRepository(Bookshelf::class)->find($id);
        if (!$bookshelf) {
            return $this->json(['success' => false, 'message' => 'Livre introuvable dans votre bibliothèque.']);
        }

        // Récupération du statut
        $status = $em->getRepository(ReadingStatus::class)->find($statusId);
        if (!$status) {
            return $this->json(['success' => false, 'message' => 'Statut introuvable.']);
        }

        // Mise à jour du statut
        $bookshelf->setReadingStatus($status);
        $em->flush();

        // Retour JSON avec label et ID
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