<?php

namespace App\Repository;

use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findActivos($value, $nombre, $hab = null, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val')->setParameter('val', $value)
            ->orWhere('c.fEgreso IS NULL');
        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }
        $query
            ->andWhere('c.derivado = 0')
            ->orWhere('c.derivado is null')
            ->andWhere('c.dePermiso = 0')
            ->orWhere('c.dePermiso is null')
            ->andWhere('c.ambulatorio = 0')
            ->orWhere('c.ambulatorio is null')
            ->andWhere('c.habitacion is not null');
            if($hab != null) {
                $query->andWhere('c.habitacion = :hab')->setParameter('hab',$hab);
            }

        if ( $orderBy ) {
            $query = $query->orderBy('c.'.$orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }

        if ( $os ) {
            $query->andWhere('c.obraSocial = :os')->setParameter("os", $os);
        }

        return $query->getQuery()->getResult();
    }

    // modalidad 1 es ambulatorio
    public function findActivosDesdeHasta($from, $to, $nombre, $estado, $obraSocial)
    {
        $query = $this->createQueryBuilder('c');
        if (!empty($from)){
            $query->andWhere('c.fIngreso >= :from')->setParameter('from', $from);
        }
        if (!empty($to)){
            $query->andWhere('c.fEgreso <= :to')->setParameter('to', $to);
        }          
        $query->orWhere('c.fEgreso IS NULL');

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }

        if ( $obraSocial ) {
            $query->andWhere('c.obraSocial = :os')->setParameter("os", $obraSocial);
        }
        $query = $query->orderBy('c.hClinica', 'DESC');


        return $query->getQuery()->getResult();
    }

    public function findByNombreYobraSocial($nombre = null, $oSocial = null)
    {
        $query = $this->createQueryBuilder('c');
        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }
        if($oSocial != null && $oSocial != 0) {
            $query->andWhere('c.obraSocial = :oSocial')->setParameter('oSocial',$oSocial);
        }
        return $query
            ->orderBy('c.hClinica', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findDerivados($value, $nombre, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val or c.fEgreso IS NULL')->setParameter('val', $value);

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }

        if ( $orderBy ) {
            $query = $query->orderBy('c.'.$orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }

        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }

        return $query->andWhere('c.derivado = 1')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findDePermiso($value, $nombre, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val')->setParameter('val', $value)
            ->orWhere('c.fEgreso IS NULL');
        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }
        if ( $orderBy ) {
            $query = $query->orderBy('c.'.$orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }
        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }
        return $query->andWhere('c.dePermiso = 1')->getQuery()->getResult();
    }

    public function findAmbulatorios($value, $nombre, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val or c.fEgreso IS NULL')->setParameter('val', $value);

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }
        if ( $orderBy ) {
            $query = $query->orderBy('c.'.$orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }
        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }
        return $query->andWhere('c.ambulatorio = 1 or (c.habitacion is null and c.derivado != 1)')->getQuery()->getResult();
    }

    public function findInActivos($value, $nombre, $currentPage, $limit, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso <= :val')->setParameter('val', $value);
            if ( $nombre != '' ) {
                $arrayNombres = explode(' ', $nombre);
                $i = 1;
                foreach ( $arrayNombres as $nombre ) {
                    $where = "c.nombre like :nombre$i OR c.apellido like :nombre$i";
                    if ($i == 1) {
                        $query->andWhere($where)->setParameter("nombre$i",'%'. $nombre .'%');
                    } else {
                        $query->orWhere($where)->setParameter("nombre$i",'%'. $nombre .'%');
                    }
                    $i ++;
                }
            }
            if ( $orderBy ) {
                $query = $query->orderBy('c.'.$orderBy, 'ASC');
            } else {
                $query = $query->orderBy('c.hClinica', 'ASC');
            }

            if ( $os ) {
                $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
            }
            
            $paginator = $this->paginate($query, $currentPage, $limit);
            return array('paginator' => $paginator, 'query' => $query);
            //return $query->getQuery()->getResult();
    }


    public function findInActivosOcupandoCama() {
        $hoy = new \DateTime();
        return $this->createQueryBuilder('c')
            //'c.fEgreso != null && c.fEgreso <= :hoy) and (c.nCama != null and c.nCama != 0) and (c.habitacion != null and c.habitacion != 0)'
            ->andWhere('(c.fEgreso is not null and c.fEgreso <= :hoy) and ((c.nCama is not null and c.nCama != 0) or (c.habitacion is not null and c.habitacion != 0))')
            ->setParameter(':hoy', $hoy)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findClienteEnHabitacion($habitacion) {
        $hoy = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere('(c.fEgreso is null or c.fEgreso >= :hoy) and (c.habitacion is not null and c.habitacion = :habitacion)')
            ->setParameter(':hoy', $hoy)
            ->setParameter(':habitacion', $habitacion->getId())
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

    public function findClienteConHabitacion()
    {
        return $this
            ->createQueryBuilder("c")
            ->where("c.habitacion IS NOT NULL")
            ->orderBy("c.habitacion", "DESC")
            ->getQuery()->getResult();
    }

    public function findAllByName($nombre, $orderBy = null)
    {
        $query = $this->createQueryBuilder('c');

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }

        if ( $orderBy ) {
            $query = $query->orderBy('c.'.$orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }

        return $query->getQuery()->getResult();
    }

    public function findByNameDocReferentePaginado($clientesYaFiltrados = null, $nombre = null, $doc = null, $vto = null, $hc = null, $ob = null, $from = null, $to = null)
    {
        $entityManager = $this->getEntityManager();
        $query = $this->createQueryBuilder('c');
            
        if ( $doc ) {
            $query->leftJoin('c.docReferente', 'd')->andWhere('d.id = :doctorId')
                ->setParameter('doctorId', $doc);
        }
        if (!empty($vto)) {
            $query->andWhere('c.vtoSesiones <= :vto')->setParameter('vto', $vto);
        }
        if (!empty($hc)) {
            $query->andWhere('c.hClinica = :hc')->setParameter('hc', $hc);
        }
        if (!empty($ob)) {
            $query->andWhere('c.obraSocial = :ob')->setParameter('ob', $ob);
        }
        if (!empty($clientesYaFiltrados)) {
            $query->andWhere('c.id in (:ids)')->setParameter('ids', $clientesYaFiltrados);
        }
        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i ++;
            }
        }

        if (!empty($to)) {
            $query->andWhere('c.fEgreso >= :to or c.fEgreso is null')
                ->setParameter('to', $to);
        }

        return $query->getQuery()->getResult();

    }

    public function getPacienteConModalidadAntesDeFecha($fechaDesde, $fechaHasta, $modalidad, $nombre, $os, $clientesYaFiltrados) {
        $query = $this->createQueryBuilder('c');

        if ($modalidad) {
            if($modalidad == 2) {
                $query->leftJoin('c.historiaHabitaciones', 'hi')->andWhere('hi.cliente  = c.id');
                $query->andWhere('hi.fecha >= :desde');
                $query->andWhere('hi.fecha <= :hasta');
            } else {
                if ( !$os ) {
                    $query->leftJoin('c.historia', 'h')->andWhere('h.cliente  = c.id');
                }
                $query->andWhere('h.fecha <= :hasta');
                $query->andWhere(':desde = :desde');
                $query->andWhere('h.modalidad != 2');
                $query->andWhere('c.fIngreso <= :hasta');
                
                $query->andWhere($query->expr()->orX(
                    'c.fEgreso >= :desde',
                    'c.fEgreso is null'
                ));
            }

        } else {
            $query->andWhere('c.fIngreso <= :hasta');
            $query->andWhere($query->expr()->orX('c.fEgreso >= :desde','c.fEgreso is null'));
        }

        if (!empty($clientesYaFiltrados)) {
            $query->andWhere('c.id in (:ids)')->setParameter('ids', $clientesYaFiltrados);
        }
        
        $query->setParameter('desde', $fechaDesde);
        $query->setParameter('hasta', $fechaHasta);
        
        return $query->getQuery()->getResult();

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
