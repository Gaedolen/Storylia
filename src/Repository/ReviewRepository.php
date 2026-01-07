<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findSignaled(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reports', 'rep')
            ->where('rep.status = :status')
            ->setParameter('status', \App\Entity\Report::STATUS_EN_COURS)
            ->orderBy('rep.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBookPaginated(Book $book, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByReportsEnCours(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.reports', 'rep')
            ->andWhere('rep.status = :status')
            ->setParameter('status', \App\Entity\Report::STATUS_EN_COURS)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Review[] Returns an array of Review objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Review
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
