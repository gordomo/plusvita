<?php

namespace App\Command;

use App\Service\AuthorizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanPermissionsCommand extends Command
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
            ->setName('app:clean-permissions')
            ->setDescription('Limpia permisos duplicados y actualiza el sistema de permisos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Limpiando Permisos del Sistema');

        try {
            // Eliminar permisos obsoletos
            $obsoletePermissions = [
                'patient.vitals',
                'patient.medication', 
                'patient.care'
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
            
            $io->success('Permisos limpiados y actualizados correctamente.');
            $io->text('Cambios realizados:');
            $io->listing([
                '✅ Eliminados permisos obsoletos (vitals, medication, care)',
                '✅ Agregado nuevo permiso: patient.kardex',
                '✅ Limpiados duplicados',
                '✅ Reorganizados por categorías',
                '✅ Actualizados roles con nuevos permisos'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al limpiar permisos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
