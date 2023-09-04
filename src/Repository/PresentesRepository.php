<?php

namespace App\Repository;

use App\Entity\Presentes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Presentes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Presentes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Presentes[]    findAll()
 * @method Presentes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresentesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presentes::class);
    }

    public function findByFechaCliente($fecha, $cliente)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->andWhere('p.paciente = :paciente')
            ->setParameter('paciente', $cliente)
            ->orderBy('p.fecha', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Presentes[] Returns an array of Presentes objects
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
    public function findOneBySomeField($value): ?Presentes
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
