<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Role;
use App\Repository\DoctorRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AssignDoctorRolesCommand extends Command
{
    protected static $defaultName = 'app:assign-doctor-roles';
    protected static $defaultDescription = 'Asigna roles correctos a todos los doctores existentes';

    private $entityManager;
    private $doctorRepository;
    private $roleRepository;

    public function __construct(EntityManagerInterface $entityManager, DoctorRepository $doctorRepository, RoleRepository $roleRepository)
    {
        $this->entityManager = $entityManager;
        $this->doctorRepository = $doctorRepository;
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

        $io->title('Asignando roles a doctores existentes');

        // Obtener todos los doctores
        $doctors = $this->doctorRepository->findAll();
        $io->text(sprintf('Encontrados %d doctores', count($doctors)));

        // Obtener o crear el rol de doctor
        $doctorRole = $this->roleRepository->findOneBy(['name' => 'doctor']);
        if (!$doctorRole) {
            $io->warning('Rol "doctor" no encontrado. Creando...');
            $doctorRole = new Role();
            $doctorRole->setName('doctor');
            $doctorRole->setDisplayName('Doctor');
            $doctorRole->setDescription('Acceso para personal médico');
            $doctorRole->setIsActive(true);
            $doctorRole->setIsSystem(true);
            $this->entityManager->persist($doctorRole);
            $this->entityManager->flush();
        }

        $assigned = 0;
        foreach ($doctors as $doctor) {
            // Verificar si ya tiene el rol
            if (!$doctor->hasRole('doctor')) {
                $doctor->addRole($doctorRole);
                $assigned++;
                $io->text(sprintf('Asignado rol a: %s %s', $doctor->getNombre(), $doctor->getApellido()));
            }

            // Asegurar que tenga ROLE_STAFF en legacyRoles para compatibilidad
            $legacyRoles = $doctor->getLegacyRoles();
            if (!in_array('ROLE_STAFF', $legacyRoles)) {
                $legacyRoles[] = 'ROLE_STAFF';
                $doctor->setLegacyRoles($legacyRoles);
                $io->text(sprintf('Agregado ROLE_STAFF a: %s %s', $doctor->getNombre(), $doctor->getApellido()));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Proceso completado. %d doctores actualizados.', $assigned));

        return Command::SUCCESS;
    }
}
