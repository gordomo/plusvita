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
 * Comando para corregir fechas de nacimiento incorrectas
 *
 * Identifica y corrige pacientes con fechas de nacimiento sospechosas:
 * - Fechas iguales a hoy
 * - Fechas futuras
 * - Fechas muy recientes (menos de 1 año)
 */
class CorregirFechasNacimientoCommand extends Command
{
    protected static $defaultName = 'app:corregir-fechas-nacimiento';

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
            ->setDescription('Corrige fechas de nacimiento incorrectas (hoy, futuras o muy recientes)')
            ->setHelp('Este comando identifica pacientes con fechas de nacimiento sospechosas y las establece como NULL para que el usuario las corrija manualmente.')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Aplica las correcciones automáticamente')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Muestra qué cambios se aplicarían sin ejecutarlos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $dryRun = $input->getOption('dry-run');

        $io->title('🔧 Corrección de Fechas de Nacimiento Incorrectas');

        $io->warning('⚠️  Este comando corrige fechas de nacimiento sospechosas.');
        $io->text('Se consideran sospechosas: fechas iguales a hoy, futuras, o muy recientes (menos de 1 año).');
        $io->text('Estas fechas se establecerán como NULL para que el usuario las corrija manualmente.');
        $io->newLine();

        $hoy = new \DateTime();
        $hoy->setTime(0, 0, 0);
        $hoyFin = new \DateTime();
        $hoyFin->setTime(23, 59, 59);
        $haceUnAno = new \DateTime('-1 year');

        // Buscar pacientes con fechas sospechosas
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
           ->from('App:Cliente', 'c')
           ->where('c.fNacimiento IS NOT NULL')
           ->andWhere($qb->expr()->orX(
               // Fecha igual a hoy (entre inicio y fin del día)
               $qb->expr()->andX(
                   $qb->expr()->gte('c.fNacimiento', ':hoy'),
                   $qb->expr()->lte('c.fNacimiento', ':hoyFin')
               ),
               // Fecha futura
               $qb->expr()->gt('c.fNacimiento', ':hoyFin'),
               // Fecha muy reciente (menos de 1 año)
               $qb->expr()->gt('c.fNacimiento', ':haceUnAno')
           ))
           ->setParameter('hoy', $hoy)
           ->setParameter('hoyFin', $hoyFin)
           ->setParameter('haceUnAno', $haceUnAno);

        $pacientesProblematicos = $qb->getQuery()->getResult();

        if (empty($pacientesProblematicos)) {
            $io->success('✅ No se encontraron pacientes con fechas de nacimiento sospechosas.');
            return Command::SUCCESS;
        }

        $io->error(sprintf('❌ Se encontraron %d pacientes con fechas de nacimiento SOSPECHOSAS:', count($pacientesProblematicos)));
        $io->newLine();

        $tableRows = [];
        foreach ($pacientesProblematicos as $paciente) {
            $fechaNac = $paciente->getFNacimiento();
            $problema = '';

            if ($fechaNac->format('Y-m-d') === $hoy->format('Y-m-d')) {
                $problema = '❌ Fecha de hoy';
            } elseif ($fechaNac > $hoy) {
                $problema = '❌ Fecha futura';
            } elseif ($fechaNac > $haceUnAno) {
                $problema = '⚠️  Menos de 1 año';
            }

            $tableRows[] = [
                $paciente->getId(),
                $paciente->getNombre() . ' ' . $paciente->getApellido(),
                $fechaNac->format('Y-m-d'),
                $problema
            ];
        }

        $io->table(
            ['ID', 'Paciente', 'Fecha Actual', 'Problema'],
            $tableRows
        );

        if ($dryRun) {
            $io->note('🔍 Modo dry-run: No se aplicaron cambios.');
            $io->text('Use --fix para establecer estas fechas como NULL.');
            return Command::SUCCESS;
        }

        if (!$fix) {
            $io->warning('⚠️  IMPORTANTE: Estas fechas se establecerán como NULL.');
            $io->text('Los usuarios deberán corregirlas manualmente en el formulario.');
            $io->newLine();
            $io->text('💡 Use --fix para aplicar los cambios.');
            $io->text('💡 Use --dry-run para ver qué cambios se aplicarían sin ejecutarlos.');
            return Command::SUCCESS;
        }

        // Aplicar correcciones
        $io->section('Aplicando correcciones...');

        $corregidos = 0;
        foreach ($pacientesProblematicos as $paciente) {
            $paciente->setFNacimiento(null);

            $io->writeln(sprintf(
                '  ✅ %s %s (ID: %d) - Fecha establecida como NULL',
                $paciente->getNombre(),
                $paciente->getApellido(),
                $paciente->getId()
            ));

            $corregidos++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ Se corrigieron %d pacientes.', $corregidos));
        $io->text('Las fechas sospechosas ahora están como NULL y deberán ser corregidas manualmente.');
        $io->newLine();

        // Verificación final
        $io->section('Verificación final...');
        $remaining = $qb->getQuery()->getResult();

        if (empty($remaining)) {
            $io->success('✅ Todas las fechas sospechosas han sido corregidas.');
        } else {
            $io->error(sprintf('❌ Aún quedan %d pacientes con fechas sospechosas.', count($remaining)));
            return Command::FAILURE;
        }

        $io->newLine();
        $io->note('📋 RECOMENDACIONES:');
        $io->text('1. Los usuarios deberán corregir manualmente las fechas de nacimiento en el formulario');
        $io->text('2. Verificar que el formulario ahora previene este problema');
        $io->text('3. Monitorear que no se produzcan nuevas fechas incorrectas');

        return Command::SUCCESS;
    }
}
