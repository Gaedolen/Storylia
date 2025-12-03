<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Club;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function rechercheRapideSQL(string $titleQuery, string $authorQuery = ''): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $titleQuery = mb_strtolower(trim($titleQuery));
        $authorQuery = mb_strtolower(trim($authorQuery));

        $sql = "
            SELECT b.id, b.title, b.cover, a.name AS author
            FROM book b
            JOIN author a ON b.author_id = a.id
            WHERE LOWER(b.title) LIKE LOWER(:title)
        ";

        if (strlen($authorQuery) >= 2) {
            $sql .= " AND LOWER(a.name) LIKE LOWER(:author)";
        }

        $sql .= " LIMIT 5";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('title', '%' . $titleQuery . '%');
        if (strlen($authorQuery) >= 2) {
            $stmt->bindValue('author', '%' . $authorQuery . '%');
        }

        $result = $stmt->executeQuery();
        $books = $result->fetchAllAssociative();

        // Encoder l'image BLOB en base64
        foreach ($result as &$book) {
            if ($book['cover'] !== null) {
                $book['cover'] = 'data:image/jpeg;base64,' . base64_encode($book['cover']);
            }
        }

        return $books;
    }

    // Récupère le livre prévu pour le club et le mois donné
    
    public function findNextBookForClub(Club $club, \DateTime $month): ?Book
    {
        $start = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
        $end   = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('b')
            ->andWhere('b.club = :club')
            ->andWhere('b.publicationDate BETWEEN :start AND :end')
            ->setParameter('club', $club)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
