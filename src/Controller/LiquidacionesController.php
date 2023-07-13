<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\EvolucionRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\HistoriaPacienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/liquidaciones")
 */
class LiquidacionesController extends AbstractController
{
    /**
     * @Route("/", name="liquidaciones_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('liquidaciones/index.html.twig');
    }
    /**
     * @Route("/profesionales", name="profesionales_index", methods={"GET"})
     */
    public function profesionales(Request $request, DoctorRepository $doctorRepository): Response
    {
        $directo = [
            'Nutricionista',
            'Director medico',
            'Sub director medico',
            'Trabajadora social',
            'Psiquiatra',
            'Infectologo',
            'Contador',
            'Abogado',
            'Estudio contable',
            'Directivo',
            'Programador',
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico de guardia',
            'Medico Clínico',
            'HidroTerapia motora',
            'Kinesiologo motora ',
            'Kinesiología respiratoria',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
        ];
        $sinContrato = [
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];
        $contratosParaBusqueda = array_merge($directo, $prestacion, $sinContrato);
        $contratosParaVista = ['directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $ctrs = $request->query->get('ctr');
        $ctrsArray = explode(',', $ctrs);


        if(!empty($ctrs)) {
            $profesionales = $doctorRepository->findByContratos($ctrsArray, false);
        } else {
            $profesionales = $doctorRepository->findByContratos($contratosParaBusqueda, false);
        }


        return $this->render('liquidaciones/profesionales.html.twig', [
            'doctors' => $profesionales,
            'contratos' => $contratosParaVista,
            'ctrsArray' => $ctrsArray
        ]);
    }

    /**
     * @Route("/profesional/varios", name="liquidar_varios", methods={"GET"})
     * @param DoctorRepository $doctorRepository
     * @param BookingRepository $bookingRepository
     * @param ObraSocialRepository $obraSocialRepository
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function liquidarVarios(DoctorRepository $doctorRepository, BookingRepository $bookingRepository, ObraSocialRepository $obraSocialRepository, ClienteRepository $clienteRepository, Request $request, EvolucionRepository $evolucionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $desde = $request->query->get('desde') ?? '';
        $hasta = $request->query->get('hasta') ?? '';
        $obraSocialSelected = $request->query->get('obraSocial') ?? '';
        $completados = $request->query->get('completados') ?? 1;
        $from = new \DateTime('2000-01-01');
        $to = new \DateTime();

        if($desde != '') {
            $from = (new \DateTime($desde));
        }
        if($hasta != '') {
            $to = (new \DateTime($hasta));
        }

        $ids = $request->query->get('ids');

        $doctores = $doctorRepository->findBy(['id' => $ids]);

        $clientes = $clienteRepository->findByNombreYobraSocial(null, $obraSocialSelected);

        foreach ($doctores as $doctor) {
            $bookings[] = $bookingRepository->turnosParaAgenda($doctor, $from, '', $clientes, $from, $to, $completados);
            $evoluciones[] = $evolucionRepository->findByFechaYDoctor($doctor->getEmail(), $from, $to);

        }

        $obrasSociales = $obraSocialRepository->findAll();
        $obrasSocialesArray = [];

        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }

        return $this->render('liquidaciones/liquidar_varios.html.twig',
            [
                'bookings' => $bookings,
                'doctor' => $doctor,
                'desde' => $desde,
                'hasta' => $hasta,
                'obrasSociales' => $obrasSocialesArray,
                'obraSocialSelected' => $obraSocialSelected,
                'paginaImprimible' => true,
                'completados' => $completados,
                'evoluciones' => $evoluciones
            ]);
    }

    /**
     * @Route("/profesional/{id}", name="liquidar", methods={"GET"})
     * @param $id
     * @param DoctorRepository $doctorRepository
     * @param BookingRepository $bookingRepository
     * @param ObraSocialRepository $obraSocialRepository
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function liquidar($id, DoctorRepository $doctorRepository, BookingRepository $bookingRepository, ObraSocialRepository $obraSocialRepository, ClienteRepository $clienteRepository, Request $request, EvolucionRepository $evolucionRepository, HistoriaPacienteRepository $historiaRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $obraSocialSelected = $request->query->get('obraSocial') ?? '';
        $estado = $request->query->get('estado') ?? 'todos';
        $completados = $request->query->get('completados') ?? 1;
        $nombreInput = $request->query->get('nombreInput') ?? '';

        $to = new \DateTime();
        $from = new \DateTime();
        $from->modify('first day of previous month');
        $to->modify('last day of previous month');
        
        $desde = $request->query->get('desde') ?? $from->format('Y-m-d');;
        $hasta = $request->query->get('hasta') ?? $to->format('Y-m-d');;


        if($desde != '') {
            $from = (new \DateTime($desde));
        }
        if($hasta != '') {
            $to = (new \DateTime($hasta));
        }

        $doctor = $doctorRepository->find($id);

        $evolucionesPivotOs = [];
        $evolucionesPivotOsActivos = [];
        $evolucionesPivotOsAmbulatorios = [];
        $evoluciones = $evolucionRepository->findByFechaDoctorYCliente($doctor->getEmail(), null, $from, $to);

        $evolucionesCount = count($evoluciones);
        $evolucionesCountActivos = 0;
        $evolucionesCountAmbulatorios = 0;

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        
        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }
        
        foreach ($evoluciones as $evolucion) {
            $historia = $historiaRepository->findLastModalidadChange($evolucion->getPaciente()->getId(), $to);
            if (isset($historia[0]) && $historia[0]->getModalidad() == 2 && ($obraSocialSelected == 0 || $evolucion->getPaciente()->getObraSocial()->getId() == $obraSocialSelected ) ) {
                $evolucionesCountActivos ++;
                $evolucionesPivotOsActivos[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
            }
            if (isset($historia[0]) && $historia[0]->getModalidad() == 1 && ($obraSocialSelected == 0 || $evolucion->getPaciente()->getObraSocial()->getId() == $obraSocialSelected ) ) {
                $evolucionesCountAmbulatorios ++;
                $evolucionesPivotOsAmbulatorios[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
            }
        }

        if ($estado == 'activos') {
            $evolucionesPivotOs = $evolucionesPivotOsActivos;
            $evolucionesCount = $evolucionesCountActivos;
        } else if ( $estado == 'ambulatorios') {
            $evolucionesPivotOs = $evolucionesPivotOsAmbulatorios;
            $evolucionesCount = $evolucionesCountAmbulatorios;
        } else {
            //$clientes = $clienteRepository->findByNombreYobraSocial(null, $obraSocialSelected);
            $evolucionesPivotOs = array_merge($evolucionesPivotOsActivos, $evolucionesPivotOsAmbulatorios);
        }

        //$bookings = $bookingRepository->turnosParaAgenda($doctor, $from, '', $clientes, $from, $to, $completados);

        // $evoluciones = $evolucionRepository->findByFechaDoctorYCliente($doctor->getEmail(), $clientes, $from, $to);

        
        return $this->render('liquidaciones/liquidar.html.twig',
            [
                //'bookings' => $bookings,
                'doctor' => $doctor,
                'desde' => $desde,
                'hasta' => $hasta,
                'obrasSociales' => $obrasSociales,
                'obraSocialSelected' => $obraSocialSelected,
                'paginaImprimible' => true,
                'completados' => $completados,
                'estado' => $estado,
                'evolucionesPivotOs' => $evolucionesPivotOs,
                'evolucionesCount' => $evolucionesCount,
            ]);
    }
}