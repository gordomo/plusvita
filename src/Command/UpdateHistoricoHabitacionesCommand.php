<?php

namespace App\Command;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Entity\HistoriaHabitaciones;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class UpdateHistoricoHabitacionesCommand extends Command
{
    protected static $defaultName = 'update-historico-habitaciones';
    protected static $defaultDescription = 'Actualización diaria de cada habitación con su ocupante';

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
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);

        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $em = $this->entityManager;

        // A. Access repositories
        $clienteRepo = $em->getRepository(Cliente::class);
        $habRepo = $em->getRepository(Habitacion::class);
        $historiaHabRepo = $em->getRepository(HistoriaHabitaciones::class);

        $historiaHab = $historiaHabRepo->findBy(['fecha' => new \DateTime()]);

        foreach ($historiaHab as $historiaH) {
            $em->remove($historiaH);
            $em->flush();
        }

        $clientes = $clienteRepo->findClienteConHabitacion();

        foreach ($clientes as $cliente) {
            $historia = new HistoriaHabitaciones();
            $historia->setFecha(new \DateTime());
            $historia->setHabitacion($habRepo->find($cliente->getHabitacion()));
            $historia->setNCama($cliente->getNcama());
            $historia->setCliente($cliente);
            $em->persist($historia);
            $em->flush();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
