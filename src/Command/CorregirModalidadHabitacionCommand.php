<?php

namespace App\Command;

use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando para corregir la inconsistencia crítica entre modalidad y habitación asignada
 *
 * Corrige automáticamente pacientes que tienen habitación asignada pero están marcados como ambulatorios
 * Esta inconsistencia causa errores graves en las liquidaciones.
 */
class CorregirModalidadHabitacionCommand extends Command
{
    protected static $defaultName = 'app:corregir-modalidad-habitacion';

    private $entityManager;
    private $clienteRepository;

    public function __construct(EntityManagerInterface $entityManager, ClienteRepository $clienteRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->clienteRepository = $clienteRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Corrige pacientes con habitación asignada pero marcados como ambulatorios')
            ->setHelp('Este comando identifica y corrige automáticamente la inconsistencia crítica donde pacientes internados físicamente aparecen como ambulatorios en el sistema, causando errores en liquidaciones.')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Aplica las correcciones automáticamente')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Muestra qué cambios se aplicarían sin ejecutarlos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $dryRun = $input->getOption('dry-run');

        $io->title('🔧 Corrección de Inconsistencia Crítica: Modalidad vs Habitación');

        $io->warning('🚨 ESTA INCONSISTENCIA CAUSA ERRORES GRAVES EN LIQUIDACIONES');
        $io->text('Los pacientes con habitación asignada deben estar marcados como INTERNADOS (modalidad=2)');
        $io->text('Si aparecen como AMBULATORIOS (modalidad=1), las liquidaciones los clasifican incorrectamente.');
        $io->newLine();

        // Buscar pacientes con inconsistencia crítica
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
           ->from('App:Cliente', 'c')
           ->where('c.habitacion IS NOT NULL')
           ->andWhere('c.habitacion != :empty')
           ->andWhere($qb->expr()->orX(
               $qb->expr()->eq('c.modalidad', 1),
               $qb->expr()->eq('c.ambulatorio', 1)
           ))
           ->andWhere('c.fEgreso IS NULL') // Solo pacientes activos
           ->setParameter('empty', '');

        $pacientesInconsistentes = $qb->getQuery()->getResult();

        if (empty($pacientesInconsistentes)) {
            $io->success('✅ No se encontraron inconsistencias críticas.');
            $io->text('Todos los pacientes con habitación están correctamente marcados como internados.');
            return Command::SUCCESS;
        }

        $io->error(sprintf('❌ Se encontraron %d pacientes con INCONSISTENCIA CRÍTICA:', count($pacientesInconsistentes)));
        $io->text('Estos pacientes causan errores en las liquidaciones de diciembre y futuras.');
        $io->newLine();

        $tableRows = [];
        foreach ($pacientesInconsistentes as $paciente) {
            $problema = [];
            if ($paciente->getModalidad() == 1) {
                $problema[] = 'Modalidad incorrecta (es 1, debe ser 2)';
            }
            if ($paciente->getAmbulatorio() == 1) {
                $problema[] = 'Marcado como ambulatorio';
            }

            $tableRows[] = [
                $paciente->getId(),
                $paciente->getNombre() . ' ' . $paciente->getApellido(),
                $paciente->getModalidad(),
                $paciente->getAmbulatorio() ? 'Sí' : 'No',
                $paciente->getHabitacion(),
                $paciente->getNCama(),
                implode(' + ', $problema)
            ];
        }

        $io->table(
            ['ID', 'Paciente', 'Modalidad', 'Ambulatorio', 'Habitación', 'Cama', 'Problema'],
            $tableRows
        );

        if ($dryRun) {
            $io->info('🔍 Modo dry-run: No se aplicaron cambios.');
            $io->text('Use --fix para aplicar las correcciones.');
            return Command::SUCCESS;
        }

        if (!$fix) {
            $io->warning('⚠️  IMPORTANTE: Esta inconsistencia afecta las liquidaciones.');
            $io->text('Los pacientes listados aparecen como AMBULATORIOS en liquidaciones cuando deberían ser INTERNADOS.');
            $io->newLine();
            $io->text('💡 Use --fix para aplicar las correcciones automáticamente.');
            $io->text('💡 Use --dry-run para ver qué cambios se aplicarían sin ejecutarlos.');
            return Command::SUCCESS;
        }

        // Aplicar correcciones
        $io->section('Aplicando correcciones críticas...');

        $corrected = 0;
        foreach ($pacientesInconsistentes as $paciente) {
            $paciente->setModalidad(2); // INTERNADO
            $paciente->setAmbulatorio(false);

            $io->writeln(sprintf(
                '  ✅ Corregido: %s %s (ID: %d) - Era %s, ahora INTERNADO',
                $paciente->getNombre(),
                $paciente->getApellido(),
                $paciente->getId(),
                $paciente->getModalidad() == 1 ? 'AMBULATORIO' : 'MODALIDAD INCORRECTA'
            ));

            $corrected++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ Se corrigieron %d pacientes críticos.', $corrected));
        $io->text('Estos pacientes ahora aparecerán correctamente como INTERNADOS en las liquidaciones.');
        $io->newLine();

        // Verificación final
        $io->section('Verificación final...');
        $remaining = $qb->getQuery()->getResult();

        if (empty($remaining)) {
            $io->success('✅ Todas las inconsistencias críticas han sido corregidas.');
            $io->text('Las liquidaciones futuras funcionarán correctamente.');
        } else {
            $io->error(sprintf('❌ Aún quedan %d pacientes inconsistentes.', count($remaining)));
            return Command::FAILURE;
        }

        $io->newLine();
        $io->info('📋 RECOMENDACIONES:');
        $io->text('1. Regenerar las liquidaciones de diciembre para los profesionales afectados.');
        $io->text('2. Programar este comando para ejecutarse diariamente.');
        $io->text('3. Monitorear que no se produzcan nuevas inconsistencias.');

        return Command::SUCCESS;
    }
}
