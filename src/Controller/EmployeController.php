<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use App\Entity\Report;
use App\Entity\Book;
use App\Repository\ReportRepository;
use App\Form\BookType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


#[Route('/employe')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeController extends AbstractController
{
    #[Route('/dashboard', name: 'employe_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('employe/dashboard.html.twig');
    }

    #[Route('/avis-signales', name: 'employe_avis_signales')]
    public function avisSignales(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findByReportsEnCours();

        return $this->render('employe/avis_signales.html.twig', [
            'avis' => $reviews
        ]);
    }

    #[Route('/avis-signales/{id}', name: 'employe_avis_detail')]
    public function avisDetail(int $id, ReviewRepository $reviewRepository): Response
    {
        $review = $reviewRepository->find($id);
        if (!$review) {
            throw $this->createNotFoundException("Avis introuvable.");
        }

        return $this->render('employe/avis_detail.html.twig', [
            'review' => $review
        ]);
    }

    #[Route('/avis-signales/valider/{id}', name: 'employe_avis_valider')]
    public function validerAvis(int $id, ReviewRepository $reviewRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $review = $reviewRepository->find($id);
        if (!$review) {
            $this->addFlash('warning', 'Cet avis a déjà été traité.');
            return $this->redirectToRoute('employe_avis_signales');
        }

        $user = $review->getUtilisateur();

        // Supprime l'avis
        $em->remove($review);
        $em->flush();

        $report = $review->getReports()->first();

        // Envoi du mail
        $email = (new TemplatedEmail())
            ->from('no-reply@storylia.com')
            ->to($user->getEmail())
            ->subject('Votre avis a été modéré')
            ->htmlTemplate('email/avis_supprime.html.twig')
            ->context([
                'user' => $user,
                'review' => $review,
                'report' => $report,
            ]);

        $mailer->send($email);

        $this->addFlash('success', 'Avis validé et utilisateur notifié.');
        return $this->redirectToRoute('employe_avis_signales');
    }

    #[Route('/avis-signales/supprimer-report/{id}', name: 'employe_report_supprimer')]
    public function supprimerReport(int $id, EntityManagerInterface $em, ReportRepository $reportRepository): Response
    {
        $report = $reportRepository->find($id);
        if (!$report) {
            throw $this->createNotFoundException("Signalement introuvable.");
        }

        $em->remove($report);
        $em->flush();

        $this->addFlash('success', 'Signalement supprimé.');
        return $this->redirectToRoute('employe_avis_signales');
    }

    #[Route('/avis-signales/detail/{id}', name: 'employe_avis_detail')]
    public function voirSignalement(int $id, ReviewRepository $reviewRepository): Response
    {
        $review = $reviewRepository->find($id);
        if (!$review) {
            throw $this->createNotFoundException("Avis introuvable.");
        }

        return $this->render('employe/avis_detail.html.twig', [
            'review' => $review
        ]);
    }

    #[Route('/livres-signales', name: 'employe_livres_signales')]
    public function livresSignales(ReportRepository $reportRepository): Response
    {
        // On récupère uniquement les reports sur des livres en cours
        $reports = $reportRepository->findBy([
            'status' => 'en_cours'
        ]);

        return $this->render('employe/livres_signales.html.twig', [
            'reports' => $reports
        ]);
    }

    #[Route('/livres-signales/valider/{id}', name: 'employe_livre_valider', methods: ['POST'])]
    public function validerLivre(int $id, ReportRepository $reportRepository, EntityManagerInterface $em): Response
    {
        $report = $reportRepository->find($id);

        if (!$report) {
            return $this->json(['success' => false, 'message' => 'Report introuvable.']);
        }

        // Supprime le report
        $em->remove($report);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/employe/livre/{id}/edit', name: 'employe_livre_edit')]
    #[IsGranted('ROLE_EMPLOYE')]
    public function edit(Book $book, Request $request, EntityManagerInterface $em): Response
    {
        $report = $em->getRepository(Report::class)->findOneBy(
            ['reportedBook' => $book],
            ['date' => 'DESC']
        );

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('cover')->getData();

            if ($file) {
                $filename = uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('covers_directory'),
                    $filename
                );
                $book->setCover('uploads/covers/'.$filename);
            }


            $em->flush();

            $this->addFlash('success', 'Livre modifié !');
            return $this->redirectToRoute('employe_livres_signales');
        }

        return $this->render('employe/livre_edit.html.twig', [
            'form' => $form->createView(),
            'book' => $book,
            'report' => $report,
        ]);
    }
}