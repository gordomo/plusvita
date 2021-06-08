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
        $default = new \DateTime();
        $defaultDesde = $default->modify('first day of this month')->format('Y-m-d');
        $defaultHasta = $default->modify('last day of this month')->format('Y-m-d');

        return $this->createQueryBuilder('c')
            ->andWhere('c.fecha >= :defaultDesde')
            ->setParameter('defaultDesde', $defaultDesde)
            ->andWhere('c.fecha <= :defaultHasta')
            ->setParameter('defaultHasta', $defaultHasta)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByAccionAndClientId($id, $desde, $hasta, $fecha, $accion = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :id')
            ->setParameter('id', $id);

        if ($accion !== null) {
            $query->andWhere('c.accion = :accion')->setParameter('accion', $accion);
        }

        if ($desde) {
            $desde = new \DateTime($desde);
            $query->andWhere('c.desde >= :desde')->setParameter('desde', $desde);
        }
        if ($hasta) {
            $hasta = new \DateTime($hasta);
            $query->andWhere('c.hasta <= :hasta')->setParameter('hasta', $hasta);
        }
        if ($fecha) {
            $query->andWhere('c.fecha = :fecha')->setParameter('fecha', $fecha);
        }


        //$query->groupBy('c.consumibleId');
        $query->orderBy('c.consumibleId', ' desc');

        return $query->getQuery()->getResult();
    }

}
