<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clase base para comandos de reseteo de presentes
 */
abstract class BaseReseteoCommand extends Command
{
    protected $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /**
     * Método abstracto que debe implementarse en las clases hijas para realizar
     * las operaciones específicas de reseteo.
     */
    abstract protected function resetearPresentes(SymfonyStyle $io): void;

    /**
     * Configura la zona horaria y ejecuta el método de reseteo específico.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Establecer zona horaria de Argentina para evitar problemas con los reinicios automáticos
        date_default_timezone_set('America/Argentina/Buenos_Aires');

        $io = new SymfonyStyle($input, $output);
        
        // Registrar hora de inicio de ejecución
        $inicioEjecucion = new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $io->note('Iniciando comando a las ' . $inicioEjecucion->format('Y-m-d H:i:s'));
        
        // Ejecutar el reseteo específico implementado en la clase hija
        $this->resetearPresentes($io);
        
        // Registrar hora de finalización
        $finEjecucion = new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $io->success('### ' . $finEjecucion->format('Y-m-d H:i:s') . ' /// ' . $this->getName() . ' ###');

        return Command::SUCCESS;
    }
}
