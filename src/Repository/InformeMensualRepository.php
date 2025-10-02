<?php

namespace App\Repository;

use App\Entity\InformeMensual;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InformeMensual>
 */
class InformeMensualRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformeMensual::class);
    }

    public function save(InformeMensual $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InformeMensual $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra informes mensuales de un cliente específico
     */
    public function findByCliente($cliente)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.cliente = :cliente')
            ->setParameter('cliente', $cliente)
            ->orderBy('i.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra informes mensuales según varios criterios de filtrado
     */
    public function findByFilters($pacienteId = null, $doctorId = null, \DateTime $fechaDesde = null, \DateTime $fechaHasta = null, $userEmail = null, $canManage = false)
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.doctor', 'd')
            ->orderBy('i.fechaCreacion', 'DESC');

        // Si el usuario no puede gestionar todos los informes, filtrar por su email
        if (!$canManage && $userEmail) {
            $qb->andWhere('d.email = :userEmail')
               ->setParameter('userEmail', $userEmail);
        }
        
        if ($pacienteId) {
            $qb->andWhere('i.cliente = :paciente')
               ->setParameter('paciente', $pacienteId);
        }
        
        if ($doctorId) {
            $qb->andWhere('i.doctor = :doctor')
               ->setParameter('doctor', $doctorId);
        }
        
        if ($fechaDesde) {
            $qb->andWhere('i.fechaCreacion >= :fechaDesde')
               ->setParameter('fechaDesde', $fechaDesde->format('Y-m-d').' 00:00:00');
        }
        
        if ($fechaHasta) {
            $qb->andWhere('i.fechaCreacion <= :fechaHasta')
               ->setParameter('fechaHasta', $fechaHasta->format('Y-m-d').' 23:59:59');
        }
        
        return $qb->getQuery()->getResult();
    }
}
