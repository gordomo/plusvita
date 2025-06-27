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
}
