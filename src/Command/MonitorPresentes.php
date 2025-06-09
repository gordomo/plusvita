<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Comando para monitorear la ejecución de cron jobs y la zona horaria
 */
class MonitorPresentes extends Command
{
    protected static $defaultName = 'monitor-presentes';
    protected static $defaultDescription = 'Monitorea la ejecución de comandos y registra información de zona horaria sin modificar la base de datos';

    private $entityManager;
    private $filesystem;
    private $logDirectory;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->filesystem = new Filesystem();
        // Definir directorio de logs que será accesible y visible
        $this->logDirectory = '/www/html/public/logs';
        
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Asegurar que el directorio de logs existe
        if (!$this->filesystem->exists($this->logDirectory)) {
            $this->filesystem->mkdir($this->logDirectory);
        }
        
        // Obtener información de entorno y sistema
        $phpTimezone = date_default_timezone_get();
        $systemTimezone = exec('date +%Z');
        $systemTime = exec('date "+%Y-%m-%d %H:%M:%S"');
        $phpTime = (new \DateTime())->format('Y-m-d H:i:s');
        
        // Establecer explícitamente zona horaria Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $arTime = (new \DateTime())->format('Y-m-d H:i:s');
        
        // Contar doctores y pacientes presentes
        $doctorRepo = $this->entityManager->getRepository(Doctor::class);
        $clienteRepo = $this->entityManager->getRepository(Cliente::class);
        $doctoresPresentes = $doctorRepo->count(['presente' => true]);
        $clientesPresentes = $clienteRepo->count(['ambulatorioPresente' => true]);
        
        // Obtener información de cron
        $cronJobs = shell_exec('crontab -l | grep -e "presentes\\|reset"');
        
        // Recopilar información para el log
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_timezone' => $phpTimezone,
            'system_timezone' => $systemTimezone,
            'php_time' => $phpTime,
            'system_time' => $systemTime,
            'argentina_time' => $arTime,
            'doctores_presentes' => $doctoresPresentes,
            'clientes_presentes' => $clientesPresentes,
            'cron_jobs' => $cronJobs,
        ];
        
        // Crear nombre de archivo con timestamp
        $timestamp = date('Ymd_His');
        $logFile = $this->logDirectory . "/monitor_presentes_{$timestamp}.log";
        
        // Guardar log
        $logContent = json_encode($logData, JSON_PRETTY_PRINT);
        $this->filesystem->dumpFile($logFile, $logContent);
        
        // Crear un registro consolidado para facilitar seguimiento
        $this->actualizarRegistroConsolidado($logData);
        
        $io->success("Monitoreo completado. Log guardado en {$logFile}");

        return Command::SUCCESS;
    }
    
    /**
     * Actualiza un archivo consolidado con todos los registros de monitoreo
     */
    private function actualizarRegistroConsolidado(array $logData): void
    {
        $consolidatedFile = $this->logDirectory . "/monitor_presentes_consolidado.log";
        
        // Leer archivo existente o crear nuevo
        $content = $this->filesystem->exists($consolidatedFile) 
            ? file_get_contents($consolidatedFile) 
            : "MONITOREO DE PRESENTES\n====================\n\n";
            
        // Añadir nueva entrada
        $entry = sprintf(
            "[%s]\n" .
            "PHP Timezone: %s\n" .
            "System Timezone: %s\n" .
            "PHP Time: %s\n" .
            "System Time: %s\n" .
            "Argentina Time: %s\n" .
            "Doctores presentes: %d\n" .
            "Clientes presentes: %d\n" .
            "------------------------\n\n",
            $logData['timestamp'],
            $logData['php_timezone'],
            $logData['system_timezone'],
            $logData['php_time'],
            $logData['system_time'],
            $logData['argentina_time'],
            $logData['doctores_presentes'],
            $logData['clientes_presentes']
        );
        
        $content .= $entry;
        
        // Guardar archivo
        $this->filesystem->dumpFile($consolidatedFile, $content);
    }
}
