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

    public function findActivosSinPag($value, $nombre, $hab = null, $orderBy = null, $os = null)
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
            // Incluir tanto pacientes normales como los de permiso
            // ->andWhere('c.dePermiso = 0')
            // ->orWhere('c.dePermiso is null')
            ->andWhere('c.ambulatorio = 0')
            ->orWhere('c.ambulatorio is null')
            // Incluir internados con habitación O internados sin habitación (inconsistencia a corregir)
            ->andWhere('(c.habitacion IS NOT NULL OR (c.modalidad = 2 AND (c.ambulatorio = 0 OR c.ambulatorio IS NULL)))');
            if($hab != null) {
                $query->andWhere('c.habitacion = :hab')->setParameter('hab',$hab);
            }

        // Actualizado para manejar array de ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.hClinica', 'ASC');
        }

        if ( $os ) {
            $query->andWhere('c.obraSocial = :os')->setParameter("os", $os);
        }

        return $query->getQuery()->getResult();
    }
                                        
    public function findActivos($value, $nombre, $currentPage, $limit, $hab = null, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c');
        
        // Agrupar condiciones principales con paréntesis
        $query->andWhere($query->expr()->orX(
            $query->expr()->gt('c.fEgreso', ':val'),
            $query->expr()->isNull('c.fEgreso')
        ))->setParameter('val', $value);
        
        // Agregar filtro de nombre si existe
        if ($nombre != '') {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ($arrayNombres as $nombre) {
                $query->andWhere($query->expr()->orX(
                    $query->expr()->like('c.nombre', ':nombre' . $i),
                    $query->expr()->like('c.apellido', ':nombre' . $i)
                ))->setParameter('nombre' . $i, '%' . $nombre . '%');
                $i++;
            }
        }
        
        // Agrupar condiciones de estado con paréntesis para la correcta lógica SQL
        // Incluir internados con habitación O internados sin habitación (inconsistencia a corregir)
        $query->andWhere($query->expr()->andX(
            $query->expr()->orX(
                $query->expr()->eq('c.derivado', 0),
                $query->expr()->isNull('c.derivado')
            ),
            $query->expr()->orX(
                $query->expr()->eq('c.dePermiso', 0),
                $query->expr()->isNull('c.dePermiso')
            ),
            $query->expr()->orX(
                $query->expr()->eq('c.ambulatorio', 0),
                $query->expr()->isNull('c.ambulatorio')
            ),
            $query->expr()->orX(
                $query->expr()->isNotNull('c.habitacion'),
                $query->expr()->andX(
                    $query->expr()->eq('c.modalidad', 2),
                    $query->expr()->orX(
                        $query->expr()->eq('c.ambulatorio', 0),
                        $query->expr()->isNull('c.ambulatorio')
                    )
                )
            )
        ));
        
        // Agregar filtro de habitación si existe
        if ($hab != null) {
            $query->andWhere('c.habitacion = :hab')->setParameter('hab', $hab);
        }

        // Agregar orden (modificado para manejar array de ordenamiento)
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.hClinica', 'ASC');
        }

        // Agregar filtro de obra social si existe
        if ($os) {
            $query->andWhere('c.obraSocial = :os')->setParameter("os", $os);
        }

        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
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
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
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
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
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

    public function findDerivados($value, $nombre, $currentPage, $limit, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val or c.fEgreso IS NULL')->setParameter('val', $value);

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
                $i ++;
            }
        }

        // Actualizado para manejar array de ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.hClinica', 'ASC');
        }

        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }

        $query->andWhere('c.derivado = 1');
        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
    }

    public function findDePermiso($value, $nombre, $currentPage, $limit, $orderBy = null, $os = null)
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
        
        // Actualizado para manejar array de ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.hClinica', 'ASC');
        }
        
        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }
        $query->andWhere('c.dePermiso = 1');

        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
    }

    public function findAmbulatorios($value, $nombre, $currentPage, $limit, $orderBy = null, $os = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso > :val or c.fEgreso IS NULL')->setParameter('val', $value);

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
                $i ++;
            }
        }
        
        // Actualizado para manejar array de ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.hClinica', 'ASC');
        }
        
        if ( $os ) {
            $query->orWhere('c.obraSocial = :os')->setParameter("os", $os);
        }
        // Solo incluir pacientes ambulatorios que NO tengan habitación asignada
        // Un paciente con habitación asignada debe aparecer como internado, no como ambulatorio
        $query->andWhere('c.habitacion IS NULL')
              ->andWhere('(c.ambulatorio = 1 OR c.modalidad = 1)')
              ->andWhere('(c.derivado != 1 OR c.derivado IS NULL)')
              ->andWhere('(c.dePermiso != 1 OR c.dePermiso IS NULL)');
        
        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
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
                        $query->andWhere($where)->setParameter("nombre$i",'%' . $nombre . '%');
                    } else {
                        $query->orWhere($where)->setParameter("nombre$i",'%' . $nombre . '%');
                    }
                    $i ++;
                }
            }
            // Actualizado para manejar array de ordenamiento
            if ($orderBy) {
                if (is_array($orderBy)) {
                    foreach ($orderBy as $field => $direction) {
                        $query->orderBy('c.' . $field, $direction);
                    }
                } else {
                    $query->orderBy('c.' . $orderBy, 'ASC');
                }
            } else {
                $query->orderBy('c.hClinica', 'ASC');
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

    public function findClienteEnHabitacion($habitacion, $soloConCamaFisica = true, $incluirPacientesDePermiso = false) {
        $hoy = new \DateTime();
        $query = $this->createQueryBuilder('c')
            ->andWhere('(c.fEgreso is null or c.fEgreso >= :hoy) and (c.habitacion is not null and c.habitacion = :habitacion)')
            ->setParameter(':hoy', $hoy)
            ->setParameter(':habitacion', $habitacion->getId());
            
        // Si se solicita, filtrar solo pacientes con cama física (nCama > 0)
        if ($soloConCamaFisica) {
            $query->andWhere('c.nCama > 0');
        }
        
        // Excluir a los pacientes de permiso si se indica
        if (!$incluirPacientesDePermiso) {
            $query->andWhere('c.dePermiso = 0 OR c.dePermiso IS NULL');
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

    public function findAllByName($nombre, $currentPage, $limit, $orderBy = null)
    {
        $query = $this->createQueryBuilder('c');

        if ( $nombre != '' ) {
            $arrayNombres = explode(' ', $nombre);
            $i = 1;
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
                $i ++;
            }
        }

        if ( $orderBy ) {
            $query = $query->orderBy('c.' . $orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
        }

        $paginator = $this->paginate($query, $currentPage, $limit);
        return array('paginator' => $paginator, 'query' => $query);
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
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%' . $nombre . '%');
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

    public function findClientesIngresadosEsteMes($primerDiaDelMes, $ultimoDiaDelMes, $paginar = false, $currentPage = 1, $limit = 10, $orderBy = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fIngreso BETWEEN :primerDia AND :ultimoDia')
            ->setParameter('primerDia', $primerDiaDelMes)
            ->setParameter('ultimoDia', $ultimoDiaDelMes);
            
        // Agregar soporte para ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.fIngreso', 'DESC');
        }
        
        if($paginar) {
            $paginator = $this->paginate($query, $currentPage, $limit);
            return array('paginator' => $paginator, 'query' => $query);
        }

        return $query->getQuery()->getResult();
    }
    
    public function findClientesEgresadosEsteMes($primerDiaDelMes, $ultimoDiaDelMes, $paginar = false, $currentPage = 1, $limit = 10, $orderBy = null)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso BETWEEN :primerDia AND :ultimoDia')
            ->setParameter('primerDia', $primerDiaDelMes)
            ->setParameter('ultimoDia', $ultimoDiaDelMes);
            
        // Agregar soporte para ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('c.' . $field, $direction);
                }
            } else {
                $query->orderBy('c.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('c.fEgreso', 'DESC');
        }

        if($paginar) {
            $paginator = $this->paginate($query, $currentPage, $limit);
            return array('paginator' => $paginator, 'query' => $query);
        }

        return $query->getQuery()->getResult();
    }

    public function findClientesIngresadosHoy()
    {
        $hoy = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere('c.fIngreso = :hoy')
            ->setParameter('hoy', $hoy)
            ->getQuery()
            ->getResult();
    }
    public function findClientesEgresadosHoy()
    {
        $hoy = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere('c.fEgreso = :hoy')
            ->setParameter('hoy', $hoy)
            ->getQuery()
            ->getResult();
    }


    public function findAllByIds($ids, $currentPage, $limit, $orderBy = null)
    {
        $query = $this->createQueryBuilder('c')
                ->where('c.id in (:ids)')
                ->setParameter('ids', $ids);


        if ( $orderBy ) {
            $query = $query->orderBy('c.' . $orderBy, 'ASC');
        } else {
            $query = $query->orderBy('c.hClinica', 'ASC');
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
    
    /**
     * Encuentra pacientes con habitación asignada pero sin número de cama o con número inválido (0)
     * y que no tengan habitación privada.
     * Útil para detectar inconsistencias en la asignación de camas.
     * 
     * @return Cliente[]
     */
    public function findClientesConHabitacionSinCama()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.habitacion IS NOT NULL')
            ->andWhere('(c.nCama IS NULL OR c.nCama = 0)')
            ->andWhere('c.fEgreso IS NULL')
            ->andWhere('c.derivado = 0')
            ->andWhere('c.dePermiso = 0')
            ->andWhere('c.habPrivada != 1 OR c.habPrivada IS NULL')  // Excluir pacientes con habitación privada
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Encuentra casos donde múltiples pacientes activos están asignados a la misma cama.
     * 
     * @return array Retorna un array de arrays con formato: [['habitacion_id' => x, 'n_cama' => y, 'cantidad' => z]]
     */
    public function findClientesCompartiendoCama()
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();
        
        $sql = '
            SELECT habitacion, n_cama, COUNT(*) as cantidad 
            FROM cliente 
            WHERE habitacion IS NOT NULL 
            AND n_cama IS NOT NULL 
            AND n_cama > 0
            AND f_egreso IS NULL 
            AND derivado = 0 
            AND de_permiso = 0 
            GROUP BY habitacion, n_cama 
            HAVING COUNT(*) > 1
        ';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }
}
