<?php

namespace App\Service;

use App\Entity\ConsumiblesClientes;
use App\Entity\HorarioToma;
use App\Repository\HorarioTomaRepository;
use Doctrine\ORM\EntityManagerInterface;

class HorarioTomaCalculatorService
{
    private $entityManager;
    private $horarioTomaRepository;

    public function __construct(EntityManagerInterface $entityManager, HorarioTomaRepository $horarioTomaRepository)
    {
        $this->entityManager = $entityManager;
        $this->horarioTomaRepository = $horarioTomaRepository;
    }

    /**
     * Genera los horarios de toma para una indicación médica
     */
    public function generarHorariosToma(ConsumiblesClientes $indicacion, int $diasAGenerar = 30): array
    {
        // Limpiar horarios existentes para esta indicación
        $this->horarioTomaRepository->eliminarHorariosPorIndicacion($indicacion->getId());
        
        $frecuencia = $indicacion->getFrecuencia();
        $horario = $indicacion->getHorario();
        $fechaInicio = $indicacion->getFechaInicio() ?: new \DateTime();
        
        // Si no hay horario definido, usar un horario por defecto según la frecuencia
        if (!$horario) {
            $horario = $this->getHorarioDefaultPorFrecuencia($frecuencia);
        }

        $horarios = [];
        $intervalos = $this->calcularIntervalos($frecuencia);
        
        // Para medicaciones SOS, crear solo un horario "flexible" por día
        if ($frecuencia === 'sos') {
            $horarios = $this->generarHorariosSOS($indicacion, $fechaInicio, $diasAGenerar);
        } else {
            $horarios = $this->generarHorariosRegulares($indicacion, $fechaInicio, $horario, $intervalos, $diasAGenerar);
        }

        // Guardar en base de datos
        foreach ($horarios as $horario) {
            $this->entityManager->persist($horario);
        }
        $this->entityManager->flush();

        return $horarios;
    }

    /**
     * Calcula los intervalos entre tomas basándose en la frecuencia
     */
    private function calcularIntervalos($frecuencia): array
    {
        switch ($frecuencia) {
            case 'cada4h':
                return [4]; // Cada 4 horas
            case 'cada6h':
                return [6]; // Cada 6 horas
            case 'cada8h':
                return [8]; // Cada 8 horas
            case 'cada12h':
                return [12]; // Cada 12 horas
            case 'cada24h':
            case 'diario':
                return [24]; // Cada 24 horas
            case 'bid':
                return [12]; // 2 veces al día (cada 12 horas)
            case 'tid':
                return [8]; // 3 veces al día (cada 8 horas)
            case 'semanal':
                return [168]; // Cada semana (7 días * 24 horas)
            case 'sos':
                return []; // SOS no tiene intervalos fijos
            default:
                return [24]; // Por defecto, una vez al día
        }
    }

    /**
     * Genera horarios para medicación SOS (si es necesario)
     */
    private function generarHorariosSOS(ConsumiblesClientes $indicacion, \DateTime $fechaInicio, int $diasAGenerar): array
    {
        $horarios = [];
        $fechaActual = clone $fechaInicio;
        
        for ($i = 0; $i < $diasAGenerar; $i++) {
            $horario = new HorarioToma();
            $horario->setIndicacion($indicacion);
            $horario->setFecha(clone $fechaActual);
            
            // Para SOS, permitir administración en cualquier momento del día
            $horarioFlexible = new \DateTime('08:00:00'); // Horario de referencia
            $horario->setHorario($horarioFlexible);
            $horario->setHabilitado(true);
            
            $horarios[] = $horario;
            $fechaActual->modify('+1 day');
        }
        
        return $horarios;
    }

    /**
     * Genera horarios regulares basándose en intervalos fijos
     */
    private function generarHorariosRegulares(ConsumiblesClientes $indicacion, \DateTime $fechaInicio, \DateTime $horario, array $intervalos, int $diasAGenerar): array
    {
        $horarios = [];
        $fechaActual = clone $fechaInicio;
        $fechaLimite = clone $fechaInicio;
        $fechaLimite->modify("+{$diasAGenerar} days");
        
        // Si hay fecha fin definida, usarla como límite
        if ($indicacion->getFechaFin()) {
            $fechaLimite = min($fechaLimite, $indicacion->getFechaFin());
        }

        foreach ($intervalos as $intervaloHoras) {
            $horarioActual = clone $horario;
            $fechaHorarioActual = clone $fechaInicio;
            $fechaHorarioActual->setTime(
                $horarioActual->format('H'),
                $horarioActual->format('i'),
                $horarioActual->format('s')
            );

            while ($fechaHorarioActual <= $fechaLimite) {
                $horario = new HorarioToma();
                $horario->setIndicacion($indicacion);
                $horario->setFecha(clone $fechaHorarioActual);
                $horario->setHorario(clone $horarioActual);
                $horario->setHabilitado(true);
                
                $horarios[] = $horario;
                
                // Avanzar al siguiente horario
                $fechaHorarioActual->modify("+{$intervaloHoras} hours");
                $horarioActual = new \DateTime($fechaHorarioActual->format('H:i:s'));
            }
        }

        return $horarios;
    }

    /**
     * Obtiene un horario por defecto según la frecuencia
     */
    private function getHorarioDefaultPorFrecuencia($frecuencia): \DateTime
    {
        switch ($frecuencia) {
            case 'cada4h':
            case 'cada6h':
            case 'cada8h':
                return new \DateTime('08:00:00'); // Comenzar a las 8 AM
            case 'cada12h':
            case 'bid':
                return new \DateTime('08:00:00'); // 8 AM y 8 PM
            case 'tid':
                return new \DateTime('08:00:00'); // 8 AM, 4 PM, 12 AM
            case 'cada24h':
            case 'diario':
                return new \DateTime('09:00:00'); // 9 AM para medicación diaria
            case 'semanal':
                return new \DateTime('10:00:00'); // 10 AM para medicación semanal
            case 'sos':
                return new \DateTime('08:00:00'); // Horario de referencia
            default:
                return new \DateTime('09:00:00'); // Por defecto 9 AM
        }
    }

    /**
     * Recalcula horarios cuando se modifica una indicación
     */
    public function recalcularHorarios(ConsumiblesClientes $indicacion): array
    {
        return $this->generarHorariosToma($indicacion);
    }

    /**
     * Marca un horario como administrado
     */
    public function marcarComoAdministrado(HorarioToma $horario, $userId = null, $observaciones = null): bool
    {
        // Para medicaciones SOS, permitir administración en cualquier momento del día actual
        $esMedicacionSOS = $horario->getIndicacion()->getFrecuencia() === 'sos';
        $esMismoDay = $horario->getFecha()->format('Y-m-d') === date('Y-m-d');
        
        // Verificar que esté en ventana de administración o sea SOS del día actual
        if (!$esMedicacionSOS && !$horario->estaEnVentanaAdministracion()) {
            return false; // No está en ventana de administración
        }
        
        // Para medicaciones SOS, verificar que sea el día correcto
        if ($esMedicacionSOS && !$esMismoDay) {
            return false; // SOS solo se puede administrar el día programado
        }

        $horario->setAdministrado(true);
        $horario->setFechaAdministracion(new \DateTime());
        
        if ($userId) {
            $horario->setAdministradoPorUserId($userId);
        }
        
        if ($observaciones) {
            $horario->setObservaciones($observaciones);
        }

        $this->entityManager->persist($horario);
        $this->entityManager->flush();

        return true;
    }
}
