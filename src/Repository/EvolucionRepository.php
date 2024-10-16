<?php

namespace App\Repository;

use App\Entity\Evolucion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Evolucion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evolucion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evolucion[]    findAll()
 * @method Evolucion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvolucionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evolucion::class);
    }

    public function findByClienteYTipo($cliente, $tipo, $currentPage, $limit, $from, $to)
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.paciente = :paciente')
            ->setParameter('paciente', $cliente);
        if ( $tipo !== 0 && $tipo !== 'todos') {
            $query->andWhere('e.tipo = :tipo')
                ->setParameter('tipo', $tipo);
        }

        if (!empty($from)) {
            $query->andWhere('e.fecha >= :from')
                ->setParameter('from', $from);
        }

        if (!empty($to)) {
            $query->andWhere('e.fecha <= :to')
                ->setParameter('to', $to);
        }
        
        $query->orderBy('e.fecha', 'DESC');
        $paginator = $this->paginate($query, $currentPage, $limit);

        return array('paginator' => $paginator, 'query' => $query);

        

    }

    public function findByFechaYTipo($cliente, $tipo)
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.paciente = :paciente')
            ->setParameter('paciente', $cliente);
        if ( $tipo !== 0 && $tipo !== 'todos') {
            $query->andWhere('e.tipo = :tipo')
                ->setParameter('tipo', $tipo);
        }

        return $query->orderBy('e.fecha', 'ASC')->getQuery()->getResult();

    }

    public function findByFechaYDoctor($doctor, $fechaDesde, $fechaHasta)
    {
        $query = $this->createQueryBuilder('e')->where('e.user = :doctor')->setParameter('doctor', $doctor);
            if (!empty($fechaDesde)) {
                $fechaDesde->setTime(0,0,0);
                $query->andWhere('e.fecha >= :fechaDesde')->setParameter('fechaDesde', $fechaDesde);
            }
            if (!empty($fechaHasta)) {
                $fechaHasta->setTime(23,59,59);
                $query->andWhere('e.fecha <= :fechaHasta')->setParameter('fechaHasta', $fechaHasta);
            }

       return $query->orderBy('e.fecha', 'ASC')->getQuery()->getResult();

    }

    public function findByFechaDoctorYCliente($doctor, $cliente, $fechaDesde, $fechaHasta)
    {
        $query = $this->createQueryBuilder('e')->where('e.user = :doctor')->setParameter('doctor', $doctor);
            if (!empty($fechaDesde)) {
                $query->andWhere('e.fecha >= :fechaDesde')->setParameter('fechaDesde', $fechaDesde);
            }
            if (!empty($fechaHasta)) {
                $query->andWhere('e.fecha <= :fechaHasta')->setParameter('fechaHasta', $fechaHasta);
            }
            if (!empty($cliente)) {
                $query->andWhere('e.paciente in (:cliente)')->setParameter('cliente', $cliente);
            }

       return $query->orderBy('e.fecha', 'ASC')->getQuery()->getResult();

    }

    public function findByFechaYCliente($cliente, $fechaDesde, $fechaHasta)
    {
        $query = $this->createQueryBuilder('e')->where('e.paciente = :cliente')->setParameter('cliente', $cliente);


        if (!empty($fechaDesde)) {
            $fechaDesde = new \DateTime($fechaDesde);
            $fechaDesde->setTime(0,0,0);
            $query->andWhere('e.fecha >= :fechaDesde')->setParameter('fechaDesde', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $fechaHasta = new \DateTime($fechaHasta);
            $fechaHasta->setTime(23,59,59);
            $query->andWhere('e.fecha <= :fechaHasta')->setParameter('fechaHasta', $fechaHasta);
        }

        return $query->orderBy('e.fecha', 'ASC')->getQuery()->getResult();

    }

    public function findByFechaClienteYtipos($cliente, $fechaDesde, $fechaHasta, $tipos = 'todos', $prof = null)
    {
        $query = $this->createQueryBuilder('e')->where('e.paciente = :cliente')->setParameter('cliente', $cliente);


        if (!empty($fechaDesde)) {
            $query->andWhere('e.fecha >= :fechaDesde')->setParameter('fechaDesde', $fechaDesde);
        }
        if (!empty($fechaHasta)) {
            $query->andWhere('e.fecha <= :fechaHasta')->setParameter('fechaHasta', $fechaHasta);
        }
        if ( !empty($tipos) ) {
            $query->andWhere('e.tipo IN (:tipos)')->setParameter('tipos', $tipos);
        }

        return $query->orderBy('e.fecha, e.tipo', 'DESC')->getQuery()->getResult();

    }

    public function paginate($dql, $page = 1, $limit = 3)
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }

    

    // /**
    //  * @return Evolucion[] Returns an array of Evolucion objects
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
    public function findOneBySomeField($value): ?Evolucion
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
