<?php

namespace App\Repository;

use App\Entity\Book;
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

        // Requête SQL brute avec my_unaccent pour UTF-8
        $sql = "
            SELECT b.title, 
                CONCAT(a.first_name, ' ', a.family_name) AS author
            FROM book b
            JOIN author a ON b.author_id = a.id
            WHERE my_unaccent(LOWER(b.title)) LIKE my_unaccent(:title)
        ";

        if (strlen($authorQuery) >= 2) {
            $sql .= " AND (
                my_unaccent(LOWER(a.family_name)) LIKE my_unaccent(:author)
                OR my_unaccent(LOWER(a.first_name)) LIKE my_unaccent(:author)
            )";
        }

        $sql .= " LIMIT 5";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('title', '%' . $titleQuery . '%');
        if (strlen($authorQuery) >= 2) {
            $stmt->bindValue('author', '%' . $authorQuery . '%');
        }

        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
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
