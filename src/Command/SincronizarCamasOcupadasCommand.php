<?php

namespace App\Command;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Repository\ClienteRepository;
use App\Repository\HabitacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SincronizarCamasOcupadasCommand extends Command
{
    protected static $defaultName = 'app:sincronizar-camas-ocupadas';
    protected static $defaultDescription = 'Sincroniza el campo camas_ocupadas de las habitaciones con los pacientes reales asignados';

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
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Sincronizando camas ocupadas con pacientes reales');
        
        $habitaciones = $this->habitacionRepository->findAll();
        $habitacionesCorregidas = 0;
        
        foreach ($habitaciones as $habitacion) {
            $io->section("Procesando habitación: {$habitacion->getNombre()}");
            
            // Obtener todos los pacientes activos en esta habitación
            $pacientesEnHabitacion = $this->clienteRepository->findClienteEnHabitacion(
                $habitacion, 
                false, // incluir todos los pacientes, no solo los con cama física
                true   // incluir pacientes de permiso
            );
            
            $io->text("Pacientes encontrados en la habitación: " . count($pacientesEnHabitacion));
            
            // Reconstruir el array de camas ocupadas basado en los pacientes reales
            $camasOcupadasReales = [];
            $pacientesConCamaFisica = 0;
            
            foreach ($pacientesEnHabitacion as $paciente) {
                $nCama = $paciente->getNCama();
                
                if ($nCama !== null && $nCama > 0) {
                    $camasOcupadasReales[$nCama] = $nCama;
                    $pacientesConCamaFisica++;
                    $io->text("  - Paciente {$paciente->getNombre()} {$paciente->getApellido()} en cama {$nCama}");
                } else {
                    $io->text("  - Paciente {$paciente->getNombre()} {$paciente->getApellido()} sin cama física asignada");
                }
            }
            
            // Obtener el estado actual de camas ocupadas en la base de datos
            $camasOcupadasActuales = $habitacion->getCamasOcupadas() ?? [];
            
            $io->text("Camas ocupadas en BD: " . json_encode($camasOcupadasActuales));
            $io->text("Camas ocupadas reales: " . json_encode($camasOcupadasReales));
            
            // Verificar si hay diferencia
            if ($camasOcupadasActuales != $camasOcupadasReales) {
                $io->warning("¡Inconsistencia detectada! Corrigiendo...");
                
                // Actualizar las camas ocupadas
                $habitacion->setCamasOcupadas($camasOcupadasReales);
                $this->entityManager->persist($habitacion);
                
                $habitacionesCorregidas++;
                
                $io->success("Habitación {$habitacion->getNombre()} corregida:");
                $io->text("  - Camas totales: {$habitacion->getCamasDisponibles()}");
                $io->text("  - Camas ocupadas: " . count($camasOcupadasReales));
                $io->text("  - Camas disponibles: " . ($habitacion->getCamasDisponibles() - count($camasOcupadasReales)));
            } else {
                $io->text("✓ Habitación {$habitacion->getNombre()} está sincronizada");
            }
            
            $io->newLine();
        }
        
        // Guardar todos los cambios
        $this->entityManager->flush();
        
        if ($habitacionesCorregidas > 0) {
            $io->success("Se corrigieron {$habitacionesCorregidas} habitaciones con inconsistencias en camas ocupadas.");
        } else {
            $io->success("Todas las habitaciones están correctamente sincronizadas.");
        }
        
        return Command::SUCCESS;
    }
}
