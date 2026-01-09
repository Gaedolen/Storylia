<?php

namespace App\Repository;

use App\Entity\ReadingHistory;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingHistory>
 */
class ReadingHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingHistory::class);
    }

    public function getHistoriqueParUtilisateur(Utilisateur $utilisateur): array
    {
        $readings = $this->createQueryBuilder('r')
            ->leftJoin('r.book', 'b')
            ->addSelect('b')
            ->where('r.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('r.readingDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Regroupement par année / mois (pour ton template)
        $historique = [];

        foreach ($readings as $reading) {
            $date = $reading->getReadingDate();

            if (!$date) {
                continue;
            }

            $year = $date->format('Y');
            $month = $date->format('F');

            $historique[$year][$month][] = $reading;
        }

        return $historique;
    }

    //    /**
    //     * @return ReadingHistory[] Returns an array of ReadingHistory objects
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

    //    public function findOneBySomeField($value): ?ReadingHistory
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
