<?php

namespace App\Repository;

use App\Entity\ClubMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubMessage>
 *
 * @method ClubMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubMessage[]    findAll()
 * @method ClubMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubMessage::class);
    }

    /**
     * Récupère les messages d'un mois de lecture donné triés par date de création
     *
     * @param int $readingMonthId
     * @return ClubMessage[]
     */
    public function findByReadingMonthOrdered(int $readingMonthId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.readingMonth = :monthId')
            ->setParameter('monthId', $readingMonthId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Optionnel : récupère les derniers messages pour un mois donné
     */
    public function findLastMessagesByReadingMonth(int $readingMonthId, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.readingMonth = :monthId')
            ->setParameter('monthId', $readingMonthId)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
