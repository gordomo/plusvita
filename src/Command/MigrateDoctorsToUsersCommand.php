<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MigrateDoctorsToUsersCommand extends Command
{
    protected static $defaultName = 'app:migrate-doctors-to-users';
    protected static $defaultDescription = 'Migrate doctors from Doctor table to User table (without roles - roles assigned later based on modalidades)';

    private $entityManager;
    private $doctorRepository;
    private $userRepository;
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        DoctorRepository $doctorRepository,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->doctorRepository = $doctorRepository;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ejecutar en modo prueba sin guardar cambios');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        
        $io->title('Migrando Doctores a Usuarios');
        
        if ($dryRun) {
            $io->warning('MODO PRUEBA - No se guardarán cambios en la base de datos');
        }

        try {
            // Obtener todos los doctores habilitados
            $doctors = $this->doctorRepository->findBy(['habilitado' => true]);
            $io->writeln(sprintf('Se encontraron %d doctores habilitados para migrar', count($doctors)));

            $createdCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($doctors as $doctor) {
                $email = $doctor->getEmail();
                
                // Validar que el doctor tenga email
                if (empty($email)) {
                    $io->writeln(sprintf('⚠️  Doctor sin email: %s %s (ID: %d) - SALTADO', 
                        $doctor->getNombre() ?? '',
                        $doctor->getApellido() ?? '',
                        $doctor->getId()
                    ));
                    $errorCount++;
                    continue;
                }

                // Verificar si el usuario ya existe
                $existingUser = $this->userRepository->findOneBy(['email' => $email]);

                if ($existingUser) {
                    $io->writeln(sprintf('⏭️  Usuario con email %s ya existe (ID: %d)', $email, $existingUser->getId()));
                    $skippedCount++;
                    continue;
                }

                // Crear nuevo usuario
                $user = new User();
                $user->setEmail($email);
                
                // Generar username: usar el del doctor, o el email, o generar uno basado en nombre
                $username = $doctor->getUsername();
                if (empty($username)) {
                    // Si no tiene username, usar email
                    $username = $email;
                    
                    // Si el email ya existe como username, generar uno único
                    if ($this->userRepository->findOneBy(['username' => $username])) {
                        // Generar username único basado en nombre + timestamp
                        $baseName = strtolower(trim($doctor->getNombre() ?? 'user'));
                        $username = $baseName . '_' . uniqid();
                    }
                }
                
                $user->setUsername($username);
                $user->setNombre($doctor->getNombre() ?? '');
                $user->setApellido($doctor->getApellido() ?? '');
                $user->setTelefono($doctor->getTelefono() ?? '');
                $user->setLegajo($doctor->getLegajo() ?? '');
                $user->setHabilitado($doctor->getHabilitado());

                // Asignar la contraseña existente del doctor (ya está hasheada)
                $user->setPassword($doctor->getPassword());

                // NO asignar roles aquí - se asignarán después con app:migrate-modalidades-to-roles
                
                $io->writeln(sprintf('✅ Creando usuario: %s %s (email: %s, legajo: %s)', 
                    $doctor->getNombre(), 
                    $doctor->getApellido(), 
                    $email,
                    $doctor->getLegajo() ?? 'N/A'
                ));

                if (!$dryRun) {
                    $this->entityManager->persist($user);
                }
                
                $createdCount++;
            }

            // Ejecutar flush solo si no es dry-run
            if (!$dryRun) {
                $this->entityManager->flush();
                $io->success('Cambios guardados en la base de datos');
            }

            // Mostrar resumen
            $io->section('Resumen de la migración');
            $io->writeln(sprintf('✅ Usuarios %s: %d', $dryRun ? 'para crear' : 'creados', $createdCount));
            $io->writeln(sprintf('⏭️  Usuarios existentes (saltados): %d', $skippedCount));
            $io->writeln(sprintf('⚠️  Errores (sin email): %d', $errorCount));
            $io->writeln(sprintf('📊 Total de doctores procesados: %d', count($doctors)));

            if ($createdCount > 0) {
                $io->newLine();
                $io->note([
                    'NOTA IMPORTANTE:',
                    '- Los usuarios migrados mantienen sus contraseñas originales',
                    '- Los roles NO se asignan en este paso',
                    '- Ejecuta app:migrate-modalidades-to-roles para asignar roles basados en modalidades'
                ]);
            }

            if ($dryRun) {
                $io->warning('MODO PRUEBA - Ningún cambio fue guardado. Ejecuta sin --dry-run para aplicar los cambios.');
                return Command::SUCCESS;
            }

            $io->success('Migración completada exitosamente');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error durante la migración: ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
