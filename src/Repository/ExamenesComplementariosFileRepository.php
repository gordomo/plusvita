<?php

namespace App\Repository;

use App\Entity\ExamenesComplementariosFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExamenesComplementariosFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExamenesComplementariosFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExamenesComplementariosFile[]    findAll()
 * @method ExamenesComplementariosFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExamenesComplementariosFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamenesComplementariosFile::class);
    }

    // /**
    //  * @return ExamenesComplementariosFile[] Returns an array of ExamenesComplementariosFile objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExamenesComplementariosFile
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
