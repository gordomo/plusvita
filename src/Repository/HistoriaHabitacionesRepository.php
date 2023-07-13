<?php

namespace App\Repository;

use App\Entity\HistoriaHabitaciones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HistoriaHabitaciones|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriaHabitaciones|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriaHabitaciones[]    findAll()
 * @method HistoriaHabitaciones[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriaHabitacionesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriaHabitaciones::class);
    }

    public function findByDate($from, $to)
    {
        $query = $this->createQueryBuilder('h');
        if (!empty($from)) {
            $query->andWhere('h.fecha >= :from')
                ->setParameter('from', $from);
        }
        if (!empty($to)) {
            $query->andWhere('h.fecha <= :to')
                ->setParameter('to', $to);
        }
        $query->orderBy('h.cliente, h.fecha, h.habitacion, h.nCama', 'ASC');

        return $query->getQuery()->getResult();
    }
    public function countByDate($date)
    {
        $query = $this->createQueryBuilder('h');
        $query->select('count(h.id)')
                ->andWhere('h.fecha = :date')
                ->setParameter('date', $date);

        return $query->getQuery()->getSingleScalarResult();
    }

    public function getClienteIdFromHistHabitacion($from, $to)
    {
        $query = $this->createQueryBuilder('h')->select('identity(h.cliente) as cliente_id, IDENTITY(h.habitacion), h.nCama');
        if (!empty($from)) {
            $query->andWhere('h.fecha >= :from')
                ->setParameter('from', $from);
        }
        if (!empty($to)) {
            $query->andWhere('h.fecha <= :to')
                ->setParameter('to', $to);
        }
        $query->groupBy('h.cliente, h.habitacion, h.nCama');
        $query->orderBy('h.cliente');

        $ar = $query->getQuery()->getResult();

        return(array_unique(array_column($ar, "cliente_id")));
    }

    // /**
    //  * @return HistoriaHabitaciones[] Returns an array of HistoriaHabitaciones objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HistoriaHabitaciones
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
