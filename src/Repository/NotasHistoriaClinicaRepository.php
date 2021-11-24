<?php

namespace App\Repository;

use App\Entity\NotasHistoriaClinica;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotasHistoriaClinica|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotasHistoriaClinica|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotasHistoriaClinica[]    findAll()
 * @method NotasHistoriaClinica[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotasHistoriaClinicaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotasHistoriaClinica::class);
    }

    // /**
    //  * @return NotasHistoriaClinica[] Returns an array of NotasHistoriaClinica objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NotasHistoriaClinica
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
