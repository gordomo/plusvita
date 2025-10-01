<?php

namespace App\Command;

use App\Repository\RoleRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowInventoryManagerRoleCommand extends Command
{
    protected static $defaultName = 'app:show-inventory-manager-role';
    protected static $defaultDescription = 'Muestra información detallada del rol Administrador de Inventario';

    private $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
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

        $io->title('Información del Rol: Administrador de Inventario');

        $role = $this->roleRepository->findByName('inventory_manager');
        
        if (!$role) {
            $io->error('El rol "Administrador de Inventario" no existe');
            return Command::FAILURE;
        }

        $io->section('Información del Rol');
        $io->definitionList(
            ['Nombre', $role->getName()],
            ['Nombre para mostrar', $role->getDisplayName()],
            ['Descripción', $role->getDescription()],
            ['Activo', $role->getIsActive() ? 'Sí' : 'No'],
            ['Es sistema', $role->getIsSystem() ? 'Sí' : 'No']
        );

        $permissions = $role->getPermissions();
        $io->section('Permisos asignados (' . count($permissions) . ')');
        
        if (empty($permissions)) {
            $io->warning('Este rol no tiene permisos asignados');
        } else {
            $permissionsByCategory = [];
            foreach ($permissions as $permission) {
                $category = $permission->getCategory();
                if (!isset($permissionsByCategory[$category])) {
                    $permissionsByCategory[$category] = [];
                }
                $permissionsByCategory[$category][] = $permission;
            }

            foreach ($permissionsByCategory as $category => $categoryPermissions) {
                $io->writeln(sprintf('<info>%s:</info>', $category));
                foreach ($categoryPermissions as $permission) {
                    $io->writeln(sprintf('  - %s (%s)', $permission->getDisplayName(), $permission->getName()));
                }
                $io->writeln('');
            }
        }

        // Mostrar usuarios con este rol
        $users = $role->getUsers();
        $io->section('Usuarios con este rol (' . count($users) . ')');
        
        if (empty($users)) {
            $io->info('No hay usuarios asignados a este rol');
        } else {
            foreach ($users as $user) {
                $io->writeln(sprintf('  - %s %s (%s)', 
                    $user->getNombre(), 
                    $user->getApellido(), 
                    $user->getEmail()
                ));
            }
        }

        $io->success('Información del rol mostrada correctamente');
        return Command::SUCCESS;
    }
}
