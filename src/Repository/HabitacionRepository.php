<?php

namespace App\Repository;

use App\Entity\Habitacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Habitacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Habitacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Habitacion[]    findAll()
 * @method Habitacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HabitacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitacion::class);
    }

    public function findAllInNameOrder()
    {
        return $this->createQueryBuilder('q')
                    ->addSelect('ABS(q.nombre) AS HIDDEN foo')
                    ->orderBy('foo')
                    ->getQuery()
                    ->getResult();
    }

    // public function findHabitacionConCamasDisponibles()
    // {
    //     $resp = [];
    //     $todas = $this->findAll();
    //     foreach ($todas as $habitacion) {
    //         $arrayCamas = $habitacion->getCamasOcupadas();

    //         if ($habitacion->getCamasDisponibles() > count($arrayCamas)) {
    //            $resp[]  = $habitacion;

    //         }
    //     }
    //     return $resp;

    // }

    public function findHabitacionConCamasDisponibles($clienteRepository)
    {
        $resp = [];
        $todas = $this->findBy(array(), array('nombre' => 'ASC'));
        foreach ($todas as $habitacion) {
            $totalCamas = $habitacion->getCamasDisponibles();

            $cli = $clienteRepository->findClienteEnHabitacion($habitacion);
            if (count($cli) < $totalCamas) {
                $resp[]  = $habitacion;
            }
        }
        return $resp;

    }

    public function findHabitacionSinCamasDisponibles()
    {
        $resp = [];
        $todas = $this->findBy(array(), array('nombre' => 'ASC'));
        foreach ($todas as $habitacion) {
            $arrayCamas = $habitacion->getCamasOcupadas();

            if ($habitacion->getCamasDisponibles() == count($arrayCamas)) {
                $resp[]  = $habitacion;

            }
        }
        return $resp;

    }

    public function getCamasDisp(int $id)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function getHabitacionesConPacientes()
    {
        $resp = [];
        $todas = $this->findAll();
        foreach ($todas as $habitacion) {
            $arrayCamas = $habitacion->getCamasOcupadas();

            if ($arrayCamas) {
                $resp[]  = $habitacion;
            }
        }
        return $resp;
    }

    // /**
    //  * @return Habitacion[] Returns an array of Habitacion objects
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
    public function findOneBySomeField($value): ?Habitacion
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
