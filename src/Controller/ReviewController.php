<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Book;
use App\Entity\Utilisateur;
use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
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
        /** @var Utilisateur $user */
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
        $review->setRating(null);
        $review->setAuthor($book->getAuthor());

        $this->em->persist($review);
        $this->em->flush();

        $responseData = [
            'success' => true,
            'userId' => $user->getId(),
            'userPseudo' => $user->getPseudo(),
            'userProfilePicture' => '/uploads/profiles/' . $user->getProfilePicture(),
            'review' => $comment,
            'date' => $review->getDate()->format('d/m/Y'),
            'statusLabel' => 'Lu', // ou le statut réel si tu gères le bookshelf
        ];

        return new JsonResponse($responseData);
    }

    #[Route('/book/{id}/review', name: 'app_review_submit', methods: ['POST'])]
    public function submitReview(Book $book, Request $request, ReviewRepository $reviewRepo, EntityManagerInterface $em) 
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['success' => false, 'message' => 'Utilisateur non connecté.']);

        $data = json_decode($request->getContent(), true);
        $rating = $data['rating'] ?? null;
        $comment = $data['comment'] ?? '';

        if (!$rating || $rating < 1 || $rating > 5) {
            return $this->json(['success' => false, 'message' => 'Note invalide.']);
        }

        $review = $reviewRepo->findOneBy(['book' => $book, 'utilisateur' => $user]);
        if (!$review) {
            $review = new Review();
            $review->setBook($book)->setUtilisateur($user)->setAuthor($book->getAuthor())->setDate(new \DateTime());
        }

        $review->setRating($rating)->setComment($comment);
        $em->persist($review);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
