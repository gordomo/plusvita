<?php

namespace App\Repository;

use App\Entity\NotasTurno;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotasTurno|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotasTurno|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotasTurno[]    findAll()
 * @method NotasTurno[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotasTurnoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotasTurno::class);
    }

    // /**
    //  * @return NotasTurno[] Returns an array of NotasTurno objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NotasTurno
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
