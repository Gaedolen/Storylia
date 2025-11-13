<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;


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
     * @param array $data
     * @return Book|null
     */
    public function createOrUpdateBookFromApi(array $data): ?Book
    {
        // Récupération ou création de l'auteur
        $authorName = $data['author'] ?? 'Auteur inconnu';
        $author = $this->authorRepository->findOneBy(['name' => $authorName]);

        if (!$author) {
            $author = new Author();
            $author->setName($authorName);
            $this->em->persist($author);
            $this->em->flush(); // pour que l'ID existe
        }

        // Vérifier si un livre avec la même combinaison title + author + format existe
        $format = $data['format'] ?? 'Broché';
        $existingBook = $this->em->getRepository(Book::class)
            ->findOneBy([
                'title' => $data['title'] ?? '',
                'author' => $author,
                'format' => $format,
            ]);

        if ($existingBook) {
            // Mise à jour des champs non-uniques seulement
            return $this->updateBookFields($existingBook, $data);
        }

        // Création d'un nouveau livre
        $book = new Book();
        $book->setTitle($data['title'] ?? '');
        $book->setAuthor($author);
        $book->setFormat($format);

        return $this->updateBookFields($book, $data);
    }

    /**
     * Met à jour uniquement les champs modifiables d'un livre
     */
    public function updateBookFields(Book $book, array $data): Book
    {
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

        $this->em->persist($book);

        return $book;
    }

    /**
     * Import massif de livres
     */
    public function importBooks(array $booksData, int $batchSize = 50): array
    {
        $imported = [];
        $updated = [];
        $count = 0;

        foreach ($booksData as $data) {
            $book = $this->createOrUpdateBookFromApi($data);

            if ($book) {
                // Déterminer si c'était une mise à jour ou un nouvel import
                if ($book->getId()) {
                    $updated[] = $book->getTitle();
                } else {
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
        $this->em->clear();

        return [
            'imported' => $imported,
            'updated' => $updated,
        ];
    }
}
