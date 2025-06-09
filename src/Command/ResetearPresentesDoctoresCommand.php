<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetearPresentesDoctoresCommand extends BaseReseteoCommand
{
    protected static $defaultName = 'resetear-presentes-doctores-command';
    protected static $defaultDescription = 'Reseteo diario de los presentes de Doctores';

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }
    
    /**
     * Implementa el método abstracto para resetear presentes de doctores y pacientes
     * 
     * @throws \Exception
     */
    protected function resetearPresentes(SymfonyStyle $io): void
    {
        // Registrar información sobre el contexto de ejecución
        $io->note('Ejecutando reseteo de presentes para doctores y ciertos pacientes');
        
        $em = $this->entityManager;

        // Access repositories
        $doctorRepo  = $em->getRepository(Doctor::class);
        $clienteRepo = $em->getRepository(Cliente::class);
        
        // Obtener entidades a resetear
        $doctores = $doctorRepo->findBy(['presente'=> true]);
        $clientes = $clienteRepo->findBy(['ambulatorioPresente'=>true, 'ambulatorio'=>false]);
        
        $io->note(sprintf('Encontrados %d doctores presentes y %d pacientes ambulatorios presentes', 
            count($doctores), count($clientes)));

        // Resetear presentes de doctores
        foreach ($doctores as $doctor) {
            $doctor->setPresente(false);
            $em->persist($doctor);
        }
        
        // Resetear presentes de pacientes (solo ambulatorios=false)
        foreach ($clientes as $cliente) {
            $cliente->setAmbulatorioPresente(false);
            $em->persist($cliente);
        }
        
        // Guardar cambios
        $em->flush();
    }

}
