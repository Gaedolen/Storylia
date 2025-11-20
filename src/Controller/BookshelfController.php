<?php

namespace App\Controller;

use App\Entity\Bookshelf;
use App\Entity\Book;
use App\Entity\ReadingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        return $this->json(['success' => true, 'message' => 'Livre ajouté ou mis à jour avec succès !']);
    }
}