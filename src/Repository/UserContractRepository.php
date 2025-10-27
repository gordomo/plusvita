<?php

namespace App\Repository;

use App\Entity\UserContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserContract|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserContract|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserContract[]    findAll()
 * @method UserContract[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserContract::class);
    }

    /**
     * Encuentra el contrato activo de un usuario
     */
    public function findActiveByUser($userId): ?UserContract
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.user = :userId')
            ->andWhere('uc.isActive = 1')
            ->setParameter('userId', $userId)
            ->orderBy('uc.inicioContrato', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Encuentra todos los contratos de un usuario (histórico)
     */
    public function findByUser($userId)
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('uc.inicioContrato', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra contratos que vencen este mes
     */
    public function findExpiringThisMonth()
    {
        $now = new \DateTime();
        $firstDay = new \DateTime('first day of this month');
        $lastDay = new \DateTime('last day of this month');

        return $this->createQueryBuilder('uc')
            ->where('uc.vtoContrato BETWEEN :firstDay AND :lastDay')
            ->andWhere('uc.isActive = 1')
            ->setParameter('firstDay', $firstDay)
            ->setParameter('lastDay', $lastDay)
            ->orderBy('uc.vtoContrato', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra contratos vencidos
     */
    public function findExpired()
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('uc')
            ->where('uc.vtoContrato < :now')
            ->andWhere('uc.isActive = 1')
            ->setParameter('now', $now)
            ->orderBy('uc.vtoContrato', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra contratos por tipo
     */
    public function findByTipo($tipo)
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.tipo = :tipo')
            ->andWhere('uc.isActive = 1')
            ->setParameter('tipo', $tipo)
            ->orderBy('uc.user', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Desactiva todos los contratos activos de un usuario (para renovaciones)
     */
    public function deactivateUserContracts($userId): void
    {
        $this->createQueryBuilder('uc')
            ->update()
            ->set('uc.isActive', '0')
            ->set('uc.updatedAt', ':now')
            ->where('uc.user = :userId')
            ->andWhere('uc.isActive = 1')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
