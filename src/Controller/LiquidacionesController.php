<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\EvolucionRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\UserRepository;
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
        if (!$this->isGranted('liquidations.manage')) {
            $this->addFlash('error', 'No tienes permiso para acceder a esta página. Solo administradores pueden ver el listado general de liquidaciones.');
            return $this->redirectToRoute('liquidaciones_mis');
        }
        return $this->render('liquidaciones/index.html.twig');
    }
    /**
     * @Route("/profesionales", name="profesionales_index", methods={"GET"})
     */
    public function profesionales(Request $request, DoctorRepository $doctorRepository): Response
    {
        if (!$this->isGranted('liquidations.manage')) {
            $this->addFlash('error', 'No tienes permiso para acceder a esta página. Solo administradores pueden ver el listado de profesionales.');
            return $this->redirectToRoute('liquidaciones_mis');
        }
        
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
        if (!$this->isGranted('liquidations.manage')) {
            $this->addFlash('error', 'No tienes permiso para liquidar múltiples profesionales. Solo administradores pueden realizar esta acción.');
            return $this->redirectToRoute('liquidaciones_mis');
        }
        
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
     * @Route("/mis", name="liquidaciones_mis", methods={"GET"})
     */
    public function mis(EvolucionRepository $evolucionRepository, HistoriaPacienteRepository $historiaRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Solo doctores pueden ver sus propias liquidaciones
        $this->denyAccessUnlessGranted('liquidations.view');
        
        // Redirigir a la liquidación del usuario actual (ahora User es doctor)
        return $this->redirectToRoute('liquidar', ['id' => $user->getId()]);
    }

    /**
     * @Route("/profesional/{id}", name="liquidar", methods={"GET"})
     * @param $id
     * @param UserRepository $UserRepository
     * @param BookingRepository $bookingRepository
     * @param ObraSocialRepository $obraSocialRepository
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function liquidar($id, UserRepository $userRepository, BookingRepository $bookingRepository, ObraSocialRepository $obraSocialRepository, ClienteRepository $clienteRepository, Request $request, EvolucionRepository $evolucionRepository, HistoriaPacienteRepository $historiaRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verify permission: if user doesn't have liquidations.manage (admin), they can only see their own
        if (!$this->isGranted('liquidations.manage')) {
            if (!$this->isGranted('liquidations.view')) {
                $this->addFlash('error', 'No tienes permiso para ver liquidaciones.');
                return $this->redirectToRoute('app_login');
            }
            
            // Regular users (doctors) can only access their own liquidations
            if ($user->getId() != $id) {
                $this->addFlash('error', 'No tienes permiso para ver las liquidaciones de otro doctor.');
                return $this->redirectToRoute('liquidaciones_mis');
            }
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

        $evolucionesPivotOs = [];
        $evolucionesPivotOsActivos = [];
        $evolucionesPivotOsAmbulatorios = [];
        
        // Obtener el doctor (que ahora es User) por ID
        $doctor = $userRepository->find($id);
        if (!$doctor) {
            $this->addFlash('error', 'Doctor no encontrado.');
            return $this->redirectToRoute('liquidaciones_mis');
        }
        
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
                'isDoctor' => $this->isGranted('ROLE_STAFF') && $user->hasRole('doctor'),
            ]);
    }
}