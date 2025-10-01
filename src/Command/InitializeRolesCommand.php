<?php

namespace App\Command;

use App\Service\AuthorizationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitializeRolesCommand extends Command
{
    protected static $defaultName = 'app:initialize-roles';
    protected static $defaultDescription = 'Initialize default roles and permissions';

    private $authorizationService;

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Inicializando roles y permisos por defecto');

        try {
            $this->authorizationService->initializeDefaultRolesAndPermissions();
            
            $io->success('Roles y permisos inicializados correctamente');
            
            // Mostrar resumen
            $roles = $this->authorizationService->getAvailableRoles();
            $permissions = $this->authorizationService->getPermissionsByCategory();
            
            $io->section('Roles creados:');
            foreach ($roles as $role) {
                $io->writeln(sprintf('  - %s (%s)', $role->getDisplayName(), $role->getName()));
            }
            
            $io->section('Permisos creados por categoría:');
            foreach ($permissions as $category => $categoryPermissions) {
                $io->writeln(sprintf('  %s:', $category));
                foreach ($categoryPermissions as $permission) {
                    $io->writeln(sprintf('    - %s (%s)', $permission->getDisplayName(), $permission->getName()));
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al inicializar roles y permisos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
