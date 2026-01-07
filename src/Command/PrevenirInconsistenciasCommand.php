<?php

namespace App\Command;

use App\Service\HabitacionService;
use App\Repository\HistoriaPacienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrevenirInconsistenciasCommand extends Command
{
    protected static $defaultName = 'app:prevenir-inconsistencias';
    protected static $defaultDescription = 'Ejecuta verificaciones periódicas para prevenir inconsistencias en las camas ocupadas';

    private $entityManager;
    private $habitacionService;
    private $historiaPacienteRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        HabitacionService $habitacionService,
        HistoriaPacienteRepository $historiaPacienteRepository
    ) {
        $this->entityManager = $entityManager;
        $this->habitacionService = $habitacionService;
        $this->historiaPacienteRepository = $historiaPacienteRepository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Prevención de inconsistencias en camas ocupadas');
        
        $habitacionRepository = $this->entityManager->getRepository(\App\Entity\Habitacion::class);
        $habitaciones = $habitacionRepository->findAll();
        
        $inconsistenciasEncontradas = 0;
        $inconsistenciasCorregidas = 0;
        
        foreach ($habitaciones as $habitacion) {
            $resultado = $this->habitacionService->verificarInconsistencias($habitacion);
            
            if ($resultado['tieneInconsistencia']) {
                $inconsistenciasEncontradas++;
                $io->warning("Inconsistencia detectada en habitación {$habitacion->getNombre()}");
                $io->text("  - Camas ocupadas en BD: " . json_encode($resultado['camasOcupadasActuales']));
                $io->text("  - Camas ocupadas reales: " . json_encode($resultado['camasOcupadasReales']));
                
                // Corregir automáticamente
                $this->habitacionService->actualizarCamasOcupadas($habitacion);
                $inconsistenciasCorregidas++;
                
                $io->success("  ✓ Corregida automáticamente");
            }
        }
        
        // Guardar todos los cambios
        $this->entityManager->flush();
        
        if ($inconsistenciasEncontradas > 0) {
            $io->success("Se encontraron y corrigieron {$inconsistenciasCorregidas} inconsistencias de {$inconsistenciasEncontradas} detectadas.");
        } else {
            $io->success("No se encontraron inconsistencias. El sistema está sincronizado correctamente.");
        }
        
        return Command::SUCCESS;
    }
}
