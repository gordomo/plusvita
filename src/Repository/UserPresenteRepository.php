<?php

namespace App\Repository;

use App\Entity\UserPresente;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPresente>
 */
class UserPresenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPresente::class);
    }

    /**
     * Check if a user has a presence record for the given date (default today)
     */
    public function hasPresente(User $user, ?\DateTimeInterface $date = null): bool
    {
        $date = $date ?? new \DateTime('today');

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.fecha = :fecha')
            ->andWhere('p.valor = true')
            ->setParameter('user', $user)
            ->setParameter('fecha', $date->format('Y-m-d'));

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }
}
