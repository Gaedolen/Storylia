<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/messagerie', name: 'messagerie_')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(MessageRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        $messages = $repo->findBy([], ['createdAt' => 'ASC']);

        return $this->render('messagerie/message_admin_employe.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/envoyer', name: 'send', methods: ['POST'])]
    public function envoyer(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        $content = trim($request->request->get('content'));

        if (!$content) {
            return $this->json(['success' => false]);
        }

        $message = new Message();
        $message->setSender($this->getUser());
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());

        $em->persist($message);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => [
                'id' => $message->getId(),
                'sender_id' => $message->getSender()->getId(),
                'sender' => $message->getSender()->getPseudo(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('d/m/Y H:i')
            ]
        ]);
    }

    #[Route('/load', name: 'load', methods: ['GET'])]
    public function loadMessages(MessageRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        $messages = $repo->findBy([], ['createdAt' => 'ASC']);

        $data = array_map(fn($msg) => [
            'id' => $msg->getId(),
            'sender_id' => $msg->getSender()->getId(),
            'sender' => $msg->getSender()->getPseudo(),
            'content' => $msg->getContent(),
            'createdAt' => $msg->getCreatedAt()->format('d/m/Y H:i')
        ], $messages);

        return $this->json($data);
    }
}