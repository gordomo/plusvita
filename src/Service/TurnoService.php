<?php

namespace App\Service;

/**
 * Servicio para determinar el turno actual y manejar la lógica de turnos de enfermería
 * Turnos:
 * - Mañana: 06:00 - 14:00
 * - Tarde: 14:00 - 22:00
 * - Noche: 22:00 - 06:00 (del día siguiente)
 */
class TurnoService
{
    const TURNO_MANANA = 'mañana';
    const TURNO_TARDE = 'tarde';
    const TURNO_NOCHE = 'noche';

    /**
     * Determina el turno actual basándose en la hora del día
     * 
     * @param \DateTime|null $fechaHora Si es null, usa la fecha/hora actual
     * @return string El turno actual ('mañana', 'tarde' o 'noche')
     */
    public function obtenerTurnoActual(\DateTime $fechaHora = null): string
    {
        if ($fechaHora === null) {
            $fechaHora = new \DateTime();
        }

        $hora = (int)$fechaHora->format('H');

        // Turno noche: 22:00 - 05:59
        if ($hora >= 22 || $hora < 6) {
            return self::TURNO_NOCHE;
        }
        
        // Turno mañana: 06:00 - 13:59
        if ($hora >= 6 && $hora < 14) {
            return self::TURNO_MANANA;
        }
        
        // Turno tarde: 14:00 - 21:59
        return self::TURNO_TARDE;
    }

    /**
     * Obtiene el rango de horas para un turno específico
     * 
     * @param string $turno El turno ('mañana', 'tarde' o 'noche')
     * @return array ['inicio' => hora inicio, 'fin' => hora fin]
     */
    public function obtenerRangoTurno(string $turno): array
    {
        switch ($turno) {
            case self::TURNO_MANANA:
                return ['inicio' => 6, 'fin' => 14];
            case self::TURNO_TARDE:
                return ['inicio' => 14, 'fin' => 22];
            case self::TURNO_NOCHE:
                return ['inicio' => 22, 'fin' => 6];
            default:
                throw new \InvalidArgumentException("Turno inválido: {$turno}");
        }
    }

    /**
     * Obtiene el nombre legible del turno
     */
    public function obtenerNombreTurno(string $turno): string
    {
        switch ($turno) {
            case self::TURNO_MANANA:
                return 'Mañana (06:00 - 14:00)';
            case self::TURNO_TARDE:
                return 'Tarde (14:00 - 22:00)';
            case self::TURNO_NOCHE:
                return 'Noche (22:00 - 06:00)';
            default:
                return $turno;
        }
    }

    /**
     * Verifica si una hora específica está dentro de un turno
     * 
     * @param \DateTime $fechaHora La fecha/hora a verificar
     * @param string $turno El turno a verificar
     * @return bool
     */
    public function estaEnTurno(\DateTime $fechaHora, string $turno): bool
    {
        $turnoActual = $this->obtenerTurnoActual($fechaHora);
        return $turnoActual === $turno;
    }

    /**
     * Obtiene la fecha base para un turno (importante para turno noche que cruza medianoche)
     * 
     * @param \DateTime $fechaHora La fecha/hora de referencia
     * @param string $turno El turno
     * @return \DateTime La fecha base (para turno noche, es la fecha del inicio del turno)
     */
    public function obtenerFechaBaseTurno(\DateTime $fechaHora, string $turno): \DateTime
    {
        $fechaBase = clone $fechaHora;
        
        // Para turno noche, si estamos después de medianoche pero antes de las 6am,
        // la fecha base es el día anterior
        if ($turno === self::TURNO_NOCHE) {
            $hora = (int)$fechaHora->format('H');
            if ($hora < 6) {
                $fechaBase->modify('-1 day');
            }
        }
        
        $fechaBase->setTime(0, 0, 0);
        return $fechaBase;
    }
}

