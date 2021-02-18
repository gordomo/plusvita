<?php

namespace App\Repository;

use App\Entity\Consumible;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Consumible|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consumible|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consumible[]    findAll()
 * @method Consumible[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsumibleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consumible::class);
    }

    // /**
    //  * @return Consumible[] Returns an array of Consumible objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Consumible
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
