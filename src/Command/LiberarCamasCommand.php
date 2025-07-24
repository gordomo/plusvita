<?php

namespace App\Command;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Entity\HistoriaHabitaciones;
use App\Entity\HistoriaPaciente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class LiberarCamasCommand extends Command
{
    protected static $defaultName = 'liberar-camas-command';
    protected static $defaultDescription = 'Tarea que libera la cama a futuro para las derivaciones o egresos programados';

    // 2. Expose the EntityManager in the class level
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        // 3. Update the value of the private entityManager variable through injection
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
        $em = $this->entityManager;

        $clienteRepository = $em->getRepository(Cliente::class);
        $historiaHabRepo = $em->getRepository(HistoriaHabitaciones::class);


        $clientesInactivos = $clienteRepository->findInActivosOcupandoCama();

        foreach ( $clientesInactivos as $cliente ) {
            if($cliente->getFEgreso() <= new \DateTime()) {
                $habitacionRepository = $em->getRepository(Habitacion::class);

                if($cliente->getHabitacion()) {
                    $habitacionActual = $habitacionRepository->find($cliente->getHabitacion());

                    // Verificar si hay otros pacientes en la misma habitación para evitar liberar sus camas
                    $otrosPacientes = $clienteRepository->findBy([
                        'habitacion' => $habitacionActual->getId(),
                        'fEgreso' => null
                    ]);
                    
                    // Filtrar el cliente actual de la lista
                    $otrosPacientes = array_filter($otrosPacientes, function($p) use ($cliente) {
                        return $p->getId() != $cliente->getId();
                    });

                    // Determinar qué camas deben permanecer ocupadas
                    $camasOcupadas = [];
                    $camasAsignadas = 0;
                    foreach ($otrosPacientes as $paciente) {
                        if ($paciente->getNCama() !== null) {
                            // Si el paciente tiene número de cama (incluso si es 0), mantenerlo
                            $camasOcupadas[$paciente->getNCama()] = $paciente->getNCama();
                        } else {
                            // Si hay pacientes sin número de cama, asignarles una
                            $camasAsignadas++;
                            $numeroCama = $camasAsignadas;
                            
                            // Buscar la primera cama disponible
                            while (isset($camasOcupadas[$numeroCama])) {
                                $numeroCama++;
                            }
                            
                            // Asignar la cama al paciente y actualizar el registro
                            $paciente->setNCama($numeroCama);
                            $camasOcupadas[$numeroCama] = $numeroCama;
                            $em->persist($paciente);
                        }
                    }

                    $habitacionActual->setCamasOcupadas($camasOcupadas);

                    $cliente->setHabitacion(null);
                    $cliente->setNCama(null);
                    $cliente->setHabPrivada(0);

                    $em->persist($habitacionActual);
                    $em->persist($cliente);
                    $em->flush();
                }
            }
        }

        $hoy = new \DateTime();
        $io->success('### ' . $hoy->format('Y-m-d H:i:s'). ' /// liberar-camas-command ###');

        return Command::SUCCESS;
    }    
}
