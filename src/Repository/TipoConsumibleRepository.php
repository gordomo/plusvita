<?php

namespace App\Repository;

use App\Entity\TipoConsumible;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TipoConsumible|null find($id, $lockMode = null, $lockVersion = null)
 * @method TipoConsumible|null findOneBy(array $criteria, array $orderBy = null)
 * @method TipoConsumible[]    findAll()
 * @method TipoConsumible[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoConsumibleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoConsumible::class);
    }

    // /**
    //  * @return TipoConsumible[] Returns an array of TipoConsumible objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TipoConsumible
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
