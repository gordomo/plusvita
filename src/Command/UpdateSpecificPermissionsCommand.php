<?php

namespace App\Command;

use App\Service\AuthorizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSpecificPermissionsCommand extends Command
{
    private $authorizationService;
    private $entityManager;

    public function __construct(AuthorizationService $authorizationService, EntityManagerInterface $entityManager)
    {
        $this->authorizationService = $authorizationService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:update-specific-permissions')
            ->setDescription('Actualiza permisos a específicos (CRUD) y limpia duplicados');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Actualizando Permisos a Específicos (CRUD)');

        try {
            // Eliminar permisos obsoletos
            $obsoletePermissions = [
                'user.manage',
                'role.manage', 
                'nurse.manage',
                'nurse.view',
                'doctor.manage',
                'doctor.view'
            ];

            $permissionRepo = $this->entityManager->getRepository('App\Entity\Permission');
            
            foreach ($obsoletePermissions as $permissionName) {
                $permission = $permissionRepo->findOneBy(['name' => $permissionName]);
                if ($permission) {
                    $this->entityManager->remove($permission);
                    $io->text("Eliminado permiso obsoleto: {$permissionName}");
                }
            }

            // Actualizar permisos y roles
            $this->authorizationService->initializeDefaultRolesAndPermissions();
            
            $this->entityManager->flush();
            
            $io->success('Permisos actualizados a específicos correctamente.');
            $io->text('Cambios realizados:');
            $io->listing([
                '✅ Eliminados permisos generales obsoletos',
                '✅ Agregados permisos específicos (create, read, update, delete)',
                '✅ Actualizados roles con permisos específicos',
                '✅ Sidebar actualizado para mostrar enlaces por permisos'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al actualizar permisos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
