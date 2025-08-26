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
}
