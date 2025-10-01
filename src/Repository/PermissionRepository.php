<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Permission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permission[]    findAll()
 * @method Permission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * Find active permissions
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permissions by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('p.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permission by name
     */
    public function findByName(string $name): ?Permission
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system permissions
     */
    public function findSystem(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isSystem = :system')
            ->setParameter('system', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
