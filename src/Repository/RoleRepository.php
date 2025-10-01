<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Role|null find($id, $lockMode = null, $lockVersion = null)
 * @method Role|null findOneBy(array $criteria, array $orderBy = null)
 * @method Role[]    findAll()
 * @method Role[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Find active roles
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system roles
     */
    public function findSystem(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isSystem = :system')
            ->setParameter('system', true)
            ->orderBy('r.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by name
     */
    public function findByName(string $name): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find roles with permissions
     */
    public function findWithPermissions(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.permissions', 'p')
            ->addSelect('p')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
