<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Role;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;

class MigrateModalidadesToRolesCommand extends Command
{
    protected static $defaultName = 'app:migrate-modalidades-to-roles';
    private $entityManager;
    private $userRepository;
    private $roleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Migra las modalidades de la tabla doctor a roles en user_roles')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ejecutar sin hacer cambios en la base de datos')
            ->setHelp('Este comando migra las modalidades almacenadas en la tabla doctor a roles del nuevo sistema de roles y permisos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('Modo DRY RUN - No se harán cambios en la base de datos');
        }

        // Mapeo de modalidades antiguas a nuevos roles
        $modalidadToRole = [
            // Médicos
            'Medico' => 'medico_clinico',
            'Medico Clínico' => 'medico_clinico',
            'Medico de guardia' => 'medico_guardia',
            'Fisiatra' => 'fisiatra',
            'Cardiologo' => 'cardiologo',
            'Neurologo' => 'neurologo',
            'Neumonologo' => 'neumonologo',
            'Psiquiatra' => 'psiquiatra',
            'Cirujano' => 'cirujano',
            'Traumatologo' => 'traumatologo',
            'Infectologo' => 'infectologo',
            'Urologo' => 'urologo',
            
            // Kinesiología - cada especialidad con su rol
            'Kinesiologo motora' => 'kinesiologo_motora',
            'Kinesiologo respiratorio' => 'kinesiologo_respiratorio',
            'Kinesiología respiratoria' => 'kinesiologo_respiratorio',
            'HidroTerapia motora' => 'kinesiologo_motora',
            'HidroTerapia respiratoria' => 'kinesiologo_respiratorio',
            
            // Terapeutas
            'Fonoaudiologo' => 'fonoaudiologo',
            'Terapista ocupacional' => 'terapista_ocupacional',
            'Psicologo' => 'psicologo',
            'Musicoterapia' => 'musicoterapeuta',
            
            // Enfermería - unificado
            'Enfermero' => 'enfermero',
            'Enfermera' => 'enfermero',
            'Auxiliar de enfermeria' => 'auxiliar_enfermeria',
            'Coordinador de enfermeria' => 'coordinador_enfermeria',
            
            // Nutrición
            'Nutricionista' => 'nutricionista',
            
            // Trabajo Social - unificado
            'Trabajador Social' => 'trabajador_social',
            'Trabajadora social' => 'trabajador_social',
            'Asistente Social' => 'trabajador_social',
            
            // Administrativo y Coordinación
            'Directivo' => 'director_medico',
            'Administracion' => 'administrativo',
            'Administrativo' => 'administrativo',
            'Recepcionista' => 'recepcionista',
            'Coordinador general' => 'coordinador_general',
            'Coordinador de pisos' => 'coordinador_pisos',
            
            // Otros
            'Mucamo/a' => 'mucamo',
            'Mantenimiento' => 'mantenimiento',
        ];

        $io->title('Migración de Modalidades a Roles');

        // Obtener doctores con modalidades de la base de datos
        $connection = $this->entityManager->getConnection();
        $sql = "
            SELECT d.id as doctor_id, d.nombre, d.apellido, d.email, d.modalidad, u.id as user_id
            FROM doctor d
            LEFT JOIN user u ON d.email = u.email
            WHERE d.modalidad IS NOT NULL 
            AND d.modalidad != '[]'
            AND u.id IS NOT NULL
        ";
        
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();
        $doctors = $result->fetchAllAssociative();

        $io->section('Usuarios a migrar: ' . count($doctors));

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($doctors as $doctorData) {
            $userId = $doctorData['user_id'];
            $modalidades = json_decode($doctorData['modalidad'], true);
            
            if (!is_array($modalidades)) {
                $modalidades = [$modalidades];
            }

            $user = $this->userRepository->find($userId);
            if (!$user) {
                $io->warning("Usuario ID {$userId} no encontrado");
                $errors++;
                continue;
            }

            $userName = $doctorData['nombre'] . ' ' . $doctorData['apellido'];
            $io->text("Procesando: {$userName} (User ID: {$userId})");
            $io->text("  Modalidades actuales: " . implode(', ', $modalidades));

            $rolesAsignados = [];

            foreach ($modalidades as $modalidad) {
                // Buscar el rol correspondiente
                $roleName = $modalidadToRole[$modalidad] ?? null;
                
                if (!$roleName) {
                    $io->warning("  ⚠ Modalidad '{$modalidad}' no mapeada - omitiendo");
                    continue;
                }

                $role = $this->roleRepository->findOneBy(['name' => $roleName, 'isActive' => true]);
                
                if (!$role) {
                    $io->error("  ✗ Rol '{$roleName}' no encontrado en la base de datos");
                    $errors++;
                    continue;
                }

                // Verificar si ya tiene el rol
                if ($user->getRoleEntities()->contains($role)) {
                    $io->text("  - Ya tiene rol: {$role->getDisplayName()}");
                    continue;
                }

                if (!$dryRun) {
                    $user->addRole($role);
                    $rolesAsignados[] = $role->getDisplayName();
                } else {
                    $rolesAsignados[] = $role->getDisplayName() . ' (DRY RUN)';
                }
            }

            if (count($rolesAsignados) > 0) {
                $io->success("  ✓ Roles asignados: " . implode(', ', $rolesAsignados));
                $migrated++;
            } else {
                $io->text("  - Sin cambios necesarios");
                $skipped++;
            }

            $io->newLine();
        }

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success('Cambios guardados en la base de datos');
        }

        $io->section('Resumen');
        $io->table(
            ['Estado', 'Cantidad'],
            [
                ['Migrados', $migrated],
                ['Sin cambios', $skipped],
                ['Errores', $errors],
                ['Total', count($doctors)],
            ]
        );

        if ($dryRun) {
            $io->note('Esto fue un DRY RUN. Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return Command::SUCCESS;
    }
}
