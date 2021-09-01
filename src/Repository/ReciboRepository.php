<?php

namespace App\Repository;

use App\Entity\Recibo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Recibo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recibo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recibo[]    findAll()
 * @method Recibo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReciboRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recibo::class);
    }

    // /**
    //  * @return Recibo[] Returns an array of Recibo objects
    //  */

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getCountByType($type)
    {
        $qb = $this->createQueryBuilder('r');
        return $qb
            ->select('count(r.id)')
            ->where('r.tipo = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findReciboImputacionCliente($cid)
    {
        return $this->createQueryBuilder('r')
            ->where('r.cid = :cid')
            ->setParameter('cid', $cid)
            ->andWhere('r.tipo = :tipo')
            ->setParameter('tipo', 'consumible')
            ->orderBy('r.fecha', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /*
    public function findOneBySomeField($value): ?Recibo
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
