<?php

namespace App\Controller;

use App\Entity\Club;
use App\Form\ClubType;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/clubs')]
class ClubController extends AbstractController
{
   #[Route('/', name: 'club_index', methods: ['GET'])]
    public function index(Request $request, ClubRepository $clubRepository): Response
    {
        $search = $request->query->get('q');

        // Requête Doctrine insensible à la casse
        $qb = $clubRepository->createQueryBuilder('c');

        if ($search) {
            $qb->where('LOWER(c.name) LIKE :q')
               ->setParameter('q', '%' . strtolower($search) . '%');
        }

        $clubs = $qb->getQuery()->getResult();

        return $this->render('club/club_listing.html.twig', [
            'clubs' => $clubs,
            'search' => $search
        ]);
    }

    #[Route('/create', name: 'club_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $club = new Club();
        $club->setCreator($this->getUser()); // Créateur = utilisateur connecté

        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid().'_'.time().'.'.$photoFile->guessExtension();
                $photoFile->move($this->getParameter('clubs_photos_directory'), $newFilename);
                $club->setPhoto($newFilename);
            }

            $em->persist($club);
            $em->flush();

            $this->addFlash('success', 'Le club a été créé avec succès !');

            return $this->redirectToRoute('club_index');
        }

        return $this->render('club/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clubs/{id}', name: 'club_show', methods: ['GET'])]
    public function show(Club $club): Response
    {
        return $this->render('club/show.html.twig', [
            'club' => $club,
        ]);
    }
}
