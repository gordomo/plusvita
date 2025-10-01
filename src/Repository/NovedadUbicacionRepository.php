<?php

namespace App\Repository;

use App\Entity\NovedadUbicacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NovedadUbicacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method NovedadUbicacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method NovedadUbicacion[]    findAll()
 * @method NovedadUbicacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NovedadUbicacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NovedadUbicacion::class);
    }

    public function findByUbicacion($ubicacionId)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ubicacion = :ubicacion')
            ->setParameter('ubicacion', $ubicacionId)
            ->orderBy('n.fecha_creacion', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEstado($estado)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.estado = :estado')
            ->setParameter('estado', $estado)
            ->orderBy('n.fecha_creacion', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPrioridad($prioridad)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.prioridad = :prioridad')
            ->setParameter('prioridad', $prioridad)
            ->orderBy('n.fecha_creacion', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
