<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class ReviewController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    #[Route('/laisser-avis', name: 'leave_review', methods: ['POST'])]
    public function leaveReview(Request $request, BookRepository $bookRepository): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $bookId = $data['bookId'] ?? null;
        $comment = trim($data['review'] ?? '');

        if (!$bookId || !$comment) {
            return new JsonResponse(['success' => false, 'message' => 'Informations manquantes.'], 400);
        }

        $book = $bookRepository->find($bookId);
        if (!$book) {
            return new JsonResponse(['success' => false, 'message' => 'Livre introuvable.'], 404);
        }

        $review = new Review();
        $review->setBook($book);
        $review->setUtilisateur($user);
        $review->setComment($comment);
        $review->setDate(new \DateTime());
        $review->setRating(null); // facultatif
        $review->setAuthor($book->getAuthor());

        $this->em->persist($review);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Avis enregistré !']);
    }
}
