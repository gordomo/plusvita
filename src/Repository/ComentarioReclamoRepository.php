<?php

namespace App\Repository;

use App\Entity\ComentarioReclamo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComentarioReclamo>
 *
 * @method ComentarioReclamo|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComentarioReclamo|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComentarioReclamo[]    findAll()
 * @method ComentarioReclamo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComentarioReclamoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComentarioReclamo::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ComentarioReclamo $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ComentarioReclamo $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return ComentarioReclamo[] Returns an array of ComentarioReclamo objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ComentarioReclamo
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
