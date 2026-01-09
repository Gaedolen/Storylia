<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Club>
 */
class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    public function findByName(?string $search)
    {
        $qb = $this->createQueryBuilder('c');

        if ($search) {
            $qb->where('LOWER(c.name) LIKE :q')
            ->setParameter('q', '%' . strtolower($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithAllFilters(?string $search = null, ?string $preference = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('c')
                ->leftJoin('c.membres', 'm')
                ->addSelect('m');

        // Filtre par nom de club
        if ($search) {
            $qb->andWhere('LOWER(c.name) LIKE :search')
            ->setParameter('search', '%'.strtolower($search).'%');
        }

        // Tri
        switch ($sort) {
            case 'recent':
                $qb->orderBy('c.creationDate', 'DESC');
                break;
            case 'old':
                $qb->orderBy('c.creationDate', 'ASC');
                break;
            case 'participants_max':
                $qb->orderBy('SIZE(c.membres)', 'DESC');
                break;
            case 'participants_min':
                $qb->orderBy('SIZE(c.membres)', 'ASC');
                break;
            default:
                $qb->orderBy('c.creationDate', 'DESC');
        }

        $clubs = $qb->getQuery()->getResult();

        // Filtre préférences en PHP
        if ($preference) {
            $clubs = array_filter($clubs, function($club) use ($preference) {
                return in_array($preference, $club->getPreferences());
            });
        }

        return $clubs;
    }

    public function findByMembre(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.membres', 'm')
            ->where('m = :user')
            ->setParameter('user', $utilisateur)
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Club[] Returns an array of Club objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Club
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
