<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function findEnCours(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', Report::STATUS_EN_COURS)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHistorique(?string $status = null, ?string $pseudo = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reported', 'reportedUser') // jointure avec l'utilisateur signalé
            ->addSelect('reportedUser')
            ->leftJoin('r.author', 'authorUser')    // jointure avec l'auteur
            ->addSelect('authorUser')
            ->where('r.status != :enCours')
            ->setParameter('enCours', 'en_cours')
            ->orderBy('r.date', 'DESC');

        if ($status) {
            $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
        }

        if ($pseudo) {
            $qb->andWhere('reportedUser.pseudo LIKE :pseudo OR authorUser.pseudo LIKE :pseudo')
            ->setParameter('pseudo', '%' . $pseudo . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findHistoriqueClubs(?string $status, ?string $searchClub): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reportedClub', 'c')
            ->addSelect('c')
            ->leftJoin('r.author', 'a')
            ->addSelect('a')
            ->where('r.reportedClub IS NOT NULL');

        if ($status) {
            $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
        }

        if ($searchClub) {
            $qb->andWhere('c.name LIKE :club')
            ->setParameter('club', '%' . $searchClub . '%');
        }

        return $qb
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Report[] Returns an array of Report objects
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

    //    public function findOneBySomeField($value): ?Report
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
