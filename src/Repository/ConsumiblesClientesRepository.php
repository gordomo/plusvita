<?php

namespace App\Repository;

use App\Entity\ConsumiblesClientes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConsumiblesClientes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConsumiblesClientes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConsumiblesClientes[]    findAll()
 * @method ConsumiblesClientes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsumiblesClientesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsumiblesClientes::class);
    }

    // /**
    //  * @return Consumible[] Returns an array of Consumible objects
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

    public function findLastMes()
    {
        $now = new \DateTime();
        $mes = $now->modify("-1 month")->format('m');

        return $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @deprecated Usar findActiveIndicationsForClient en su lugar
     */
    public function findConsumibleMesAnteriorParaElCliente($id, $accion = null)
    {
        $now = new \DateTime();
        $mes = $now->modify("-1 month")->format('m');
        $year = $now->format('Y');
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->andWhere('c.year = :year')
            ->setParameter('year', $year)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $id);

        if ( $accion !== null ) {
            $query->andWhere('c.accion = :accion')
                ->setParameter('accion', $accion);
        }
            return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }
    
    /**
     * Encuentra todas las indicaciones activas para un cliente
     * 
     * Una indicación está activa si:
     * 1. El campo activo es true
     * 2. La fecha actual está entre fechaInicio y fechaFin (o fechaFin es null para indicaciones indefinidas)
     */
    public function findActiveIndicationsForClient($clienteId)
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :clienteId')
            ->setParameter('clienteId', $clienteId)
            ->andWhere('c.activo = :activo')
            ->setParameter('activo', true)
            ->andWhere('(c.fechaInicio IS NULL OR c.fechaInicio <= :today)')
            ->andWhere('(c.fechaFin IS NULL OR c.fechaFin >= :today)')
            ->setParameter('today', $today)
            ->orderBy('c.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Encuentra indicaciones que están próximas a vencer (en los próximos X días)
     */
    public function findExpiringIndicationsForClient($clienteId, $daysThreshold = 7)
    {
        $today = new \DateTime();
        $futureDate = (new \DateTime())->modify("+{$daysThreshold} days");
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :clienteId')
            ->setParameter('clienteId', $clienteId)
            ->andWhere('c.activo = :activo')
            ->setParameter('activo', true)
            ->andWhere('c.fechaFin IS NOT NULL')
            ->andWhere('c.fechaFin > :today')
            ->andWhere('c.fechaFin <= :futureDate')
            ->setParameter('today', $today)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('c.fechaFin', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Encuentra indicaciones históricas (inactivas o vencidas)
     */
    public function findHistoricalIndicationsForClient($clienteId)
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :clienteId')
            ->setParameter('clienteId', $clienteId)
            ->andWhere('(c.activo = :inactivo OR (c.fechaFin IS NOT NULL AND c.fechaFin < :today))')
            ->setParameter('inactivo', false)
            ->setParameter('today', $today)
            ->orderBy('c.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findIndicacionesParaElCliente($id, $year = '', $mes = '', $limit = null, $soloActivas = true)
    {
        // Ejecutar consulta SQL directa para verificar los datos
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM consumibles_clientes WHERE cliente_id = :cid ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('cid', $id);
        $resultSet = $stmt->executeQuery();
        $rawData = $resultSet->fetchAllAssociative();
        
        // Solo usar los datos raw para depuración, no devolver directamente
        $rawDataExists = !empty($rawData);
        // Si no hay registros, mostrar mensaje en la consola pero continuar con la consulta normal
        if (!$rawDataExists) {
            // No hay indicaciones en la base de datos para este cliente
            error_log("No se encontraron indicaciones en la base de datos para el cliente ID: " . $id);
        }
        
        // Continuar con la consulta normal
        $qb = $this->createQueryBuilder('c');
        
        $query = $qb
            ->select('c')
            ->where('c.clienteId = :cid')
            ->andWhere('c.accion = :accion')
            ->setParameter('cid', $id)
            ->setParameter('accion', '0');
            
        // Si soloActivas es true, filtramos para mostrar solo indicaciones activas
        if ($soloActivas) {
            $query->andWhere('c.activo = :activo')
                  ->setParameter('activo', true);
        }
        
        if (!empty($year)) {
            $query->andWhere('c.year = :year')
                  ->setParameter('year', $year);
        }
        
        if (!empty($mes)) {
            $query->andWhere('c.mes = :mes')
                  ->setParameter('mes', $mes);
        }
        
        // Realizar un debug de la consulta SQL para ver qué está pasando
        $query->orderBy('c.id', 'DESC');
        
        // Guardar la consulta SQL para depuración
        $sqlQuery = $query->getQuery()->getSQL();
        
        // Aplicamos límite antes de ejecutar la consulta si está especificado
        if ($limit !== null) {
            $query->setMaxResults($limit);
        }
        // Obtener la consulta SQL
        $result = $query->getQuery()->getResult();
        $indicaciones = [];
        
        // Si no hay resultados, solo registrar el mensaje en el log
        if (empty($result)) {
            error_log("No se encontraron indicaciones en la consulta ORM para el cliente ID: " . $id);
            // Devolver un array vacío
            return [];
        }
        
        foreach ($result as $indicacion) {
            // Ahora trabajamos directamente con objetos ConsumiblesClientes
            $consumible = $this->getEntityManager()
                ->getRepository('App\Entity\Consumible')
                ->find($indicacion->getConsumibleId());
            
            $doctorNombre = "Usuario";
            $doctorApellido = "del Sistema";
            
            $indicacionArray = [
                'id' => $indicacion->getId(),
                'consumibleId' => $indicacion->getConsumibleId(),
                'consumibleNombre' => $consumible ? $consumible->getNombre() : '',
                'unidades' => $consumible ? $consumible->getUnidades() : '',
                'tipo' => $consumible && method_exists($consumible, 'getTipo') && $consumible->getTipo() ? $consumible->getTipo()->getId() : null,
                'clienteId' => $indicacion->getClienteId(),
                'fecha' => $indicacion->getFecha(),
                'mes' => $indicacion->getMes(),
                'year' => $indicacion->getYear(),
                'cantidad' => $indicacion->getCantidad(),
                'accion' => $indicacion->getAccion(),
                'doctorNombre' => $doctorNombre,
                'doctorApellido' => $doctorApellido,
                'notas' => $indicacion->getNotas(),
                'activo' => method_exists($indicacion, 'isActivo') ? $indicacion->isActivo() : true,
                // Nuevos campos agregados
                'tipoIndicacion' => method_exists($indicacion, 'getTipoIndicacion') ? $indicacion->getTipoIndicacion() : null,
                'frecuencia' => method_exists($indicacion, 'getFrecuencia') ? $indicacion->getFrecuencia() : null,
                'duracion' => method_exists($indicacion, 'getDuracion') ? $indicacion->getDuracion() : null,
                'viaAdministracion' => method_exists($indicacion, 'getViaAdministracion') ? $indicacion->getViaAdministracion() : null,
            ];
            
            $indicaciones[] = $indicacionArray;
        }
        
        // Ordenar por año (DESC), mes (DESC) y fecha (DESC)
        usort($indicaciones, function($a, $b) {
            if ($a['year'] != $b['year']) {
                return $b['year'] <=> $a['year']; // Ordenar por año descendente
            }
            if ($a['mes'] != $b['mes']) {
                return $b['mes'] <=> $a['mes']; // Ordenar por mes descendente
            }
            return $b['fecha'] <=> $a['fecha']; // Ordenar por fecha descendente
        });
        
        return $indicaciones;
    }

    public function findImputacionesMesConsumibleCliente($mes, $consumibleId, $cid, $year)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.mes = :mes')
            ->setParameter('mes', $mes)
            ->andWhere('c.year = :year')
            ->setParameter('year', $year)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $cid)
            ->andWhere('c.consumibleId = :consumibleId')
            ->setParameter('consumibleId', $consumibleId)
            ->andWhere('c.accion = :accion')
            ->setParameter('accion', '1');

        return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }

    public function findImputacionesFechaConsumible($fecha, $cid)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->andWhere('c.clienteId = :cid')
            ->setParameter('cid', $cid)
            ->andWhere('c.accion = :accion')
            ->setParameter('accion', '1');

        return $query->orderBy('c.consumibleId', 'ASC')->getQuery()->getResult();
    }

    public function findByAccionAndClientId($id, $mes, $fecha, $accion = null, $year = '')
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.clienteId = :id')
            ->setParameter('id', $id);

        if ($accion !== null) {
            $query->andWhere('c.accion = :accion')->setParameter('accion', $accion);
        }
        if ($mes) {
            $query->andWhere('c.mes = :mes')->setParameter('mes', $mes);
        }
        if ($fecha) {
            $query->andWhere('c.fecha = :fecha')->setParameter('fecha', $fecha);
        }
        if ($year != '') {
            $query->andWhere('c.year = :year')->setParameter('year', $year);
        }


        //$query->groupBy('c.consumibleId');
        $query->orderBy('c.consumibleId', ' desc');

        return $query->getQuery()->getResult();
    }

}
