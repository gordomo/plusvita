<?php

namespace App\Repository;

use App\Entity\Nurse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Nurse|null find($id, $lockMode = null, $lockVersion = null)
 * @method Nurse|null findOneBy(array $criteria, array $orderBy = null)
 * @method Nurse[]    findAll()
 * @method Nurse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NurseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nurse::class);
    }

    /**
     * Find nurses by role
     */
    public function findByRole(string $roleName): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.roles', 'r')
            ->where('r.name = :roleName')
            ->andWhere('r.isActive = :active')
            ->andWhere('n.habilitado = :enabled')
            ->setParameter('roleName', $roleName)
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('n.apellido', 'ASC')
            ->addOrderBy('n.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active nurses
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.habilitado = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('n.apellido', 'ASC')
            ->addOrderBy('n.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find nurses by permission
     */
    public function findByPermission(string $permissionName): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.roles', 'r')
            ->join('r.permissions', 'p')
            ->where('p.name = :permissionName')
            ->andWhere('r.isActive = :roleActive')
            ->andWhere('p.isActive = :permissionActive')
            ->andWhere('n.habilitado = :enabled')
            ->setParameter('permissionName', $permissionName)
            ->setParameter('roleActive', true)
            ->setParameter('permissionActive', true)
            ->setParameter('enabled', true)
            ->orderBy('n.apellido', 'ASC')
            ->addOrderBy('n.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search nurses by name or email
     */
    public function search(string $term): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.nombre LIKE :term')
            ->orWhere('n.apellido LIKE :term')
            ->orWhere('n.email LIKE :term')
            ->orWhere('n.legajo LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('n.apellido', 'ASC')
            ->addOrderBy('n.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
