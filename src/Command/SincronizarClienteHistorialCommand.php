<?php

namespace App\Command;

use App\Entity\HistoriaPaciente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando para sincronizar la tabla cliente con historia_paciente
 *
 * Corrige casos donde la tabla cliente tiene datos diferentes al último registro
 * de historia_paciente, lo cual puede causar problemas en liquidaciones.
 */
class SincronizarClienteHistorialCommand extends Command
{
    protected static $defaultName = 'app:sincronizar-cliente-historial';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sincroniza la tabla cliente con historia_paciente para campos críticos')
            ->setHelp('Este comando identifica pacientes donde la tabla cliente tiene valores diferentes al último registro de historia_paciente y los sincroniza.')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Aplica la sincronización automáticamente')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Muestra qué cambios se aplicarían sin ejecutarlos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $dryRun = $input->getOption('dry-run');

        $io->title('🔄 SINCRONIZACIÓN: Cliente vs Historia_Paciente');

        $io->warning('⚠️  Este comando corrige inconsistencias entre tablas que pueden afectar liquidaciones.');
        $io->text('Los campos críticos que se sincronizan son: modalidad, ambulatorio, motivo_ing, sistema_emergencia');
        $io->newLine();

        // Consulta para encontrar diferencias
        $sql = "
            SELECT
                c.id,
                CONCAT(c.nombre, ' ', c.apellido) as paciente,
                c.modalidad as cliente_modalidad,
                COALESCE(CAST(hp.modalidad AS UNSIGNED), c.modalidad) as historial_modalidad,
                c.motivo_ing as cliente_patologia,
                COALESCE(CAST(hp.patologia AS UNSIGNED), c.motivo_ing) as historial_patologia,
                c.sistema_de_emergencia_nombre as cliente_sistema,
                COALESCE(hp.sistema_de_emergencia, c.sistema_de_emergencia_nombre) as historial_sistema,
                hp.fecha as ultimo_cambio_historial
            FROM cliente c
            LEFT JOIN (
                SELECT hp.* FROM (
                    SELECT hp1.*, ROW_NUMBER() OVER (PARTITION BY hp1.cliente_id ORDER BY hp1.fecha DESC) as rn
                    FROM historia_paciente hp1
                ) hp WHERE hp.rn = 1
            ) hp ON c.id = hp.cliente_id
            WHERE c.modalidad != COALESCE(CAST(hp.modalidad AS UNSIGNED), c.modalidad)
               OR c.motivo_ing != COALESCE(CAST(hp.patologia AS UNSIGNED), c.motivo_ing)
               OR c.sistema_de_emergencia_nombre != COALESCE(hp.sistema_de_emergencia, c.sistema_de_emergencia_nombre)
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $pacientesInconsistentes = $result->fetchAllAssociative();

        if (empty($pacientesInconsistentes)) {
            $io->success('✅ No se encontraron inconsistencias entre cliente e historia_paciente.');
            return Command::SUCCESS;
        }

        $io->error(sprintf('❌ Se encontraron %d pacientes con INCONSISTENCIAS:', count($pacientesInconsistentes)));
        $io->text('Estos pacientes tienen datos diferentes entre la tabla cliente y el historial.');
        $io->newLine();

        $tableRows = [];
        foreach ($pacientesInconsistentes as $paciente) {
            $problemas = [];

            if ($paciente['cliente_modalidad'] != $paciente['historial_modalidad']) {
                $problemas[] = sprintf('Modalidad: %s → %s',
                    $paciente['cliente_modalidad'],
                    $paciente['historial_modalidad']
                );
            }

            if ($paciente['cliente_patologia'] != $paciente['historial_patologia']) {
                $problemas[] = sprintf('Patología: %s → %s',
                    $paciente['cliente_patologia'] ?? 'NULL',
                    $paciente['historial_patologia'] ?? 'NULL'
                );
            }

            if ($paciente['cliente_sistema'] != $paciente['historial_sistema']) {
                $problemas[] = sprintf('Sistema: %s → %s',
                    $paciente['cliente_sistema'] ?? 'NULL',
                    $paciente['historial_sistema'] ?? 'NULL'
                );
            }

            $tableRows[] = [
                $paciente['id'],
                $paciente['paciente'],
                implode('; ', $problemas),
                $paciente['ultimo_cambio_historial'] ?? 'Sin historial'
            ];
        }

