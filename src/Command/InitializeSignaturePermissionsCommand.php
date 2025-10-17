<?php

namespace App\Command;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitializeSignaturePermissionsCommand extends Command
{
    protected static $defaultName = 'app:initialize-signature-permissions';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Initialize signature management permissions')
            ->setHelp('Creates the necessary permissions for signature management');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $permissions = [
            [
                'name' => 'firma.view',
                'displayName' => 'Ver Firmas',
                'category' => 'Firmas',
                'description' => 'Permite ver las firmas de los usuarios',
            ],
            [
                'name' => 'firma.create',
                'displayName' => 'Crear Firma',
                'category' => 'Firmas',
                'description' => 'Permite subir una nueva firma',
            ],
            [
                'name' => 'firma.update',
                'displayName' => 'Editar Firma',
                'category' => 'Firmas',
                'description' => 'Permite activar/desactivar una firma',
            ],
            [
                'name' => 'firma.delete',
                'displayName' => 'Eliminar Firma',
                'category' => 'Firmas',
                'description' => 'Permite eliminar una firma',
            ],
            [
                'name' => 'firma.manage_others',
                'displayName' => 'Gestionar Firmas de Otros',
                'category' => 'Firmas',
                'description' => 'Permite gestionar las firmas de otros usuarios',
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
        $io->success('Signature permissions initialized successfully');

        return Command::SUCCESS;
    }
}
