<?php

namespace App\Repository;

use App\Entity\MailCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailCode[]    findAll()
 * @method MailCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailCode::class);
    }

    // /**
    //  * @return MailCode[] Returns an array of MailCode objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MailCode
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
