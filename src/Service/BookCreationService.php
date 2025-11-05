<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookCreationService
{
    private EntityManagerInterface $em;
    private AuthorRepository $authorRepository;

    public function __construct(EntityManagerInterface $em, AuthorRepository $authorRepository)
    {
        $this->em = $em; // Pour sauvegarder les entités en BDD
        $this->authorRepository = $authorRepository;
    }

     /**
     * Crée un livre et son auteur si nécessaire
     * @param array $bookData Tableau issu de l'API
     * @return Book|null
     */
    public function createBookFromApi(array $bookData): ?Book
    {
        // Si le titre ou l'auteur est manquant, on ne crée rien
        if(empty($bookData['title']) || empty($bookData['author'])) return null;

        $authorName = $bookData['authors'][0]; // On prend le premier auteur

        // Vérifier si l'auteur existe déjà en BDD
        $author = $this->authorRepository->findOneBy(['name' => $authorName]);
        if(!$author) {
            // Si non, on crée un nouvel auteur
            $author = new Author();
            $author->setName($authorName);
            $this->em->persist($author); // On le prépare pour l'enregistrement
        }

        // Création du livre
        $book = new Book();
        $book->setTitle($bookData['title']);
        $book->setAuthor($author); // Lier l'auteur au livre
        $book->setGenre($bookData['genres'][0] ?? null); // On prend le premier genre si disponible
        $book->setCover($bookData['cover' ?? null]);
        $book->setSummary($bookData['summary'] ?? null);
        $book->setEdition($bookData['edition']);

        // Conversion de l'année en DateTime
        if (!empty($bookData['publish_date'])) {
            try {
                // On essaie de transformer la date en format complet (jour/mois/année)
                $date = new \DateTime($bookData['publish_date']);
                $book->setPublicationDate($date);
            } catch (\Exception $e) {
                // Si la date est incomplète (ex: juste "1997"), on met le 1er janvier par défaut
                $year = preg_replace('/[^0-9]/', '', $bookData['publish_date']);
                $book->setPublicationDate(new \DateTime("$year-01-01"));
            }
        }

        // Persistance en base
        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }
}