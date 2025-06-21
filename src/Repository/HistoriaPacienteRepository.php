<?php

namespace App\Repository;

use App\Entity\HistoriaPaciente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method HistoriaPaciente|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriaPaciente|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriaPaciente[]    findAll()
 * @method HistoriaPaciente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriaPacienteRepository extends ServiceEntityRepository
{
    public $em;
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, HistoriaPaciente::class);
        $this->em = $entityManager;
    }

    // /**
    //  * @return HistoriaPaciente[] Returns an array of HistoriaPaciente objects
    //  */

    public function getHistorialDesdeHastaOcupandoHabitacion($from, $to)
    {
        $query = $this->createQueryBuilder('h')->select('h.id, h.fecha, identity(h.cliente) as cliente, h.habitacion, h.cama')
            ->andWhere('h.cliente is not null');
        if (!empty($from)) {
            $query->andWhere('h.fecha >= :from')
                ->setParameter('from', $from);
        }
        if (!empty($to)) {
            $query->andWhere('h.fecha <= :to')
                ->setParameter('to', $to);
        }
        $query->andWhere('h.habitacion IS NOT NULL')
            ->andWhere("h.habitacion <> ''")
            ->orderBy('h.fecha, h.habitacion, h.cama, h.cliente', 'ASC')
        ;

        return $query->getQuery()->getResult();
    }

    public function getHistorialDesdeHasta($cliente, $from, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.cliente = :cliente')
            ->setParameter('cliente', $cliente);
        if (!empty($from)) {
            $query->andWhere('h.fecha >= :fechaDesde')->setParameter('fechaDesde', $from);
        }
        if (!empty($to)) {
            $query->andWhere('h.fecha <= :fechaHasta')->setParameter('fechaHasta', $to);
        }
        $query->orderBy('h.fecha, h.id', 'ASC');

        return $query->getQuery()->getResult();
    }
    public function getLastHistoriaAnterior($id, $cliente) {
        $query = $this->createQueryBuilder('h');
        $query->andWhere('h.id < :id')
        ->andWhere('h.cliente = :cliente')
        ->setParameter('id', $id)
        ->setParameter('cliente', $cliente)
        ->orderBy('h.id', 'DESC')
        ->setMaxResults(1);
        return $query->getQuery()->getOneOrNullResult();
    }

    public function getLastHistoriaPacienteHasta($to, $cliente) {
        $query = $this->createQueryBuilder('h');
        $query->andWhere('h.fechaEngreso >= :to or h.fechaEngreso is null');
        $query->andWhere('h.fecha <= :to')->setParameter('to', $to);
        $query->andWhere('h.cliente = :cliente')->setParameter('cliente', $cliente);
        $query->setMaxResults(1);
        $query->orderBy('h.fecha', 'DESC');
        return $query->getQuery()->getResult();
    }

    public function getPacienteConModalidadAntesDeFecha($desde, $hasta, $modalidad)
    {
        $query = $this->createQueryBuilder('h');
        $query->select('identity(h.cliente) as cliente')->andWhere('h.cliente is not null');
        $query->leftJoin('h.cliente', 'c');
        
        if (!empty($desde)) {
            $query->andWhere('h.fechaEngreso >= :desde or h.fechaEngreso is null');
            $query->andWhere('c.fEgreso >= :desde or c.fEgreso is null')->setParameter('desde', $desde);
        }
        if (!empty($hasta)) {
            $query->andWhere('h.fechaIngreso <= :hasta or h.fechaIngreso is null');
            $query->andWhere('c.fIngreso <= :hasta or c.fIngreso is null');
            $query->andWhere('h.fecha <= :hasta')->setParameter('hasta', $hasta);
        }
        if (!empty($modalidad)) {
            if($modalidad == 1) {
                $query->andWhere('h.modalidad != :modalidad')->setParameter('modalidad', 2);
            } else {
                $query->andWhere('h.modalidad = :modalidad')->setParameter('modalidad', $modalidad);
            }
            
        }
        $query->groupBy('h.cliente');


        return $query->getQuery()->getResult();
    }

    public function findFromTo($from, $to)
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha >= :from')->setParameter('from', $from)
            ->andWhere('h.fecha <= :to')->setParameter('to', $to);

            return $query->getQuery()->getResult();
    }
    public function findFromToCliente($from, $to, $cliente)
    {
        if ( empty($from) ) {
            $from = '1999-01-01';
        }
        if ( empty($to) ) {
            $to = '9999-01-01';
        }

        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha >= :from')->setParameter('from', $from)
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $cliente);

            return $query->getQuery()->getResult();
    }
    public function findLastModalidadChange($clienteId, $to)
    {
        if ( empty($to) ) {
            $to = '9999-01-01';
        }
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.fecha <= :to')->setParameter('to', $to)
            ->andWhere('h.cliente = :cliente')->setParameter('cliente', $clienteId)
            ->orderBy('h.fecha', 'DESC')
            ->setMaxResults(1);

            return $query->getQuery()->getResult();
    }

    public function findLastChange($clienteId, $to)
    {
        $query = $this->createQueryBuilder('h');
        
        if (!empty($to) ) {
            $query->andWhere('h.fecha <= :to')->setParameter('to', $to);
        }   
        $query->andWhere('h.cliente = :cliente')->setParameter('cliente', $clienteId)
            ->orderBy('h.fecha', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function getHistoricoDesdeHasta($desde, $hasta, $nombre = null, $modalidad = 0, $obraSocial = null, $prof = null, $hc = null) {
        $query = $this->createQueryBuilder('h')->where('h.fecha <= :hasta');
        $query->andWhere($query->expr()->orX('h.fechaFin >= :desde or h.fechaFin is null'));
        
        $query->leftJoin('h.cliente', 'c');

        $query->andWhere($query->expr()->orX('c.fEgreso >= :desde or c.fEgreso is null'))->setParameter('hasta', $hasta);
        $query->andWhere($query->expr()->orX('c.fIngreso <= :hasta or c.fIngreso is null'))->setParameter('desde', $desde);

        if ( $nombre ) {
            $i = 1;
            $arrayNombres = explode(' ', $nombre);
            foreach ( $arrayNombres as $nombre ) {
                $query->andWhere("c.nombre like :nombre$i OR c.apellido like :nombre$i")->setParameter("nombre$i",'%'. $nombre .'%');
                $i++;
            }
        }

        if ( $hc ) {
            $query->andWhere('c.hClinica = :hc')->setParameter('hc', $hc);
        }

        if ( $modalidad || $prof || $obraSocial) {
            $newQuery = "Select DISTINCT historia_paciente.cliente_id from historia_paciente where fecha <= '" . $hasta->format('Y-m-d') . "' and ( fecha_fin >= '". $desde->format('Y-m-d') . "' or fecha_fin is null )";
            
            if ( $modalidad ) { 
                if ( $modalidad == 1 ) { 
                    $newQuery .= " and ( modalidad = 1 or modalidad = 4)";
                } else {
                    $newQuery .= " and modalidad = " . $modalidad;
                }
            }
            if ( $prof ) { 
                $prof = '%'.$prof.'%';
                $newQuery .= " and doc_referente like '" . $prof ."'";
            }
            if ($obraSocial) { 
                if (is_array($obraSocial)) {
                    if (count($obraSocial) > 0) {
                        $obrasSocialesIDs = implode(',', array_map('intval', $obraSocial));
                        $newQuery .= " and obra_social IN (" . $obrasSocialesIDs . ")";
                    }
                } else {
                    // Mantener compatibilidad con versiones anteriores (un solo ID)
                    $newQuery .= " and obra_social = " . $obraSocial;
                }
            }
            
            $ids = $this->em->getConnection()->prepare($newQuery)->executeQuery()->fetchFirstColumn();
            
            $query->andWhere('c.id in (:newQuery)')->setParameter('newQuery', $ids);
        }

        return $query->getQuery()->getResult();

        // $sql = "SELECT *, s.fecha as fecha_posta from (
        //     SELECT h.cliente_id, h.fecha, h.habitacion_id, h.n_cama, null
        //         FROM historia_habitaciones h
        
        //         UNION 
        
        //     SELECT p.paciente_id, p.fecha, null, null, p.valor
        //         FROM presentes p
        //     ) as s  
        
        // LEFT JOIN historia_paciente hist 
        // on s.cliente_id = hist.cliente_id
        // WHERE s.fecha >= hist.fecha and (s.fecha <= hist.fecha_fin or hist.fecha_fin is null)
        // and hist.fecha <= '$hasta' and (hist.fecha_fin >= '$desde' or hist.fecha_fin is null)
        // and s.fecha >= '$desde' and s.fecha <= '$hasta'
        // ORDER BY `s`.`fecha` DESC";

        // $result = $this->em->getConnection()->prepare($sql)->executeQuery();

        // return $result->fetchAllAssociative();
    }

    public function getAmbulatorios()
    {
        // Subconsulta para obtener el último ID (registro más reciente) de cada cliente en historia_pacientes
        $subquery = $this->createQueryBuilder('sub')
            ->select('MAX(sub.id)')
            ->where('sub.cliente = hp.cliente')
            ->getDQL();

        // Consulta principal
        $qb = $this->createQueryBuilder('hp')
            ->select('hp')
            ->join('hp.cliente', 'c') // Asocia la tabla cliente con el alias 'c'
            ->where('hp.modalidad IN (:modalities) OR hp.ambulatorio = :isAmbulatory')
            ->andWhere('hp.fechaEngreso IS NULL OR hp.fechaEngreso < :today')  // Verifica la fecha de egreso del historial
            ->andWhere('c.fEgreso IS NULL OR c.fEgreso > :today')              // Verifica la fecha de egreso del cliente
            ->andWhere('(hp.fechaDerivacion IS NULL OR hp.fechaDerivacion > :today)')
            ->andWhere('(hp.fechaReingresoDerivacion IS NULL OR hp.fechaReingresoDerivacion <= :today)')
            ->andWhere('hp.id = (' . $subquery . ')')  // Utiliza la subconsulta para obtener el último registro del cliente
            ->setParameter('modalities', [1, 4])
            ->setParameter('isAmbulatory', true)
            ->setParameter('today', new \DateTime('now'));

        return $qb->getQuery()->getResult();
    }

    public function getAmbulatoriosIds($startDate, $endDate)
    {
        $subquery = $this->createQueryBuilder('sub')
            ->select('MAX(sub.id)')
            ->where('sub.cliente = hp.cliente')
            ->getDQL();

        // Consulta principal
        $qb = $this->createQueryBuilder('hp')
            ->select('c.id')
            ->join('hp.cliente', 'c')
            ->where('hp.modalidad IN (:modalities) OR hp.ambulatorio = :isAmbulatory')
            ->andWhere('hp.fechaEngreso IS NULL OR hp.fechaEngreso < :endDate')  // Filtra por fecha de egreso
            ->andWhere('c.fEgreso IS NULL OR c.fEgreso > :startDate')              // Filtra por fecha de egreso del cliente
            ->andWhere('(hp.fechaDerivacion IS NULL OR hp.fechaDerivacion > :startDate)')
            ->andWhere('(hp.fechaReingresoDerivacion IS NULL OR hp.fechaReingresoDerivacion <= :endDate)')
            ->andWhere('hp.id = (' . $subquery . ')')  // Utiliza la subconsulta
            ->setParameter('modalities', [1, 4])
            ->setParameter('isAmbulatory', true)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'));

        // Obtener los resultados como un array
        $results = $qb->getQuery()->getArrayResult();

        // Extraer los IDs en un array plano
        $ids = array_column($results, 'id');

        return $ids;  // Devuelve solo el array de IDs
    }

    public function getPacientesDerivadosPorMes($startDate, $endDate, $orderBy = null)
    {    
        $query = $this->createQueryBuilder('h')
            ->select('DISTINCT h.id_paciente AS id_paciente, h.fechaDerivacion')
            ->andWhere('h.fechaDerivacion >= :startDate')
            ->andWhere('h.fechaDerivacion <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
            
        // Agregar soporte para ordenamiento
        if ($orderBy) {
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $direction) {
                    $query->orderBy('h.' . $field, $direction);
                }
            } else {
                $query->orderBy('h.' . $orderBy, 'ASC');
            }
        } else {
            $query->orderBy('h.fechaDerivacion', 'DESC');
        }
        
        $result = $query->getQuery()->getScalarResult();

        // Extraemos los valores en un array simple
        $pacientesIds = array_column($result, 'id_paciente');

        return $pacientesIds;
    }

    public function getPacientesDePermisoPorMes($startDate, $endDate)
    {    
        $result = $this->createQueryBuilder('h')
            ->select('DISTINCT h.id_paciente AS id_paciente')
            ->andWhere('h.fechaAltaPorPermiso >= :startDate')
            ->andWhere('h.fechaAltaPorPermiso <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getScalarResult();

        // Extraemos los valores en un array simple
        $pacientesIds = array_column($result, 'id_paciente');

        return $pacientesIds;
    }

    public function getPacientesReingresoDerivadosPorMes($startDate, $endDate)
    {    
        $result = $this->createQueryBuilder('h')
            ->select('DISTINCT h.id_paciente AS id_paciente')
            ->andWhere('h.fechaReingresoDerivacion >= :startDate')
            ->andWhere('h.fechaReingresoDerivacion <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getScalarResult();

        // Extraemos los valores en un array simple
        $pacientesIds = array_column($result, 'id_paciente');

        return $pacientesIds;
    }

    public function getPacientesReingresoPermisoPorMes($startDate, $endDate)
    {    
        $result = $this->createQueryBuilder('h')
            ->select('DISTINCT h.id_paciente AS id_paciente')
            ->andWhere('h.fechaBajaPorPermiso >= :startDate')
            ->andWhere('h.fechaBajaPorPermiso <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getScalarResult();

        // Extraemos los valores en un array simple
        $pacientesIds = array_column($result, 'id_paciente');

        return $pacientesIds;
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