        $io->table(
            ['ID', 'Paciente', 'Problemas (Cliente → Historial)', 'Último Historial'],
            $tableRows
        );

        if ($dryRun) {
            $io->note('🔍 Modo dry-run: No se aplicaron cambios.');
            $io->text('Use --fix para aplicar la sincronización.');
            return Command::SUCCESS;
        }

        if (!$fix) {
            $io->warning('⚠️  IMPORTANTE: Esta sincronización actualizará la tabla cliente.');
            $io->text('Los valores del historial se considerarán como la "fuente de verdad".');
            $io->newLine();
            $io->text('💡 Use --fix para aplicar los cambios.');
            $io->text('💡 Use --dry-run para ver qué cambios se aplicarían sin ejecutarlos.');
            return Command::SUCCESS;
        }

        // Aplicar sincronización
        $io->section('Aplicando sincronización...');

        $sincronizados = 0;
        foreach ($pacientesInconsistentes as $paciente) {
            $cliente = $this->entityManager->getRepository(\App\Entity\Cliente::class)->find($paciente['id']);

            if (!$cliente) {
                $io->warning("Cliente ID {$paciente['id']} no encontrado, omitiendo.");
                continue;
            }

            // Sincronizar campos críticos
            $cambios = [];

            if ($paciente['cliente_modalidad'] != $paciente['historial_modalidad']) {
                $cliente->setModalidad($paciente['historial_modalidad']);
                $cliente->setAmbulatorio($paciente['historial_modalidad'] == 1);
                $cambios[] = "modalidad {$paciente['cliente_modalidad']} → {$paciente['historial_modalidad']}";
            }

            if ($paciente['cliente_patologia'] != $paciente['historial_patologia']) {
                $cliente->setMotivoIng($paciente['historial_patologia']);
                $cambios[] = "patología {$paciente['cliente_patologia']} → {$paciente['historial_patologia']}";
            }

            if ($paciente['cliente_sistema'] != $paciente['historial_sistema']) {
                $cliente->setSistemaDeEmergenciaNombre($paciente['historial_sistema']);
                $cambios[] = "sistema {$paciente['cliente_sistema']} → {$paciente['historial_sistema']}";
            }

            if (!empty($cambios)) {
                $this->entityManager->persist($cliente);
                $io->writeln(sprintf(
                    '  ✅ %s: %s',
                    $paciente['paciente'],
                    implode(', ', $cambios)
                ));
                $sincronizados++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ Se sincronizaron %d pacientes.', $sincronizados));
        $io->text('La tabla cliente ahora está sincronizada con historia_paciente.');
        $io->newLine();

        // Verificación final
        $io->section('Verificación final...');
        $result = $stmt->executeQuery();
        $remaining = $result->fetchAllAssociative();

        if (empty($remaining)) {
            $io->success('✅ Todas las inconsistencias han sido corregidas.');
            $io->text('Las liquidaciones ahora usarán datos consistentes.');
        } else {
            $io->error(sprintf('❌ Aún quedan %d pacientes inconsistentes.', count($remaining)));
            return Command::FAILURE;
        }

        $io->newLine();
        $io->note('📋 RECOMENDACIONES:');
        $io->text('1. Verificar que las liquidaciones ahora funcionen correctamente');
        $io->text('2. Monitorear que no se produzcan nuevas inconsistencias');
        $io->text('3. Considerar agregar triggers o constraints para mantener consistencia');

        return Command::SUCCESS;
    }
}
