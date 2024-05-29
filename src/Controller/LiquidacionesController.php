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

        $obraSocialSelected = $request->query->get('obraSocial') ?? '';
        $completados        = $request->query->get('completados') ?? 1;
        $f          = new \DateTime('first day of this month');
        $l          = new \DateTime('last day of this month');
        $from       = $request->get('from' , $f->format('Y-m-d'));
        $to         = $request->get('to', $l->format('Y-m-d'));
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;

        $ids = $request->query->get('ids');

        $doctores = $doctorRepository->findBy(['id' => $ids]);

        $clientes = $clienteRepository->findByNombreYobraSocial(null, $obraSocialSelected);

        foreach ($doctores as $doctor) {
            $bookings[] = $bookingRepository->turnosParaAgenda($doctor, $fechaDesde, '', $clientes, $fechaDesde, $fechaHasta, $completados);
            $evoluciones[] = $evolucionRepository->findByFechaYDoctor($doctor->getEmail(), $from, $to);

        }

        $obrasSociales = $obraSocialRepository->findAll();
        $obrasSocialesArray = [];

        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }

        return $this->render('liquidaciones/liquidar_varios.html.twig',
            [
                'bookings'              => $bookings,
                'doctor'                => $doctor,
                'fechaDesde'            => $from,
                'fechaHasta'            => $to,
                'obrasSociales'         => $obrasSocialesArray,
                'obraSocialSelected'    => $obraSocialSelected,
                'paginaImprimible'      => true,
                'completados'           => $completados,
                'evoluciones'           => $evoluciones
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

        $f          = new \DateTime('first day of this month');
        $l          = new \DateTime('last day of this month');
        $from       = $request->get('from' , $f->format('Y-m-d'));
        $to         = $request->get('to', $l->format('Y-m-d'));
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;

        $doctor = $doctorRepository->find($id);

        $evolucionesPivotOs = [];
        $evolucionesPivotOsActivos = [];
        $evolucionesPivotOsAmbulatorios = [];
        $evoluciones = $evolucionRepository->findByFechaDoctorYCliente($doctor->getEmail(), null, $fechaDesde, $fechaHasta);

        $evolucionesCount = count($evoluciones);
        $evolucionesCountActivos = 0;
        $evolucionesCountAmbulatorios = 0;

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        
        foreach ($obrasSociales as $obrasSocial) {
            $obrasSocialesArray[$obrasSocial->getId()] = $obrasSocial->getNombre();
        }
        
        foreach ($evoluciones as $evolucion) {
            
            $historia = $historiaRepository->findLastModalidadChange($evolucion->getPaciente()->getId(), $to);

            if (isset($historia[0]) && $historia[0]->getModalidad() == 2 ) {
                $evolucionesCountActivos ++;
                $evolucionesPivotOsActivos[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
            }
            else if (isset($historia[0]) && ($historia[0]->getModalidad() == 1 || $historia[0]->getModalidad() == 4 ) ) {
                $evolucionesCountAmbulatorios ++;
                $evolucionesPivotOsAmbulatorios[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
            } else if (empty($historia)) {
                if ($evolucion->getPaciente()->getModalidad() == 2) {
                    $evolucionesCountActivos ++;
                    $evolucionesPivotOsActivos[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
                } else if ($evolucion->getPaciente()->getModalidad() == 1 || $evolucion->getPaciente()->getModalidad() == 4) {
                    $evolucionesCountAmbulatorios ++;
                    $evolucionesPivotOsAmbulatorios[$evolucion->getPaciente()->getObraSocial()->getNombre()][] = $evolucion;
                }
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
            $evolucionesPivotOs = array_merge_recursive($evolucionesPivotOsActivos, $evolucionesPivotOsAmbulatorios);
        }

        //$bookings = $bookingRepository->turnosParaAgenda($doctor, $from, '', $clientes, $from, $to, $completados);

        // $evoluciones = $evolucionRepository->findByFechaDoctorYCliente($doctor->getEmail(), $clientes, $from, $to);

        
        return $this->render('liquidaciones/liquidar.html.twig',
            [
                //'bookings' => $bookings,
                'doctor' => $doctor,
                'fechaDesde' => $from,
                'fechaHasta' => $to,
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