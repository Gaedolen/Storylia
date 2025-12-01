<?php

namespace App\Repository;

use App\Entity\ClubReadingMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubReadingMonth>
 *
 * @method ClubReadingMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubReadingMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubReadingMonth[]    findAll()
 * @method ClubReadingMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubReadingMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubReadingMonth::class);
    }

    /**
     * Récupère le mois de lecture actif pour un club donné
     *
     * @param int $clubId
     * @return ClubReadingMonth|null
     */
    public function findCurrentMonthForClub(int $clubId): ?ClubReadingMonth
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('m.month', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les mois de lecture d'un club
     *
     * @param int $clubId
     * @return ClubReadingMonth[]
     */
    public function findAllMonthsForClub(int $clubId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('m.month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un mois de lecture spécifique d'un club
     *
     * @param int $clubId
     * @param string $month
     * @return ClubReadingMonth|null
     */
    public function findByClubAndMonth(int $clubId, string $month): ?ClubReadingMonth
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.club = :clubId')
            ->andWhere('m.month = :month')
            ->setParameter('clubId', $clubId)
            ->setParameter('month', $month)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
