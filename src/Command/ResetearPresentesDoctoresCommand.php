<?php

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetearPresentesDoctoresCommand extends Command
{
    protected static $defaultName = 'resetear-presentes-doctores-command';
    protected static $defaultDescription = 'Reseteo diario de los presentes de Doctores';

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
        $doctorRepo  = $em->getRepository(Doctor::class);
        $clienteRepo = $em->getRepository(Cliente::class);
        $doctores    = $doctorRepo->findBy(['presente'=> true]);
        $clientes    = $clienteRepo->findBy(['ambulatorioPresente'=>true, 'ambulatorio'=>false]);

        foreach ($doctores as $doctor) {
            $doctor->setPresente(false);
            $em->persist($doctor);
        }
        
        foreach ($clientes as $cliente) {
            $cliente->setAmbulatorioPresente(false);
            $em->persist($cliente);
        }

        $em->flush();
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

}
