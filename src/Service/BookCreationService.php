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
        $this->em = $em;
        $this->authorRepository = $authorRepository;
    }

    /**
     * Crée ou met à jour un livre à partir des données d'une API
     *
     * @param array $data Données issues de l'API
     * @return Book|null
     */
    public function createOrUpdateBookFromApi(array $data): ?Book
    {
        $isbn = $data['isbn'] ?? null;
        if (!$isbn) return null;

        // Cherche l'existant
        $book = $this->em->getRepository(Book::class)->findOneBy(['isbn' => $isbn]);

        if ($book) {
            return $this->updateBookFromApi($book, $data);
        }

        $book = new Book();
        $book->setIsbn($isbn);
        $this->updateBookFromApi($book, $data);

        $this->em->persist($book);

        return $book;
    }

    /**
     * Met à jour un livre existant avec les données de l'API
     */
    public function updateBookFromApi(Book $book, array $data): Book
    {
        $book->setTitle($data['title'] ?? $book->getTitle() ?? 'Titre inconnu');
        $book->setVoTitle($data['voTitle'] ?? $book->getVoTitle() ?? $book->getTitle());
        $book->setSummary($data['summary'] ?? $book->getSummary());
        $book->setPages($data['pages'] ?? $book->getPages());
        $book->setPublishers($data['publishers'] ?? $book->getPublishers() ?? []);
        $book->setGenres($data['genres'] ?? $book->getGenres() ?? []);
        $book->setSubjects($data['subjects'] ?? $book->getSubjects() ?? []);

        if (!empty($data['publicationDate']) && preg_match('/^\d{4}(-\d{2}-\d{2})?$/', $data['publicationDate'])) {
            $book->setPublicationDate(new \DateTime($data['publicationDate']));
        } else {
            $book->setPublicationDate(null);
        }

        $book->setCover($data['cover'] ?? $book->getCover() ?? '/images/default_cover.jpg');
        $book->setFormat($data['format'] ?? $book->getFormat() ?? 'Broché');

        // Gestion de l'auteur
        $authorName = $data['author'] ?? 'Auteur inconnu';
        $author = $this->authorRepository->findOneBy(['name' => $authorName]);

        if (!$author) {
            $author = new Author();
            $author->setName($authorName);
            $this->em->persist($author);
        }

        $book->setAuthor($author);

        return $book;
    }

    /**
     * Gère l’import massif de plusieurs livres en optimisant les flush()
     *
     * @param array $booksData Données de plusieurs livres
     * @param int $batchSize Nombre de livres par flush
     * @return array Résumé des actions
     */
    public function importBooks(array $booksData, int $batchSize = 50): array
    {
        $imported = [];
        $updated = [];
        $count = 0;

        foreach ($booksData as $data) {
            $existingBook = $this->em->getRepository(Book::class)->findOneBy(['isbn' => $data['isbn'] ?? null]);

            if ($existingBook) {
                $this->updateBookFromApi($existingBook, $data);
                $updated[] = $existingBook->getTitle();
            } else {
                $book = $this->createOrUpdateBookFromApi($data);
                if ($book) {
                    $imported[] = $book->getTitle();
                }
            }

            $count++;
            if ($count % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        // Flush final
        $this->em->flush();

        return [
            'imported' => $imported,
            'updated' => $updated,
        ];
    }
}
