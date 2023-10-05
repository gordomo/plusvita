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
        $query->orderBy('h.fecha, h.id', 'ASC');

        return $query->getQuery()->getResult();
    }
    public function getLastHistoriaAnterior($id, $cliente) {
        $query = $this->createQueryBuilder('h');
        $query->andWhere('h.id < :id')
        ->andWhere('h.cliente = :cliente')
        ->setParameter('id', $id)
        ->setParameter('cliente', $cliente)
        ->orderBy('h.id', 'DESC')
        ->setMaxResults(1);
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getLastHistoriaPacienteHasta($to, $cliente) {
        $query = $this->createQueryBuilder('h');
        $query->andWhere('h.fechaEngreso >= :to or h.fechaEngreso is null');
        $query->andWhere('h.fecha <= :to')->setParameter('to', $to);
        $query->andWhere('h.cliente = :cliente')->setParameter('cliente', $cliente);
        $query->setMaxResults(1);
        $query->orderBy('h.fecha', 'DESC');
        return $query->getQuery()->getResult();
    }

    public function getPacienteConModalidadAntesDeFecha($desde, $hasta, $modalidad)
    {
        $query = $this->createQueryBuilder('h');
        $query->select('identity(h.cliente) as cliente')->andWhere('h.cliente is not null');
        $query->leftJoin('h.cliente', 'c');
        
        if (!empty($desde)) {
            $query->andWhere('h.fechaEngreso >= :desde or h.fechaEngreso is null');
            $query->andWhere('c.fEgreso >= :desde or c.fEgreso is null')->setParameter('desde', $desde);
        }
        if (!empty($hasta)) {
            $query->andWhere('h.fechaIngreso <= :hasta or h.fechaIngreso is null');
            $query->andWhere('c.fIngreso <= :hasta or c.fIngreso is null');
            $query->andWhere('h.fecha <= :hasta')->setParameter('hasta', $hasta);
        }
        if (!empty($modalidad)) {
            if($modalidad == 1) {
                $query->andWhere('h.modalidad != :modalidad')->setParameter('modalidad', 2);
            } else {
                $query->andWhere('h.modalidad = :modalidad')->setParameter('modalidad', $modalidad);
            }
            
        }
        $query->groupBy('h.cliente');


        return $query->getQuery()->getResult();
    }

    public function findFromTo($from, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha >= :from')->setParameter('from', $from)
            ->andWhere('h.fecha <= :to')->setParameter('to', $to);

            return $query->getQuery()->getResult();
    }
    public function findFromToCliente($from, $to, $cliente)
    {
        if ( empty($from) ) {
            $from = '1999-01-01';
        }
        if ( empty($to) ) {
            $to = '9999-01-01';
        }

        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha >= :from')->setParameter('from', $from)
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $cliente);

            return $query->getQuery()->getResult();
    }
    public function findLastModalidadChange($clienteId, $to)
    {
        if ( empty($to) ) {
            $to = '9999-01-01';
        }
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $clienteId)
            ->orderBy('h.fecha', 'DESC')
            ->setMaxResults(1);

            return $query->getQuery()->getResult();
    }

    public function findLastChange($clienteId, $to)
    {
        if ( empty($to) ) {
            $to = '9999-01-01';
        }
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $clienteId)
            ->orderBy('h.fecha', 'DESC');

            return $query->getQuery()->getResult();
    }

    public function getHistoricoDesdeHasta($desde, $hasta, $nombre = null, $modalidad = 0, $obraSocial = null, $prof = null, $hc = null) {
        $query = $this->createQueryBuilder('h')->where('h.fecha <= :hasta')->setParameter('hasta', $hasta);
        $query->andWhere($query->expr()->orX('h.fechaFin >= :desde','h.fechaFin is null'))->setParameter('desde', $desde);

        if ( $modalidad ) {
            $query->andWhere('h.modalidad = :modalidad')->setParameter('modalidad', $modalidad);
        }
        if ( $obraSocial ) {
           $query->andWhere('h.obra_social = :obraSocial')->setParameter('obraSocial', $obraSocial);
        }
        
        if ( $prof ) {
            $prof = '%'.$prof.'%';
            $query->andWhere('h.docReferente like :prof')->setParameter('prof', $prof);
        }

        if ( $nombre || $hc) {
            $query->leftJoin('h.cliente', 'c');
            
            if ( $nombre ) {
                $i = 1;
                $arrayNombres = explode(' ', $nombre);
                foreach ( $arrayNombres as $nombre ) {
                    $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                    $i++;
                }
            }

            if ( $hc ) {
                $query->andWhere('c.hClinica = :hc')->setParameter('hc', $hc);
            }
        }

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
