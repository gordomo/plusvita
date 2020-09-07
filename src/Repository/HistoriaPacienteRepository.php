<?php

namespace App\Repository;

use App\Entity\HistoriaPaciente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HistoriaPaciente|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriaPaciente|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriaPaciente[]    findAll()
 * @method HistoriaPaciente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriaPacienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriaPaciente::class);
    }

    // /**
    //  * @return HistoriaPaciente[] Returns an array of HistoriaPaciente objects
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
    public function findOneBySomeField($value): ?HistoriaPaciente
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
