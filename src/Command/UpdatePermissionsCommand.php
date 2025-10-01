<?php

namespace App\Command;

use App\Service\AuthorizationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdatePermissionsCommand extends Command
{
    private $authorizationService;

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:update-permissions')
            ->setDescription('Actualiza los permisos del sistema con los nuevos permisos definidos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Actualizando Permisos del Sistema');

        try {
            // Actualizar permisos y roles
            $this->authorizationService->initializeDefaultRolesAndPermissions();
            
            $io->success('Permisos y roles actualizados correctamente.');
            $io->text('Los nuevos permisos están organizados por categorías:');
            $io->listing([
                'Administración - Gestión de usuarios y roles',
                'Pacientes - Gestión de pacientes y historias clínicas',
                'Inventario - Gestión de items e inventario',
                'Consumibles - Gestión de consumibles',
                'Agenda - Gestión de agenda y turnos',
                'Liquidaciones - Gestión de liquidaciones',
                'Reportes - Visualización y exportación de reportes',
                'Estadísticas - Visualización de estadísticas',
                'Configuración - Configuración del sistema',
                'Reclamos - Gestión de reclamos',
                'QR - Generación de códigos QR',
                'Enfermería - Gestión de enfermeros',
                'Doctores - Gestión de doctores'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al actualizar permisos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
