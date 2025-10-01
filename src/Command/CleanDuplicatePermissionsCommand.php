<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanDuplicatePermissionsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:clean-duplicate-permissions')
            ->setDescription('Limpia permisos duplicados en la base de datos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Limpiando Permisos Duplicados');

        try {
            $permissionRepo = $this->entityManager->getRepository('App\Entity\Permission');
            
            // Buscar permisos duplicados por nombre
            $qb = $permissionRepo->createQueryBuilder('p');
            $qb->select('p.name, COUNT(p.id) as count')
               ->groupBy('p.name')
               ->having('COUNT(p.id) > 1');
            
            $duplicates = $qb->getQuery()->getResult();
            
            $deletedCount = 0;
            
            foreach ($duplicates as $duplicate) {
                $permissionName = $duplicate['name'];
                $count = $duplicate['count'];
                
                $io->text("Encontrados {$count} permisos duplicados para: {$permissionName}");
                
                // Obtener todos los permisos con este nombre
                $permissions = $permissionRepo->findBy(['name' => $permissionName]);
                
                // Mantener el primero, eliminar el resto
                $firstPermission = array_shift($permissions);
                $io->text("Manteniendo permiso ID: {$firstPermission->getId()}");
                
                foreach ($permissions as $permission) {
                    $io->text("Eliminando permiso duplicado ID: {$permission->getId()}");
                    $this->entityManager->remove($permission);
                    $deletedCount++;
                }
            }
            
            // Eliminar permisos obsoletos específicos
            $obsoletePermissions = [
                'item.clone',      // Duplicado de inventory.clone
                'item.create',     // Duplicado de inventory.manage
                'item.update',     // Duplicado de inventory.manage
                'item.delete',     // Duplicado de inventory.manage
                'item.read',       // Duplicado de inventory.view
                'client.create',   // No usado en el sistema actual
                'client.read',     // No usado en el sistema actual
                'client.update',   // No usado en el sistema actual
                'client.delete',   // No usado en el sistema actual
                'report.export',   // Duplicado de reports.export
                'report.read',     // Duplicado de reports.view
            ];
            
            foreach ($obsoletePermissions as $permissionName) {
                $permission = $permissionRepo->findOneBy(['name' => $permissionName]);
                if ($permission) {
                    $io->text("Eliminando permiso obsoleto: {$permissionName}");
                    $this->entityManager->remove($permission);
                    $deletedCount++;
                }
            }
            
            $this->entityManager->flush();
            
            if ($deletedCount > 0) {
                $io->success("Se eliminaron {$deletedCount} permisos duplicados/obsoletos.");
            } else {
                $io->info('No se encontraron permisos duplicados para eliminar.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al limpiar permisos duplicados: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
