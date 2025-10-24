<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateLegacyRolesToRolesCommand extends Command
{
    protected static $defaultName = 'app:migrate-legacy-roles';
    protected static $defaultDescription = 'Migra los legacy_roles (ROLE_ADMIN, ROLE_USER, etc.) a la tabla user_roles';

    private $entityManager;
    private $userRepository;
    private $roleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ejecutar en modo prueba sin guardar cambios');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        
        $io->title('Migrando Legacy Roles a Sistema de Roles');
        
        if ($dryRun) {
            $io->warning('MODO PRUEBA - No se guardarán cambios en la base de datos');
        }

        try {
            // Mapeo de legacy roles a nuevos roles
            $roleMapping = [
                'ROLE_ADMIN' => 'admin',
                'ROLE_USER' => null, // ROLE_USER era genérico, se ignora (ya tienen roles de doctor/enfermero)
                'ROLE_EDIT_HC' => null, // Este era un permiso, no un rol - se ignora por ahora
            ];

            // Obtener el rol de admin
            $adminRole = $this->roleRepository->findOneBy(['name' => 'admin']);
            if (!$adminRole) {
                $io->error('Rol "admin" no encontrado. Ejecuta primero: php bin/console app:initialize-roles');
                return Command::FAILURE;
            }

            // Obtener usuarios con legacy_roles
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('u')
               ->from('App\Entity\User', 'u')
               ->where("u.legacyRoles != '[]'")
               ->andWhere('u.legacyRoles IS NOT NULL');
            
            $users = $qb->getQuery()->getResult();
            
            $io->writeln(sprintf('Se encontraron %d usuarios con legacy_roles para migrar', count($users)));

            $migratedCount = 0;
            $skippedCount = 0;
            $totalRolesAssigned = 0;

            foreach ($users as $user) {
                $legacyRoles = $user->getLegacyRoles();
                
                // Mostrar usuario actual
                $io->writeln('');
                $io->writeln(sprintf('📋 Procesando: %s %s (ID: %d, Email: %s)', 
                    $user->getNombre() ?? '',
                    $user->getApellido() ?? '',
                    $user->getId(),
                    $user->getEmail()
                ));
                $io->writeln(sprintf('   Legacy roles: %s', implode(', ', $legacyRoles)));

                $rolesAdded = [];
                $hasChanges = false;

                foreach ($legacyRoles as $legacyRole) {
                    // Mapear el legacy role
                    if (isset($roleMapping[$legacyRole]) && $roleMapping[$legacyRole] !== null) {
                        $newRoleName = $roleMapping[$legacyRole];
                        $role = $this->roleRepository->findOneBy(['name' => $newRoleName]);
                        
                        if ($role) {
                            // Verificar si el usuario ya tiene este rol
                            if (!$user->hasRole($newRoleName)) {
                                if (!$dryRun) {
                                    $user->addRole($role);
                                }
                                $rolesAdded[] = $role->getDisplayName();
                                $hasChanges = true;
                                $totalRolesAssigned++;
                            } else {
                                $io->writeln(sprintf('   ⏭️  Ya tiene rol: %s', $role->getDisplayName()));
                            }
                        } else {
                            $io->writeln(sprintf('   ⚠️  Rol no encontrado: %s', $newRoleName));
                        }
                    } elseif ($legacyRole === 'ROLE_USER' || $legacyRole === 'ROLE_EDIT_HC') {
                        // Estos se ignoran - ROLE_USER era genérico, ROLE_EDIT_HC es un permiso
                        $io->writeln(sprintf('   ℹ️  Ignorado (genérico): %s', $legacyRole));
                    } else {
                        $io->writeln(sprintf('   ⚠️  Legacy role desconocido: %s', $legacyRole));
                    }
                }

                if ($hasChanges) {
                    $io->writeln(sprintf('   ✅ Roles asignados: %s', implode(', ', $rolesAdded)));
                    $migratedCount++;
                } else {
                    $io->writeln('   ⏭️  Sin cambios necesarios');
                    $skippedCount++;
                }
            }

            // Guardar cambios
            if (!$dryRun && $migratedCount > 0) {
                $this->entityManager->flush();
                $io->success('Cambios guardados en la base de datos');
            }

            // Mostrar resumen
            $io->section('Resumen de la migración');
            $io->writeln(sprintf('✅ Usuarios %s: %d', $dryRun ? 'para migrar' : 'migrados', $migratedCount));
            $io->writeln(sprintf('⏭️  Usuarios sin cambios: %d', $skippedCount));
            $io->writeln(sprintf('🎯 Total de roles %s: %d', $dryRun ? 'para asignar' : 'asignados', $totalRolesAssigned));
            $io->writeln(sprintf('📊 Total de usuarios procesados: %d', count($users)));

            if ($migratedCount > 0) {
                $io->newLine();
                $io->note([
                    'NOTA IMPORTANTE:',
                    '- ROLE_ADMIN → admin (acceso completo)',
                    '- ROLE_USER → ignorado (es genérico, ya tienen roles específicos)',
                    '- ROLE_EDIT_HC → ignorado (es un permiso, no un rol)',
                    '- Los usuarios mantienen sus roles existentes de doctor/enfermero'
                ]);
            }

            if ($dryRun) {
                $io->warning('MODO PRUEBA - Ningún cambio fue guardado. Ejecuta sin --dry-run para aplicar los cambios.');
                return Command::SUCCESS;
            }

            $io->success('Migración completada exitosamente');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error durante la migración: ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
