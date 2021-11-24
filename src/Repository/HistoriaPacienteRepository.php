<?php

namespace App\Repository;

use App\Entity\HistoriaPaciente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HistoriaPaciente|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriaPaciente|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriaPaciente[]    findAll()
 * @method HistoriaPaciente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriaPacienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriaPaciente::class);
    }

    // /**
    //  * @return HistoriaPaciente[] Returns an array of HistoriaPaciente objects
    //  */

    public function getHistorialDesdeHastaOcupandoHabitacion($from, $to)
    {
        $query = $this->createQueryBuilder('h')->select('h.id, h.fecha, identity(h.cliente) as cliente, h.habitacion, h.cama')
            ->andWhere('h.cliente is not null');
        if (!empty($from)) {
            $query->andWhere('h.fecha >= :from')
                ->setParameter('from', $from);
        }
        if (!empty($to)) {
            $query->andWhere('h.fecha <= :to')
                ->setParameter('to', $to);
        }
        $query->andWhere('h.habitacion IS NOT NULL')
            ->andWhere("h.habitacion <> ''")
            ->orderBy('h.fecha, h.habitacion, h.cama, h.cliente', 'ASC')
        ;

        return $query->getQuery()->getResult();
    }


    /*
    public function findOneBySomeField($value): ?HistoriaPaciente
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
