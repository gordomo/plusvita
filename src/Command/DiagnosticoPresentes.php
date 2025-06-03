<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando para diagnosticar problemas con los presentes
 */
class DiagnosticoPresentes extends Command
{
    protected static $defaultName = 'diagnostico-presentes';
    protected static $defaultDescription = 'Diagnostica problemas de zona horaria y configuración de presentes';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // 1. Información de PHP y zona horaria
        $io->section('Información de PHP y Zona Horaria');
        
        // Zona horaria de PHP
        $phpTimezone = date_default_timezone_get();
        $io->writeln(sprintf('PHP Default Timezone: %s', $phpTimezone));
        
        // Zona horaria del sistema
        $systemTimezone = exec('date +%Z');
        $systemTimezoneOffset = exec('date +%z');
        $io->writeln(sprintf('System Timezone: %s (%s)', $systemTimezone, $systemTimezoneOffset));
        
        // Hora actual según PHP
        $now = new \DateTime();
        $io->writeln(sprintf('Hora actual según PHP: %s', $now->format('Y-m-d H:i:s')));
        
        // Hora actual según el sistema
        $systemTime = exec('date "+%Y-%m-%d %H:%M:%S"');
        $io->writeln(sprintf('Hora actual según el sistema: %s', $systemTime));
        
        // 2. Comandos relacionados con presentes
        $io->section('Comandos Relacionados con Presentes');
        
        // Lista de comandos de presentes
        $io->writeln('Comandos configurados:');
        $io->writeln(' - resetear-presentes-doctores-command');
        $io->writeln(' - controlar-presentes-command');
        
        // 3. Cron jobs configurados
        $io->section('Cron Jobs Configurados');
        $cronJobs = exec('crontab -l | grep -e "presentes\\|reset"');
        $io->writeln(sprintf('Cron jobs relacionados con presentes: %s', $cronJobs ?: 'Ninguno encontrado'));
        
        // 4. Información de la base de datos
        $io->section('Estado Actual en Base de Datos');
        
        try {
            // Contar doctores presentes
            $doctorRepo = $this->entityManager->getRepository(Doctor::class);
            $doctoresPresentes = $doctorRepo->count(['presente' => true]);
            $io->writeln(sprintf('Doctores marcados como presentes: %d', $doctoresPresentes));
            
            // Contar clientes presentes
            $clienteRepo = $this->entityManager->getRepository(Cliente::class);
            $clientesPresentes = $clienteRepo->count(['ambulatorioPresente' => true]);
            $io->writeln(sprintf('Clientes marcados como presentes: %d', $clientesPresentes));
            
            // Último doctor marcado como presente
            $ultimoDoctor = $doctorRepo->findOneBy(['presente' => true], ['id' => 'DESC']);
            if ($ultimoDoctor) {
                $io->writeln(sprintf('Último doctor presente: %s %s (ID: %d)', 
                    $ultimoDoctor->getNombre(), 
                    $ultimoDoctor->getApellido(), 
                    $ultimoDoctor->getId()
                ));
            }
            
        } catch (\Exception $e) {
            $io->error(sprintf('Error al acceder a la base de datos: %s', $e->getMessage()));
        }
        
        // 5. Información de entorno Docker
        $io->section('Información de Entorno Docker');
        $envTZ = getenv('TZ');
        $io->writeln(sprintf('Variable de entorno TZ: %s', $envTZ ?: 'No definida'));
        
        // Verificar si hay un archivo de entorno
        if (file_exists('/environment')) {
            $io->writeln('Contenido del archivo /environment:');
            $envContent = file_get_contents('/environment');
            // Filtrar solo las líneas relacionadas con la zona horaria
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                if (stripos($line, 'TZ') !== false || stripos($line, 'TIME') !== false) {
                    $io->writeln(' - ' . $line);
                }
            }
        } else {
            $io->writeln('Archivo /environment no encontrado');
        }
        
        // 6. Ver si los comandos de presentes se han ejecutado recientemente
        $io->section('Ejecuciones Recientes de Comandos');
        
        $logFile = '/log/cron-1.log';
        if (file_exists($logFile)) {
            $logContent = exec(sprintf('tail -n 20 %s | grep -i "reset\\|presen"', $logFile));
            $io->writeln(sprintf('Últimas ejecuciones según log: %s', $logContent ?: 'No se encontraron ejecuciones recientes'));
        } else {
            $io->writeln(sprintf('Archivo de log %s no encontrado', $logFile));
        }
        
        $io->success('Diagnóstico completado. Revisa la información para identificar posibles problemas de zona horaria.');
        
        return Command::SUCCESS;
    }
}
