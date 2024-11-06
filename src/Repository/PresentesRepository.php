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

    public function getPresentes($ids, $startDate, $endDate)
    {
        // Asegúrate de que el formato de las fechas sea correcto
        return $this->createQueryBuilder('p')
            ->andWhere('p.fecha >= :startDate') // Filtra por la fecha de inicio
            ->andWhere('p.fecha <= :endDate')   // Filtra por la fecha de fin
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00')) // Establece la fecha de inicio
            ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'))      // Establece la fecha de fin
            ->andWhere('p.paciente IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.fecha', 'ASC')
            ->getQuery()
            ->getResult();
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
