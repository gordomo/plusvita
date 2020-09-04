<?php

namespace App\Repository;

use App\Entity\FamiliarExtra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FamiliarExtra|null find($id, $lockMode = null, $lockVersion = null)
 * @method FamiliarExtra|null findOneBy(array $criteria, array $orderBy = null)
 * @method FamiliarExtra[]    findAll()
 * @method FamiliarExtra[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FamiliarExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FamiliarExtra::class);
    }

    // /**
    //  * @return FamiliarExtra[] Returns an array of FamiliarExtra objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FamiliarExtra
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
