<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Utilisateur;
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
    public function index(MessageRepository $messageRepo): Response
    {
        $user = $this->getUser(); // L'employé connecté

        // Récupère tous les messages de l'utilisateur avec l'admin
        $messages = $messageRepo->findByUserAndAdmin($user);

        return $this->render('messagerie/message_admin_employe.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/envoyer', name: 'send', methods: ['POST'])]
    public function envoyer(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $receiverId = $request->request->get('receiver_id'); 
        $content = $request->request->get('content');

        $receiver = $em->getRepository(Utilisateur::class)->find($receiverId);

        if (!$receiver || !$content) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Impossible d’envoyer le message.']);
            }
            $this->addFlash('danger', 'Impossible d’envoyer le message.');
            return $this->redirectToRoute('messagerie_index');
        }

        $message = new Message();
        $message->setSender($user)
                ->setReceiver($receiver)
                ->setContent($content);

        $em->persist($message);
        $em->flush();

        // Si c'est une requête fetch AJAX
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'sender' => $user->getPseudo(),
                    'receiver' => $receiver->getPseudo(),
                    'content' => $content,
                    'createdAt' => $message->getCreatedAt()->format('d/m/Y H:i')
                ]
            ]);
        }

        return $this->redirectToRoute('messagerie_index');
    }


    #[Route('/load', name: 'load', methods: ['GET'])]
    public function loadMessages(MessageRepository $repo): Response
    {
        $user = $this->getUser();
        $messages = $repo->findByUserAndAdmin($user);

        $data = array_map(fn($msg) => [
            'id' => $msg->getId(),
            'sender_id' => $msg->getSender()->getId(),
            'sender' => $msg->getSender()->getPseudo(),
            'receiver_id' => $msg->getReceiver()->getId(),
            'receiver' => $msg->getReceiver()->getPseudo(),
            'content' => $msg->getContent(),
            'createdAt' => $msg->getCreatedAt()->format('d/m/Y H:i')
        ], $messages);

        return $this->json($data);
    }
}
