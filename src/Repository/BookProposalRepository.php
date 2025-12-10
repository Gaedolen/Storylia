<?php

namespace App\Repository;

use App\Entity\BookProposal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookProposal>
 *
 * @method BookProposal|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookProposal|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookProposal[]    findAll()
 * @method BookProposal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookProposal::class);
    }

    /**
     * Récupère les propositions d'un utilisateur pour un mois donné
     */
    public function findByUserAndMonth(int $userId, string $month): array
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.readingMonth', 'rm')
            ->where('bp.proposer = :userId')
            ->andWhere('rm.month = :month')
            ->setParameter('userId', $userId)
            ->setParameter('month', $month)
            ->getQuery()
            ->getResult();
    }

    public function findByMonthAndClub(string $month, int $clubId): array
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.readingMonth', 'rm')
            ->join('rm.club', 'c')
            ->where('rm.month = :month')
            ->andWhere('c.id = :clubId')
            ->setParameter('month', $month)
            ->setParameter('clubId', $clubId)
            ->getQuery()
            ->getResult();
    }

    public function findRecentBooksByClub(int $clubId, int $limit = 10): array
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.readingMonth', 'rm')
            ->join('rm.club', 'c')
            ->join('bp.book', 'b')
            ->where('c.id = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('bp.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

}
