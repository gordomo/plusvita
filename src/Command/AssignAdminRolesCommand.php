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

class AssignAdminRolesCommand extends Command
{
    protected static $defaultName = 'app:assign-admin-roles';
    protected static $defaultDescription = 'Assign admin role to users with ROLE_ADMIN in legacyRoles';

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
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Asignando rol de administrador a usuarios legacy');

        try {
            // Obtener todos los usuarios
            $allUsers = $this->userRepository->findAll();
            $io->writeln(sprintf('Se encontraron %d usuarios en el sistema', count($allUsers)));

            // Obtener el rol de admin
            $adminRole = $this->roleRepository->findOneBy(['name' => 'admin']);
            if (!$adminRole) {
                $io->error('Rol "admin" no encontrado. Ejecuta primero: php bin/console app:initialize-roles');
                return Command::FAILURE;
            }

            $assignedCount = 0;
            $alreadyHasCount = 0;
            $noLegacyCount = 0;

            foreach ($allUsers as $user) {
                $legacyRoles = $user->getLegacyRoles();

                // Verificar si tiene legacyRoles
                if (empty($legacyRoles)) {
                    $noLegacyCount++;
                    continue;
                }

                // Verificar si ROLE_ADMIN está en legacyRoles
                if (in_array('ROLE_ADMIN', $legacyRoles)) {
                    // Verificar si ya tiene el rol admin
                    if ($user->hasRole('admin')) {
                        $io->writeln(sprintf('⏭️  Usuario %s ya tiene rol admin', $user->getEmail()));
                        $alreadyHasCount++;
                        continue;
                    }

                    // Asignar el rol admin
                    $user->addRole($adminRole);
                    $this->entityManager->persist($user);

                    $assignedCount++;
                    $io->writeln(sprintf('✅ Rol admin asignado a: %s', $user->getEmail()));
                }
            }

            // Ejecutar flush
            if ($assignedCount > 0) {
                $this->entityManager->flush();
            }

            // Mostrar resumen
            $io->section('Resumen de asignación de roles');
            $io->writeln(sprintf('✅ Roles admin asignados: %d', $assignedCount));
            $io->writeln(sprintf('⏭️  Usuarios que ya tenían rol admin: %d', $alreadyHasCount));
            $io->writeln(sprintf('⚠️  Usuarios sin legacyRoles: %d', $noLegacyCount));

            $io->success('Asignación de roles completada exitosamente');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error durante la asignación de roles: ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
