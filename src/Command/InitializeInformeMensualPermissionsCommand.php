<?php

namespace App\Command;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitializeInformeMensualPermissionsCommand extends Command
{
    protected static $defaultName = 'app:initialize-informe-mensual-permissions';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Initialize informe mensual management permissions')
            ->setHelp('Creates the necessary permissions for informe mensual management');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $permissions = [
            [
                'name' => 'informe_mensual.view',
                'displayName' => 'Ver Informes Mensuales',
                'category' => 'Informes Mensuales',
                'description' => 'Permite ver los informes mensuales (solo los propios si no tiene manage)',
            ],
            [
                'name' => 'informe_mensual.manage',
                'displayName' => 'Administrar Informes Mensuales',
                'category' => 'Informes Mensuales',
                'description' => 'Permite ver, crear, editar y eliminar todos los informes mensuales (acceso total)',
            ],
        ];

        foreach ($permissions as $perm) {
            $existing = $this->entityManager
                ->getRepository(Permission::class)
                ->findOneBy(['name' => $perm['name']]);

            if (!$existing) {
                $permission = new Permission();
                $permission->setName($perm['name']);
                $permission->setDisplayName($perm['displayName']);
                $permission->setCategory($perm['category']);
                $permission->setDescription($perm['description']);
                $permission->setIsActive(true);
                $permission->setIsSystem(false);

                $this->entityManager->persist($permission);
                $io->success(sprintf('Created permission: %s', $perm['name']));
            } else {
                $io->note(sprintf('Permission already exists: %s', $perm['name']));
            }
        }

        $this->entityManager->flush();
        $io->success('Informe mensual permissions initialized successfully');

        return Command::SUCCESS;
    }
}
