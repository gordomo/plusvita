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
 * Comando para verificar y corregir inconsistencias de modalidad en pacientes
 *
 * Verifica que los pacientes con habitación asignada estén marcados como internados
 * y no como ambulatorios, previniendo errores en liquidaciones.
 */
class VerifyModalidadConsistencyCommand extends Command
{
    protected static $defaultName = 'app:verify-modalidad-consistency';

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
            ->setDescription('Verifica y corrige inconsistencias de modalidad en pacientes')
            ->setHelp('Este comando identifica pacientes que tienen habitación asignada pero están marcados como ambulatorios, lo cual causa errores en las liquidaciones.')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Aplica las correcciones automáticamente')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Muestra qué cambios se aplicarían sin ejecutarlos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $dryRun = $input->getOption('dry-run');

        $io->title('Verificación de Consistencia de Modalidad de Pacientes');

        // Buscar pacientes inconsistentes
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
           ->from('App:Cliente', 'c')
           ->where('c.habitacion IS NOT NULL')
           ->andWhere('c.habitacion != :empty')
           ->andWhere($qb->expr()->orX(
               $qb->expr()->eq('c.modalidad', 1),
               $qb->expr()->eq('c.ambulatorio', 1)
           ))
           ->setParameter('empty', '');

        $pacientesInconsistentes = $qb->getQuery()->getResult();

        if (empty($pacientesInconsistentes)) {
            $io->success('✅ No se encontraron inconsistencias de modalidad.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('⚠️  Se encontraron %d pacientes con inconsistencias:', count($pacientesInconsistentes)));

        $tableRows = [];
        foreach ($pacientesInconsistentes as $paciente) {
            $problema = '';
            if ($paciente->getModalidad() == 1) {
                $problema = 'Modalidad incorrecta (debe ser internado)';
            }
            if ($paciente->getAmbulatorio() == 1) {
                $problema .= ($problema ? ' + ' : '') . 'Marcado como ambulatorio';
            }

            $tableRows[] = [
                $paciente->getId(),
                $paciente->getNombre() . ' ' . $paciente->getApellido(),
                $paciente->getModalidad(),
                $paciente->getAmbulatorio() ? 'Sí' : 'No',
                $paciente->getHabitacion(),
                $paciente->getNCama(),
                $problema
            ];
        }

        $io->table(
            ['ID', 'Paciente', 'Modalidad', 'Ambulatorio', 'Habitación', 'Cama', 'Problema'],
            $tableRows
        );

        if ($dryRun) {
            $io->info('🔍 Modo dry-run: No se aplicaron cambios.');
            return Command::SUCCESS;
        }

        if (!$fix) {
            $io->note('💡 Use --fix para aplicar las correcciones automáticamente.');
            $io->note('💡 Use --dry-run para ver qué cambios se aplicarían sin ejecutarlos.');
            return Command::SUCCESS;
        }

        // Aplicar correcciones
        $io->section('Aplicando correcciones...');

        $corrected = 0;
        foreach ($pacientesInconsistentes as $paciente) {
            $paciente->setModalidad(2); // Internado
            $paciente->setAmbulatorio(false);

            $io->writeln(sprintf(
                '  ✅ Corregido: %s %s (ID: %d)',
                $paciente->getNombre(),
                $paciente->getApellido(),
                $paciente->getId()
            ));

            $corrected++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ Se corrigieron %d pacientes.', $corrected));

        // Verificación final
        $io->section('Verificación final...');
        $remaining = $qb->getQuery()->getResult();

        if (empty($remaining)) {
            $io->success('✅ Todas las inconsistencias han sido corregidas.');
        } else {
            $io->error(sprintf('❌ Aún quedan %d pacientes inconsistentes.', count($remaining)));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
