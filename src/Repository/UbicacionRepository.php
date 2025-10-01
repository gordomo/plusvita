<?php

namespace App\Repository;

use App\Entity\Ubicacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ubicacion>
 *
 * @method Ubicacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ubicacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ubicacion[]    findAll()
 * @method Ubicacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UbicacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ubicacion::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Ubicacion $entity, bool $flush = true): void
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
    public function remove(Ubicacion $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // UbicacionRepository.php
    public function findByTerm(string $term)
    {
        return $this->createQueryBuilder('u')
            ->where('u.nombre LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca ubicaciones por nombre y descripción
     */
    public function searchUbicaciones(string $searchTerm): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nombre LIKE :searchTerm')
            ->orWhere('u.descripcion LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('u.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }


    // /**
    //  * @return Ubicacion[] Returns an array of Ubicacion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Ubicacion
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
