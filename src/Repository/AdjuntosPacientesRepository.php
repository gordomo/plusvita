<?php

namespace App\Repository;

use App\Entity\AdjuntosPacientes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdjuntosPacientes|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdjuntosPacientes|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdjuntosPacientes[]    findAll()
 * @method AdjuntosPacientes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdjuntosPacientesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdjuntosPacientes::class);
    }

    // /**
    //  * @return AdjuntosPacientes[] Returns an array of AdjuntosPacientes objects
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
    public function findOneBySomeField($value): ?AdjuntosPacientes
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
