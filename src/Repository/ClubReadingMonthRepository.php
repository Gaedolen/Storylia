<?php

namespace App\Repository;

use App\Entity\ClubReadingMonth;
use App\Entity\Club;
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
     * @param string $month Format "YYYY-MM"
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

    public function findCurrentMonthByClub(Club $club): ?ClubReadingMonth
    {
        // mois courant au format 'YYYY-MM'
        $currentMonth = date('Y-m');

        return $this->createQueryBuilder('crm')
            ->andWhere('crm.club = :club')
            ->andWhere('crm.month = :month')
            ->setParameter('club', $club)
            ->setParameter('month', $currentMonth)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextMonthByClub(Club $club): ?ClubReadingMonth
    {
        // calcul du mois suivant et du bon format 'YYYY-MM'
        $nextMonth = (int) date('m') + 1;
        $year = (int) date('Y');
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $year++;
        }
        $nextMonthString = sprintf('%04d-%02d', $year, $nextMonth);

        return $this->createQueryBuilder('crm')
            ->andWhere('crm.club = :club')
            ->andWhere('crm.month = :month')
            ->setParameter('club', $club)
            ->setParameter('month', $nextMonthString)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentBooks(): array
    {
        $crmList = $this->createQueryBuilder('crm')
            ->join('crm.book', 'b')
            ->addSelect('b')
            ->orderBy('crm.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Extraire juste les livres
        $books = array_map(fn($crm) => $crm->getBook(), $crmList);

        return $books;
    }
}
