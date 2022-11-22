<?php

namespace App\Repository;

use App\Entity\Prescripcion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Prescripcion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prescripcion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prescripcion[]    findAll()
 * @method Prescripcion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescripcionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prescripcion::class);
    }

    // /**
    //  * @return Prescripcion[] Returns an array of Prescripcion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Prescripcion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
