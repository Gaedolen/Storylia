<?php

namespace App\Repository;

use App\Entity\ClubReview;
use App\Entity\Club;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubReview>
 *
 * @method ClubReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubReview[]    findAll()
 * @method ClubReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubReview::class);
    }

    /**
     * Récupère tous les avis pour un mois de lecture donné
     *
     * @param int $readingMonthId
     * @return ClubReview[]
     */
    public function findByReadingMonth(int $readingMonthId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.readingMonth = :monthId')
            ->setParameter('monthId', $readingMonthId)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les avis d'un utilisateur pour un mois donné
     *
     * @param int $userId
     * @param int $readingMonthId
     * @return ClubReview|null
     */
    public function findByUserAndMonth(int $userId, int $readingMonthId): ?ClubReview
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->andWhere('r.readingMonth = :monthId')
            ->setParameter('userId', $userId)
            ->setParameter('monthId', $readingMonthId)
            ->getQuery()
            ->getOneOrNullResult();
    }

     /**
     * Récupère les derniers avis d'un club
     *
     * @param Club $club
     * @param int $limit
     * @return ClubReview[]
     */
    public function findLastReviewsByClub(Club $club, int $limit = 3)
    {
        return $this->createQueryBuilder('r')
            ->join('r.readingMonth', 'm')
            ->andWhere('m.club = :club')
            ->setParameter('club', $club)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
