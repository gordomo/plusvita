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
        $clientesInactivos = $clienteRepo->findInActivosOcupandoCama();

        foreach ($clientes as $cliente) {
            $historia = new HistoriaHabitaciones();
            $historia->setFecha(new \DateTime());
            $historia->setHabitacion($habRepo->find($cliente->getHabitacion()));
            $historia->setNCama($cliente->getNcama());
            $historia->setCliente($cliente);
            $em->persist($historia);
            $em->flush();
        }

        foreach ( $clientesInactivos as $inactivo ) {
            $habitacionActual = $historiaHabRepo->find($inactivo->getHabitacion());

            $habPrivada = $inactivo->getHabPrivada();
            $camasOcupadasPorCliente = $habitacionActual->getCamasOcupadas();

            if($habPrivada != null && $habPrivada) {
                $camasOcupadasPorCliente = [];
            } else {
                unset($camasOcupadasPorCliente[$inactivo->getNCama()]);
            }

            $habitacionActual->setCamasOcupadas($camasOcupadasPorCliente);

            $inactivo->setHabitacion(null);
            $inactivo->setNCama(null);
            $inactivo->setHabPrivada(0);

            $historial = new HistoriaPaciente();
            $historial->setHabitacion(null);
            $historial->setCama(null);

            $em->persist($historial);
            $em->persist($habitacionActual);
            $em->persist($inactivo);
            $em->flush();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

    private function getHistorialActualizado(Cliente $cliente, $parametros, $user)
    {
        $historiaPacienteRepository = $this->getDoctrine()->getRepository(HistoriaPaciente::class);
        $ultimoHistorial = $historiaPacienteRepository->findBy(['cliente' => $cliente], ['fecha' => 'desc'], ['limit' => 1]);

        $historial = new HistoriaPaciente();

        $modalidad = (!empty($parametros['modalidad'])) ? $parametros['modalidad'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getModalidad() : null);
        $patologia = (!empty($parametros['patologia'])) ? $parametros['patologia'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getPatologia() : null);
        $patologiaEspecifica = (!empty($parametros['patologiaEspecifica'])) ? $parametros['patologiaEspecifica'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getPatologiaEspecifica() : null);
        $obraSocial = (!empty($parametros['obraSocial'])) ? $parametros['obraSocial'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getObraSocial() : null);
        $nAfiliadoObraSocial = (!empty($parametros['nAfiliadoObraSocial'])) ? $parametros['nAfiliadoObraSocial'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getNAfiliadoObraSocial() : null);
        $sistemaDeEmergencia = (!empty($parametros['sistemaDeEmergencia'])) ? $parametros['sistemaDeEmergencia'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getSistemaDeEmergencia() : null);
        $nAfiliadoSistemaDeEmergencia = (!empty($parametros['nAfiliadoSistemaDeEmergencia'])) ? $parametros['nAfiliadoSistemaDeEmergencia'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getNAfiliadoSistemaDeEmergencia() : null);
        $habitacion = (!empty($parametros['habitacion'])) ? $parametros['habitacion'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getHabitacion() : null);
        $cama = (!empty($parametros['cama'])) ? $parametros['cama'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getCama() : null);
        $fechaIngreso = (!empty($parametros['fechaIngreso'])) ? $parametros['fechaIngreso'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getFechaIngreso() : null);
        $fechaDerivacion = (!empty($parametros['fechaDerivacion'])) ? $parametros['fechaDerivacion'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getFechaDerivacion() : null);
        $fechaReingresoDerivacion = (!empty($parametros['fechaReingresoDerivacion'])) ? $parametros['fechaReingresoDerivacion'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getFechaReingresoDerivacion() : null);
        $motivoDerivacion = (!empty($parametros['motivoDerivacion'])) ? $parametros['motivoDerivacion'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getMotivoDerivacion() : null);
        $derivadoEn = (!empty($parametros['derivadoEn'])) ? $parametros['derivadoEn'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getDerivadoEn() : null);
        $empresaTransporteDerivacion = (!empty($parametros['empresaTransporteDerivacion'])) ? $parametros['empresaTransporteDerivacion'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getEmpresaTransporteDerivacion() : null);
        $fechaAltaPorPermiso = (!empty($parametros['fechaAltaPorPermiso'])) ? $parametros['fechaAltaPorPermiso'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getFechaAltaPorPermiso() : null);
        $fechaBajaPorPermiso = (!empty($parametros['fechaBajaPorPermiso'])) ? $parametros['fechaBajaPorPermiso'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getFechaBajaPorPermiso() : null);
        $dePermiso = (!empty($parametros['dePermiso'])) ? $parametros['dePermiso'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getDePermiso() : null);
        $ambulatorio = (!empty($parametros['ambulatorio'])) ? $parametros['ambulatorio'] : (!empty($ultimoHistorial) ? $ultimoHistorial[0]->getAmbulatorio() : null);
        $docReferente = null;
        if ((!empty($parametros['docReferente']))) {
            foreach ($parametros['docReferente'] as $doc) {
                $docReferente[] = $doc->getId();
            }
            $docReferente = json_encode($docReferente);
        } else if (!empty($ultimoHistorial)) {
            $docReferente = $ultimoHistorial[0]->getDocReferente();
        }

        $historial->setCliente($cliente);
        $historial->setModalidad($modalidad);
        $historial->setPatologia($patologia);
        $historial->setPatologiaEspecifica($patologiaEspecifica);
        $historial->setObraSocial($obraSocial);
        $historial->setNAfiliadoObraSocial($nAfiliadoObraSocial);
        $historial->setSistemaDeEmergencia($sistemaDeEmergencia);
        $historial->setNAfiliadoSistemaDeEmergencia($nAfiliadoSistemaDeEmergencia);
        $historial->setHabitacion($habitacion);
        $historial->setCama($cama);
        $historial->setIdPaciente($cliente->getId());
        $historial->setFecha(new \DateTime());
        $historial->setFechaIngreso($fechaIngreso);
        $historial->setUsuario($user->getEmail());
        $historial->setFechaDerivacion($fechaDerivacion);
        $historial->setFechaReingresoDerivacion($fechaReingresoDerivacion);
        $historial->setMotivoDerivacion($motivoDerivacion);
        $historial->setDerivadoEn($derivadoEn);
        $historial->setEmpresaTransporteDerivacion($empresaTransporteDerivacion);
        $historial->setFechaAltaPorPermiso($fechaAltaPorPermiso);
        $historial->setFechaBajaPorPermiso($fechaBajaPorPermiso);
        $historial->setDePermiso($dePermiso);
        $historial->setAmbulatorio($ambulatorio);
        $historial->setDocReferente($docReferente);

        return $historial;
    }
}
