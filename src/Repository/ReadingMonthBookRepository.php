<?php

namespace App\Repository;

use App\Entity\ReadingMonthBook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingMonthBook>
 */
class ReadingMonthBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingMonthBook::class);
    }

    /**
     * Vérifie si un utilisateur a déjà proposé un livre pour un mois donné
     */
    public function hasUserProposedBook(int $clubId, string $month, int $userId): bool
    {
        $qb = $this->createQueryBuilder('rmb')
            ->join('rmb.readingMonth', 'crm')
            ->where('crm.club = :clubId')
            ->andWhere('crm.month = :month')
            ->andWhere('rmb.user = :userId')
            ->setParameter('clubId', $clubId)
            ->setParameter('month', $month)
            ->setParameter('userId', $userId)
            ->select('COUNT(rmb.id)');


        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Récupère tous les livres proposés pour un mois donné
     *
     * @return ReadingMonthBook[]
     */
    public function findProposedBooks(int $clubId, string $month): array
    {
        return $this->createQueryBuilder('rmb')
            ->join('rmb.readingMonth', 'crm')
            ->where('crm.club = :clubId')
            ->andWhere('crm.month = :month')
            ->setParameter('clubId', $clubId)
            ->setParameter('month', $month)
            ->getQuery()
            ->getResult();
    }
}
