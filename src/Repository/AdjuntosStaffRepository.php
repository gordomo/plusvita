<?php

namespace App\Repository;

use App\Entity\AdjuntosStaff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdjuntosStaff|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdjuntosStaff|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdjuntosStaff[]    findAll()
 * @method AdjuntosStaff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdjuntosStaffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdjuntosStaff::class);
    }

    // /**
    //  * @return AdjuntosStaff[] Returns an array of AdjuntosStaff objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AdjuntosStaff
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
