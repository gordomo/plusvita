<?php

namespace App\Command;

use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ControlarPresentesCommand extends BaseReseteoCommand
{
    protected static $defaultName = 'controlar-presentes-command';
    protected static $defaultDescription = 'Actualización diaria los pacientes ambulatorios presentes';

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }
    
    /**
     * Implementa el método abstracto para resetear presentes de pacientes
     * 
     * @throws \Exception
     */
    protected function resetearPresentes(SymfonyStyle $io): void
    {
        // Registrar información sobre el contexto de ejecución
        $io->note('Ejecutando reseteo de presentes para todos los pacientes ambulatorios');
        
        $em = $this->entityManager;

        // Access repositories
        $clienteRepo = $em->getRepository(Cliente::class);
        
        // Obtener entidades a resetear (todos los pacientes ambulatorios presentes)
        $clientes = $clienteRepo->findBy(['ambulatorioPresente'=> true]);
        
        $io->note(sprintf('Encontrados %d pacientes ambulatorios presentes', count($clientes)));

        // Resetear presentes de pacientes
        foreach ($clientes as $cliente) {
            $cliente->setAmbulatorioPresente(false);
            $em->persist($cliente);
        }
        
        // Guardar cambios
        $em->flush();
    }

}
