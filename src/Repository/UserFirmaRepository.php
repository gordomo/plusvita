<?php

namespace App\Repository;

use App\Entity\UserFirma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserFirma|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFirma|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFirma[]    findAll()
 * @method UserFirma[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFirmaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFirma::class);
    }

    /**
     * Find active signature for a user
     */
    public function findActiveByUser($userId)
    {
        return $this->createQueryBuilder('uf')
            ->andWhere('uf.user = :userId')
            ->andWhere('uf.isActive = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all signatures for a user
     */
    public function findByUser($userId)
    {
        return $this->createQueryBuilder('uf')
            ->andWhere('uf.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('uf.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
