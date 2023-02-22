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
            ->orWhere('c.ambulatorio is null');
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
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fIngreso >= :from')->setParameter('from', $from)
            ->andWhere('c.fEgreso <= :to')->setParameter('to', $to)
            ->orWhere('c.fEgreso IS NULL');

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
        return $query->andWhere('c.ambulatorio = 1')->getQuery()->getResult();
    }

    public function findInActivos($value, $nombre, $orderBy = null, $os = null)
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


            return $query->getQuery()->getResult();
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
}
