<?php

namespace App\Controller;

use App\Repository\ReviewRepository;
use App\Entity\Report;
use App\Entity\Book;
use App\Repository\ReportRepository;
use App\Form\BookType;
use App\Form\ReportType;
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

        // Gestion avis en BDD
        $review->setStatus('refuse');
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

        $this->addFlash('success', 'Avis supprimé et utilisateur notifié.');
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

    #[Route('/livre/{id}/edit', name: 'employe_livre_edit')]
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

    #[Route('/utilisateurs-signales', name: 'employe_utilisateurs_signales')]
    public function utilisateursSignales(
        ReportRepository $reportRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $totalReports = $reportRepository->count(['status' => Report::STATUS_EN_COURS]);
        $totalPages = ceil($totalReports / $limit);

        $reports = $reportRepository->findBy(
            ['status' => Report::STATUS_EN_COURS],
            ['date' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        // Création du formulaire pour signaler un utilisateur
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setAuthor($this->getUser()); // l'employé qui signale
            $report->setStatus(Report::STATUS_EN_COURS);
            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Signalement envoyé.');
            return $this->redirectToRoute('employe_utilisateurs_signales');
        }

        return $this->render('employe/utilisateurs_signales.html.twig', [
            'reports' => $reports,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateur-signalement/{id}', name: 'employe_utilisateur_signalement')]
    public function detailSignalement(Report $report): Response
    {
        return $this->render('employe/utilisateur_signalement_detail.html.twig', [
            'report' => $report
        ]);
    }

    #[Route('/utilisateur-signalement/{id}/transmettre', name: 'employe_signalement_transmettre', methods: ['POST'])]
    public function transmettreAdmin(
        Report $report,
        EntityManagerInterface $em,
        Request $request,
        MailerInterface $mailer
    ): Response {
        if (!$this->isCsrfTokenValid('transmettre'.$report->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $report->setStatus(Report::STATUS_ADMIN);
        $em->flush();

        // Mail admin
        $email = (new TemplatedEmail())
            ->from('no-reply@storylia.com')
            ->to('admin@storylia.com')
            ->subject('Signalement transmis par un employé')
            ->htmlTemplate('email/report_admin.html.twig')
            ->context([
                'report' => $report
            ]);
        $mailer->send($email);

        $this->addFlash('success', 'Signalement transmis à l’administrateur.');
        return $this->redirectToRoute('employe_utilisateurs_signales');
    }

    #[Route('/utilisateur-signalement/{id}/refuser', name: 'employe_signalement_refuser', methods: ['POST'])]
    public function refuserSignalement(
        Report $report,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('refuser'.$report->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $report->setStatus(Report::STATUS_REFUSE);
        $em->flush();

        $this->addFlash('info', 'Signalement refusé.');
        return $this->redirectToRoute('employe_utilisateurs_signales');
    }

    #[Route('/signalement/{id}/mail-utilisateur', name: 'employe_signalement_mail_utilisateur', methods: ['POST'])]
    public function mailUtilisateurSignale(
        Report $report,
        Request $request,
        MailerInterface $mailer
    ): Response {
        if (!$this->isCsrfTokenValid('mail_user'.$report->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $report->getReported();
        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur signalé.');
            return $this->redirectToRoute('employe_utilisateurs_signales');
        }

        // Récupération des données du formulaire
        $subject = $request->request->get('subject');
        $reason  = $request->request->get('reason');
        $message = $request->request->get('message');

        $email = (new TemplatedEmail())
            ->from('moderation@storylia.com')
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate('email/signalement_utilisateur.html.twig')
            ->context([
                'user'    => $user,
                'report'  => $report,
                'subject' => $subject,
                'reason'  => $reason,
                'message' => $message,
            ]);

        $mailer->send($email);

        $this->addFlash('success', 'Mail envoyé à l’utilisateur signalé.');
        return $this->redirectToRoute('employe_utilisateurs_signales');
    }

    #[Route('/signalement/{id}/mail-auteur', name: 'employe_signalement_mail_auteur', methods: ['POST'])]
    public function mailAuteurSignalement(
        Report $report,
        Request $request,
        MailerInterface $mailer
    ): Response {
        if (!$this->isCsrfTokenValid('mail_author'.$report->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $author = $report->getAuthor();

        // Récupération des données du formulaire
        $subject = $request->request->get('subject');
        $reason  = $request->request->get('reason');
        $message = $request->request->get('message');

        $email = (new TemplatedEmail())
            ->from('moderation@storylia.com')
            ->to($author->getEmail())
            ->subject($subject)
            ->htmlTemplate('email/signalement_auteur.html.twig')
            ->context([
                'user'    => $author,
                'report'  => $report,
                'subject' => $subject,
                'reason'  => $reason,
                'message' => $message,
            ]);

        $mailer->send($email);

        $this->addFlash('success', 'Mail envoyé à l’auteur du signalement.');
        return $this->redirectToRoute('employe_utilisateurs_signales');
    }
}