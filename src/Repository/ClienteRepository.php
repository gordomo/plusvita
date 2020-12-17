<?php

namespace App\Repository;

use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Cliente|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cliente|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cliente[]    findAll()
 * @method Cliente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }

    public function findActivos($value, $nombre)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val')->setParameter('val', $value)
            ->orWhere('c.fEgreso IS NULL')
            ->andWhere('c.nombre like  :nombre OR c.apellido like :nombre')->setParameter('nombre','%'. $nombre .'%')
            ->orderBy('c.hClinica', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findDerivados($value, $nombre)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val')->setParameter('val', $value)
            ->orWhere('c.fEgreso IS NULL')
            ->andWhere('c.nombre like  :nombre OR c.apellido like :nombre')->setParameter('nombre','%'. $nombre .'%')
            ->andWhere('c.derivado = 1')
            ->orderBy('c.hClinica', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findInActivos($value, $nombre)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso <= :val')->setParameter('val', $value)
            ->andWhere('c.nombre like  :nombre OR c.apellido like  :nombre')->setParameter('nombre','%'. $nombre .'%')
            ->orderBy('c.hClinica', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllInactivos($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso <= :val')->setParameter('val', $value)
            ->orderBy('c.hClinica', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }
    public function findAllActivos($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val')->setParameter('val', $value)
            ->orWhere('c.fEgreso IS NULL')
            ->orderBy('c.id', 'ASC')
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findLastHClinica()
    {
        $cliente = $this
            ->createQueryBuilder("c")
            ->orderBy("c.hClinica", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if(!empty($cliente)) {
            return $cliente->getHClinica();
        } else {
            return null;
        }
    }
}
