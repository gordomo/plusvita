<?php

namespace App\Repository;

use App\Entity\ConsumiblesClientes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConsumiblesClientes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConsumiblesClientes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConsumiblesClientes[]    findAll()
 * @method ConsumiblesClientes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsumiblesClientesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsumiblesClientes::class);
    }

    // /**
    //  * @return Consumible[] Returns an array of Consumible objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findLastMes()
    {
        $now = new \DateTime();
        $mes = $now->modify("-1 month")->format('m');

        return $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findConsumibleMesAnteriorParaElCliente($id, $accion = null)
    {
        $now = new \DateTime();
        $mes = $now->modify("-1 month")->format('m');
        $year = $now->format('Y');
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->andWhere('c.year = :year')
            ->setParameter('year', $year)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $id);

        if ( $accion !== null ) {
            $query->andWhere('c.accion = :accion')
                ->setParameter('accion', $accion);
        }
            return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }

    public function findIndicacionesParaElCliente($id, $year = '', $mes = '')
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.mes is not null')
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $id);

        if (!empty($year)) {
            $query->andWhere('c.year = :year')
                ->setParameter('year', $year);
        }
        if (!empty($mes)) {
            $query->andWhere('c.mes = :mes')
                ->setParameter('mes', $mes);
        }

        $query->andWhere('c.accion = :accion')
                ->setParameter('accion', '0');

        return $query->orderBy('c.year', 'DESC')->getQuery()->getResult();
    }

    public function findImputacionesMesConsumibleCliente($mes, $consumibleId, $cid, $year)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->andWhere('c.year = :year')
            ->setParameter('year', $year)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $cid)
            ->andWhere('c.consumibleId = :consumibleId')
            ->setParameter('consumibleId', $consumibleId)
            ->andWhere('c.accion = :accion')
            ->setParameter('accion', '1');

        return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }

    public function findImputacionesFechaConsumible($fecha, $cid)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $cid)
            ->andWhere('c.accion = :accion')
            ->setParameter('accion', '1');

        return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }

    public function findByAccionAndClientId($id, $mes, $fecha, $accion = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :id')
            ->setParameter('id', $id);

        if ($accion !== null) {
            $query->andWhere('c.accion = :accion')->setParameter('accion', $accion);
        }
        if ($mes) {
            $query->andWhere('c.mes = :mes')->setParameter('mes', $mes);
        }
        if ($fecha) {
            $query->andWhere('c.fecha = :fecha')->setParameter('fecha', $fecha);
        }


        //$query->groupBy('c.consumibleId');
        $query->orderBy('c.consumibleId', ' desc');

        return $query->getQuery()->getResult();
    }

}
