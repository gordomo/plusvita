<?php

namespace App\Repository;

use App\Entity\Reclamo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Reclamo>
 *
 * @method Reclamo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reclamo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reclamo[]    findAll()
 * @method Reclamo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReclamoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamo::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Reclamo $entity, bool $flush = true): void
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
    public function remove(Reclamo $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAllPaginados($tipo = null, $area = null, $estado = null, $currentPage = 1, $limit = 10, $desde = null, $hasta = null, $orderBy = null)
    {
        $query = $this->createQueryBuilder('r');
        
        if ( $tipo ) {
            $query = $query->andWhere('r.tipo = :tipo')->setParameter('tipo', $tipo);
        }
        if ( $estado ) {
            $query = $query->andWhere('r.estado = :estado')->setParameter('estado', $estado);
        }
        if ( $area ) {
            $query = $query->andWhere('r.area = :area')->setParameter('area', $area);
        }
        if ( $desde ) {
            $query = $query->andWhere('r.desde >= :desde')->setParameter('desde', $desde);
        }
        if ( $hasta ) {
            $query = $query->andWhere('r.hasta >= :hasta')->setParameter('hasta', $hasta);
        }



        if ( $orderBy ) {
            $query = $query->orderBy('r.'.$orderBy, 'desc');
        }

        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
    }

   
   

    public function paginate($dql, $page = 1, $limit = 3)
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }
}
