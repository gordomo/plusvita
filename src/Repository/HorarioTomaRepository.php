<?php

namespace App\Repository;

use App\Entity\HorarioToma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HorarioToma|null find($id, $lockMode = null, $lockVersion = null)
 * @method HorarioToma|null findOneBy(array $criteria, array $orderBy = null)
 * @method HorarioToma[]    findAll()
 * @method HorarioToma[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HorarioTomaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HorarioToma::class);
    }

    /**
     * Obtiene los IDs de clientes que tienen medicación programada para una fecha específica
     */
    public function findClienteIdsConMedicacionHoy(\DateTime $fecha): array
    {
        $result = $this->createQueryBuilder('h')
            ->select('DISTINCT i.clienteId')
            ->join('h.indicacion', 'i')
            ->where('h.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->getQuery()
            ->getScalarResult();
        
        return array_column($result, 'clienteId');
    }

    /**
     * Obtiene los horarios de toma para un paciente en una fecha específica
     */
    public function findHorariosPorClienteYFecha($clienteId, \DateTime $fecha): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.indicacion', 'i')
            ->where('i.clienteId = :clienteId')
            ->andWhere('h.fecha = :fecha')
            ->andWhere('h.habilitado = true')
            ->andWhere('i.activo = true')
            ->andWhere('i.estadoSuspendido = false')
            ->setParameter('clienteId', $clienteId)
            ->setParameter('fecha', $fecha->format('Y-m-d'))
            ->orderBy('h.horario', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene los horarios pendientes de administrar para un paciente
     */
    public function findHorariosPendientesPorCliente($clienteId, \DateTime $fechaDesde = null, \DateTime $fechaHasta = null): array
    {
        $qb = $this->createQueryBuilder('h')
            ->join('h.indicacion', 'i')
            ->where('i.clienteId = :clienteId')
            ->andWhere('h.administrado = false')
            ->andWhere('h.habilitado = true')
            ->andWhere('i.activo = true')
            ->andWhere('i.estadoSuspendido = false')
            ->setParameter('clienteId', $clienteId);

        if ($fechaDesde) {
            $qb->andWhere('h.fecha >= :fechaDesde')
               ->setParameter('fechaDesde', $fechaDesde->format('Y-m-d'));
        }

        if ($fechaHasta) {
            $qb->andWhere('h.fecha <= :fechaHasta')
               ->setParameter('fechaHasta', $fechaHasta->format('Y-m-d'));
        }

        return $qb->orderBy('h.fecha', 'ASC')
                  ->addOrderBy('h.horario', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Obtiene horarios que están en ventana de administración (incluye SOS del día actual)
     */
    public function findHorariosEnVentana($clienteId, \DateTime $ahora = null): array
    {
        if (!$ahora) {
            $ahora = new \DateTime();
        }

        $fechaActual = $ahora->format('Y-m-d');
        $horaVentanaInicio = clone $ahora;
        $horaVentanaInicio->modify('-2 hours');
        
        $horaVentanaFin = clone $ahora;
        $horaVentanaFin->modify('+2 hours');

        // Consulta para horarios regulares en ventana + medicaciones SOS del día
        return $this->createQueryBuilder('h')
            ->join('h.indicacion', 'i')
            ->where('i.clienteId = :clienteId')
            ->andWhere('h.fecha = :fecha')
            ->andWhere('h.administrado = false')
            ->andWhere('h.habilitado = true')
            ->andWhere('i.activo = true')
            ->andWhere('i.estadoSuspendido = false')
            ->andWhere('(h.horario BETWEEN :horaInicio AND :horaFin) OR i.frecuencia = :sos')
            ->setParameter('clienteId', $clienteId)
            ->setParameter('fecha', $fechaActual)
            ->setParameter('horaInicio', $horaVentanaInicio->format('H:i:s'))
            ->setParameter('horaFin', $horaVentanaFin->format('H:i:s'))
            ->setParameter('sos', 'sos')
            ->orderBy('h.horario', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Elimina horarios de toma para una indicación específica
     */
    public function eliminarHorariosPorIndicacion($indicacionId): void
    {
        $this->createQueryBuilder('h')
            ->delete()
            ->where('h.indicacion = :indicacionId')
            ->setParameter('indicacionId', $indicacionId)
            ->getQuery()
            ->execute();
    }

    /**
     * Obtiene indicaciones próximas y pendientes para el dashboard de enfermeros
     * Incluye: horarios en ventana de administración, próximos 8 horas y pendientes de últimas 8 horas
     */
    public function findIndicacionesProximasParaDashboard(\DateTime $ahora = null, int $limite = 20): array
    {
        if (!$ahora) {
            $ahora = new \DateTime();
        }

        $fechaActual = $ahora->format('Y-m-d');
        $horaActual = $ahora->format('H:i:s');
        
        // Calcular ventanas de tiempo
        $hace8Horas = clone $ahora;
        $hace8Horas->modify('-8 hours');
        $en8Horas = clone $ahora;
        $en8Horas->modify('+8 hours');
        
        $fechaHace8Horas = $hace8Horas->format('Y-m-d');
        $fechaEn8Horas = $en8Horas->format('Y-m-d');
        $horaHace8Horas = $hace8Horas->format('H:i:s');
        $horaEn8Horas = $en8Horas->format('H:i:s');
        
        // Ventana de administración: 2 horas antes y después
        $ventanaInicio = clone $ahora;
        $ventanaInicio->modify('-2 hours');
        $ventanaFin = clone $ahora;
        $ventanaFin->modify('+2 hours');
        
        $horaVentanaInicio = $ventanaInicio->format('H:i:s');
        $horaVentanaFin = $ventanaFin->format('H:i:s');

        // Obtener horarios que cumplen los criterios:
        // 1. En ventana de administración del día actual (2 horas antes/después)
        // 2. Próximos 8 horas (hoy o mañana)
        // 3. Pendientes de últimas 8 horas
        return $this->createQueryBuilder('h')
            ->join('h.indicacion', 'i')
            ->where('h.administrado = false')
            ->andWhere('h.habilitado = true')
            ->andWhere('i.activo = true')
            ->andWhere('i.estadoSuspendido = false')
            ->andWhere(
                // En ventana de administración del día actual
                '(h.fecha = :fechaActual AND (h.horario BETWEEN :horaVentanaInicio AND :horaVentanaFin OR i.frecuencia = :sos)) OR ' .
                // Próximas 8 horas: hoy con horario futuro o mañana dentro de la ventana
                '(h.fecha = :fechaActual AND h.horario >= :horaActual AND h.horario <= :horaEn8Horas) OR ' .
                '(h.fecha = :fechaEn8Horas AND h.horario <= :horaEn8Horas) OR ' .
                // Pendientes de últimas 8 horas
                '(h.fecha = :fechaHace8Horas AND h.horario >= :horaHace8Horas) OR ' .
                '(h.fecha = :fechaActual AND h.horario < :horaActual AND h.horario >= :horaHace8Horas)'
            )
            ->setParameter('fechaActual', $fechaActual)
            ->setParameter('horaActual', $horaActual)
            ->setParameter('horaVentanaInicio', $horaVentanaInicio)
            ->setParameter('horaVentanaFin', $horaVentanaFin)
            ->setParameter('sos', 'sos')
            ->setParameter('fechaHace8Horas', $fechaHace8Horas)
            ->setParameter('fechaEn8Horas', $fechaEn8Horas)
            ->setParameter('horaHace8Horas', $horaHace8Horas)
            ->setParameter('horaEn8Horas', $horaEn8Horas)
            ->orderBy('h.fecha', 'ASC')
            ->addOrderBy('h.horario', 'ASC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
