<?php

namespace App\Service;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Repository\ClienteRepository;
use App\Repository\HabitacionRepository;
use Doctrine\ORM\EntityManagerInterface;

class HabitacionService
{
    private $entityManager;
    private $clienteRepository;
    private $habitacionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClienteRepository $clienteRepository,
        HabitacionRepository $habitacionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->clienteRepository = $clienteRepository;
        $this->habitacionRepository = $habitacionRepository;
    }

    /**
     * Actualiza las camas ocupadas de una habitación basándose en los pacientes reales asignados
     * NOTA: Este método ya no persiste datos en BD ya que el campo camasOcupadas fue eliminado.
     * Se mantiene para compatibilidad con código legacy que lo llama.
     * Las camas ocupadas ahora se calculan dinámicamente consultando pacientes activos.
     */
    public function actualizarCamasOcupadas(Habitacion $habitacion): void
    {
        // No-op: El campo camasOcupadas ya no existe en BD
        // Las camas ocupadas se calculan dinámicamente desde los pacientes reales
        // Este método se mantiene solo para evitar errores en código que lo llama
    }

    /**
     * Asigna un paciente a una cama específica y actualiza las camas ocupadas
     */
    public function asignarPacienteACama(Cliente $cliente, Habitacion $habitacion, int $numeroCama): void
    {
        // Asignar la cama al paciente
        $cliente->setNCama($numeroCama);
        $cliente->setHabitacion($habitacion->getId());
        
        // Actualizar las camas ocupadas de la habitación
        $this->actualizarCamasOcupadas($habitacion);
        
        $this->entityManager->persist($cliente);
    }

    /**
     * Libera la cama de un paciente y actualiza las camas ocupadas
     */
    public function liberarCamaPaciente(Cliente $cliente): void
    {
        if (!$cliente->getHabitacion()) {
            return;
        }
        
        $habitacion = $this->habitacionRepository->find($cliente->getHabitacion());
        if (!$habitacion) {
            return;
        }
        
        // Limpiar la asignación del paciente
        $cliente->setHabitacion(null);
        $cliente->setNCama(null);
        $cliente->setHabPrivada(0);
        
        // Actualizar las camas ocupadas de la habitación
        $this->actualizarCamasOcupadas($habitacion);
        
        $this->entityManager->persist($cliente);
    }

    /**
     * Mueve un paciente de una habitación a otra y actualiza ambas habitaciones
     */
    public function moverPacienteEntreHabitaciones(
        Cliente $cliente, 
        Habitacion $habitacionOrigen, 
        Habitacion $habitacionDestino, 
        int $nuevaCama
    ): void {
        // Liberar cama en la habitación origen
        if ($habitacionOrigen) {
            $this->actualizarCamasOcupadas($habitacionOrigen);
        }
        
        // Asignar cama en la habitación destino
        $this->asignarPacienteACama($cliente, $habitacionDestino, $nuevaCama);
    }

    /**
     * Marca una habitación como privada (todas las camas ocupadas)
     */
    public function marcarHabitacionComoPrivada(Habitacion $habitacion, Cliente $cliente): void
    {
        $cliente->setHabPrivada(1);
        $cliente->setNCama(0); // Convención: cama 0 para habitación privada
        
        // NOTA: El campo camasOcupadas ya no existe en BD
        // Las camas ocupadas se calculan dinámicamente desde los pacientes reales
        // No es necesario actualizar ningún campo en la habitación
        
        $this->entityManager->persist($cliente);
        $this->entityManager->persist($habitacion);
    }

    /**
     * Desmarca una habitación como privada y reasigna camas
     */
    public function desmarcarHabitacionComoPrivada(Habitacion $habitacion): void
    {
        // Obtener todos los pacientes en la habitación
        $pacientesEnHabitacion = $this->clienteRepository->findClienteEnHabitacion(
            $habitacion, 
            false, 
            true
        );
        
        // Reasignar camas a los pacientes
        $camasAsignadas = [];
        foreach ($pacientesEnHabitacion as $paciente) {
            $paciente->setHabPrivada(0);
            
            // Buscar la primera cama disponible
            $camaDisponible = 1;
            while (isset($camasAsignadas[$camaDisponible])) {
                $camaDisponible++;
            }
            
            $paciente->setNCama($camaDisponible);
            $camasAsignadas[$camaDisponible] = $camaDisponible;
            
            $this->entityManager->persist($paciente);
        }
        
        // NOTA: El campo camasOcupadas ya no existe en BD
        // Las camas ocupadas se calculan dinámicamente desde los pacientes reales
        // No es necesario actualizar ningún campo en la habitación
        
        $this->entityManager->persist($habitacion);
    }

    /**
     * Verifica si una habitación tiene inconsistencias en las camas ocupadas
     */
    public function verificarInconsistencias(Habitacion $habitacion): array
    {
        $pacientesEnHabitacion = $this->clienteRepository->findClienteEnHabitacion(
            $habitacion, 
            false, 
            true
        );
        
        $camasOcupadasReales = [];
        foreach ($pacientesEnHabitacion as $paciente) {
            $nCama = $paciente->getNCama();
            if ($nCama !== null && $nCama > 0) {
                $camasOcupadasReales[$nCama] = $nCama;
            }
        }
        
        // NOTA: El campo camasOcupadas ya no existe en BD
        // Siempre retornamos que no hay inconsistencia porque el campo no existe
        // Las camas ocupadas se calculan dinámicamente desde los pacientes reales
        
        return [
            'tieneInconsistencia' => false, // El campo ya no existe, no puede haber inconsistencia
            'camasOcupadasActuales' => [], // Campo inexistente
            'camasOcupadasReales' => $camasOcupadasReales,
            'pacientesEnHabitacion' => $pacientesEnHabitacion
        ];
    }
}
