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

                    $habPrivada = $cliente->getHabPrivada();
                    $camasOcupadasPorCliente = $habitacionActual->getCamasOcupadas();

                    if($habPrivada != null && $habPrivada) {
                        $camasOcupadasPorCliente = [];
                    } else {
                        unset($camasOcupadasPorCliente[$cliente->getNCama()]);
                    }

                    $habitacionActual->setCamasOcupadas($camasOcupadasPorCliente);

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
