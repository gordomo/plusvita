<?php

namespace App\Command;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CorregirAsignacionCamasCommand extends Command
{
    protected static $defaultName = 'corregir-asignacion-camas';
    protected static $defaultDescription = 'Corrige inconsistencias en la asignación de camas a pacientes';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $clienteRepository = $this->entityManager->getRepository(Cliente::class);
        $habitacionRepository = $this->entityManager->getRepository(Habitacion::class);
        $cantidadCorregida = 0;
        
        // 1. Corregir pacientes con habitación pero sin cama o cama = 0
        $io->section('Corrigiendo pacientes con habitación pero sin número de cama válido');
        $clientesProblematicos = $clienteRepository->findClientesConHabitacionSinCama();
        
        foreach ($clientesProblematicos as $cliente) {
            $io->text("Corrigiendo cliente ID: {$cliente->getId()}, Nombre: {$cliente->getNombre()} {$cliente->getApellido()}");
            
            // Obtener la habitación
            $habitacion = $habitacionRepository->find($cliente->getHabitacion());
            if (!$habitacion) {
                $io->warning("Habitación no encontrada para cliente ID: {$cliente->getId()}");
                continue;
            }
            
            // Obtener camas ocupadas
            $camasOcupadas = $habitacion->getCamasOcupadas() ?? [];
            
            // Encontrar la primera cama disponible
            $camaAsignada = null;
            for ($i = 1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                if (!isset($camasOcupadas[$i])) {
                    $camaAsignada = $i;
                    break;
                }
            }
            
            // Si no hay camas disponibles, asignar la primera (podría estar sobreocupada)
            if ($camaAsignada === null) {
                $camaAsignada = 1;
                $io->warning("Habitación {$habitacion->getNombre()} está sobreocupada. Asignando cama 1 al cliente.");
            }
            
            // Asignar cama al paciente
            $cliente->setNCama($camaAsignada);
            
            // Actualizar camas ocupadas de la habitación
            $camasOcupadas[$camaAsignada] = $camaAsignada;
            $habitacion->setCamasOcupadas($camasOcupadas);
            
            // Persistir cambios
            $this->entityManager->persist($cliente);
            $this->entityManager->persist($habitacion);
            
            $cantidadCorregida++;
        }
        
        // 2. Corregir casos de múltiples pacientes en la misma cama
        $io->section('Corrigiendo casos de múltiples pacientes compartiendo la misma cama');
        $casosCompartidos = $clienteRepository->findClientesCompartiendoCama();
        
        foreach ($casosCompartidos as $caso) {
            $habitacionId = $caso['habitacion'];
            $nCama = $caso['n_cama'];
            $cantidad = $caso['cantidad'];
            
            $io->text("Encontrados {$cantidad} pacientes compartiendo la habitación ID: {$habitacionId}, cama: {$nCama}");
            
            // Obtener la habitación
            $habitacion = $habitacionRepository->find($habitacionId);
            if (!$habitacion) {
                $io->warning("Habitación no encontrada con ID: {$habitacionId}");
                continue;
            }
            
            // Obtener todos los pacientes en esta habitación y cama
            $pacientesCompartiendo = $this->entityManager->createQuery(
                'SELECT c FROM App\Entity\Cliente c 
                WHERE c.habitacion = :habitacionId 
                AND c.nCama = :nCama 
                AND c.fEgreso IS NULL 
                AND c.derivado = 0 
                AND c.dePermiso = 0'
            )
            ->setParameter('habitacionId', $habitacionId)
            ->setParameter('nCama', $nCama)
            ->getResult();
            
            // Mantener el primero en la cama actual y reasignar el resto
            $primeraVez = true;
            foreach ($pacientesCompartiendo as $paciente) {
                if ($primeraVez) {
                    $primeraVez = false;
                    continue; // Dejar el primer paciente en su cama
                }
                
                $io->text("Reasignando paciente ID: {$paciente->getId()}, Nombre: {$paciente->getNombre()} {$paciente->getApellido()}");
                
                // Obtener camas ocupadas
                $camasOcupadas = $habitacion->getCamasOcupadas() ?? [];
                
                // Encontrar una cama disponible diferente
                $nuevaCama = null;
                for ($i = 1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                    if ($i != $nCama && !isset($camasOcupadas[$i])) {
                        $nuevaCama = $i;
                        break;
                    }
                }
                
                if ($nuevaCama === null) {
                    $io->warning("No hay camas disponibles en la habitación {$habitacion->getNombre()}. No se puede reasignar al paciente.");
                    continue;
                }
                
                // Asignar nueva cama al paciente
                $paciente->setNCama($nuevaCama);
                
                // Actualizar camas ocupadas de la habitación
                $camasOcupadas[$nuevaCama] = $nuevaCama;
                $habitacion->setCamasOcupadas($camasOcupadas);
                
                // Persistir cambios
                $this->entityManager->persist($paciente);
                $this->entityManager->persist($habitacion);
                
                $cantidadCorregida++;
            }
        }
        
        // Guardar todos los cambios
        $this->entityManager->flush();
        
        if ($cantidadCorregida > 0) {
            $io->success("Se corrigieron $cantidadCorregida pacientes con inconsistencias en la asignación de camas.");
        } else {
            $io->success("No se encontraron inconsistencias en la asignación de camas.");
        }
        
        return Command::SUCCESS;
    }
}
