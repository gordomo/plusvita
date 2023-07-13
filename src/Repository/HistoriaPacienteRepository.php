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

    public function getHistorialDesdeHasta($cliente, $from, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.cliente = :cliente')
            ->setParameter('cliente', $cliente);
        if (!empty($from)) {
            $fechaDesde = \DateTime::createFromFormat("d/m/Y", $from);
            $newDate = date("Y/m/d", strtotime($fechaDesde->format('Y/m/d')));
            $query->andWhere('h.fecha >= :fechaDesde')->setParameter('fechaDesde', $newDate);
        }
        if (!empty($to)) {
            $fechaHasta = \DateTime::createFromFormat("d/m/Y", $to);
            $fechaHasta->setTime(23,59,59);
            $newDate = date("Y/m/d", strtotime($fechaHasta->format('Y/m/d')));
            $query->andWhere('h.fecha <= :fechaHasta')->setParameter('fechaHasta', $newDate);
        }
        $query->orderBy('h.fecha', 'ASC');

        return $query->getQuery()->getResult();
    }

    public function getLastHistorialConModalidad($clientes, $from, $to, $modalidad, $vto)
    {
        $query = $this->createQueryBuilder('h');
        if (!empty($from)) {
            $from->setTime(00,00,00);
            $query->andWhere('h.fecha >= :fechaDesde')->setParameter('fechaDesde', $from);
        }
        if (!empty($to)) {
            $to->setTime(23,59,59);
            $query->andWhere('h.fecha <= :fechaHasta')->setParameter('fechaHasta', $to);
        }
        if (!empty($modalidad)) {
            $query->andWhere('h.modalidad = :modalidad')->setParameter('modalidad', $modalidad);
        }

        if (!empty($clientes)) {
            $query->andWhere('h.cliente in (:ids)')->setParameter('ids', $clientes);
        }
        $query->orderBy('h.cliente, h.fecha', 'ASC');

        return $query->getQuery()->getResult();
    }

    public function findFromTo($from, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha >= :from')->setParameter('from', $from)
            ->andWhere('h.fecha <= :to')->setParameter('to', $to);

            return $query->getQuery()->getResult();
    }
    public function findLastModalidadChange($clienteId, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $clienteId)
            ->orderBy('h.fecha', 'DESC')
            ->setMaxResults(1);

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
