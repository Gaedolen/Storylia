<?php

namespace App\Repository;

use App\Entity\Report;
use App\Entity\Utilisateur;
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

        public function findUserReportsEnCours(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.reported IS NOT NULL')
            ->andWhere('r.review IS NULL')
            ->andWhere('r.reportedClub IS NULL')
            ->andWhere('r.reportedBook IS NULL')
            ->setParameter('status', Report::STATUS_EN_COURS)
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countUserReportsEnCours(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->andWhere('r.reported IS NOT NULL')
            ->andWhere('r.review IS NULL')
            ->andWhere('r.reportedClub IS NULL')
            ->andWhere('r.reportedBook IS NULL')
            ->setParameter('status', Report::STATUS_EN_COURS)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findHistorique(?string $status = null, ?string $pseudo = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reported', 'reportedUser')
            ->addSelect('reportedUser')
            ->leftJoin('r.author', 'authorUser')
            ->addSelect('authorUser')

            // uniquement signalements utilisateurs
            ->andWhere('r.reported IS NOT NULL')
            ->andWhere('r.review IS NULL')
            ->andWhere('r.reportedClub IS NULL')
            ->andWhere('r.reportedBook IS NULL')

            // historique = pas en cours
            ->andWhere('r.status != :enCours')
            ->setParameter('enCours', Report::STATUS_EN_COURS)

            ->orderBy('r.date', 'DESC');

        if ($status) {
            $qb->andWhere('r.status = :status')
            ->setParameter('status', $status);
        }

        $reports = $qb->getQuery()->getResult();

        // Filtrage intelligent (Levenstein)
        if ($pseudo && mb_strlen($pseudo) >= 3) {
            $search = mb_strtolower($pseudo);

            $reports = array_filter($reports, function (Report $report) use ($search) {
                $names = [
                    $report->getReported()?->getPseudo(),
                    $report->getAuthor()?->getPseudo(),
                ];

                foreach ($names as $name) {
                    if (!$name) continue;

                    $name = mb_strtolower($name);

                    // correspondance approximative
                    if (
                        str_contains($name, $search) ||
                        levenshtein($name, $search) <= 3
                    ) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $reports;
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

    public function hasUserReported(Utilisateur $author, Utilisateur $reported): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.author = :author')
            ->andWhere('r.reported = :reported')
            ->setParameter('author', $author)
            ->setParameter('reported', $reported)
            ->getQuery()
            ->getSingleScalarResult();
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
