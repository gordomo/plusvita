<?php

namespace App\Repository;

use App\Entity\Evolucion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findByClienteYTipo($cliente, $tipo)
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

            $fechaDesde = \DateTime::createFromFormat("d/m/Y", $fechaDesde);
            $newDate = date("Y/m/d", strtotime($fechaDesde->format('Y/m/d')));
            $query->andWhere('e.fecha >= :fechaDesde')->setParameter('fechaDesde', $newDate);
        }
        if (!empty($fechaHasta)) {
            $fechaHasta = \DateTime::createFromFormat("d/m/Y", $fechaHasta);
            $fechaHasta->setTime(23,59,59);
            $newDate = date("Y/m/d", strtotime($fechaHasta->format('Y/m/d')));
            $query->andWhere('e.fecha <= :fechaHasta')->setParameter('fechaHasta', $newDate);
        }
        if ( !empty($tipos) ) {
            $query->andWhere('e.tipo IN (:tipos)')->setParameter('tipos', $tipos);
        }

        return $query->orderBy('e.fecha, e.tipo', 'DESC')->getQuery()->getResult();

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
