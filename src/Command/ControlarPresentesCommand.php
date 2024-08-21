<?php

namespace App\Command;

use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ControlarPresentesCommand extends Command
{
    protected static $defaultName = 'controlar-presentes-command';
    protected static $defaultDescription = 'Actualización diaria los pacientes ambulatorios presentes';

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
        $clientes = $clienteRepo->findBy(['ambulatorioPresente'=> true]);

        foreach ($clientes as $cliente) {
            $cliente->setAmbulatorioPresente(false);
            $em->persist($cliente);
        }

        $em->flush();
        $hoy = new \DateTime();
        $io->success('### ' . $hoy->format('Y-m-d H:i:s'). ' /// controlar-presentes-command ###');

        return Command::SUCCESS;
    }

}
