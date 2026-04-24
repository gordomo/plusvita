<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\UserRepository;
use App\Repository\EvolucionRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\RoleRepository;
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
    public function profesionales(Request $request, UserRepository $userRepository, RoleRepository $roleRepository): Response
    {
        if (!$this->isGranted('liquidations.manage')) {
            $this->addFlash('error', 'No tienes permiso para acceder a esta página. Solo administradores pueden ver el listado de profesionales.');
            return $this->redirectToRoute('liquidaciones_mis');
        }

        // Obtener todos los roles activos desde la base de datos
        $rolesFromDb = $roleRepository->findActive();
        
        // Convertir a formato para la vista
        $rolesParaVista = [];
        $rolesParaBusqueda = [];
        
        foreach ($rolesFromDb as $role) {
            $roleData = [
                'id' => $role->getName(),
                'label' => $role->getDisplayName()
            ];
            $rolesParaVista[] = $roleData;
            $rolesParaBusqueda[] = $role->getName();
        }

        $ctrs = $request->query->get('ctr');
        $ctrsArray = $ctrs !== null && $ctrs !== '' ? explode(',', $ctrs) : [];
        $searchTerm = $request->query->get('search', '');

        if (!empty($ctrsArray)) {
            // Filtrar por roles específicos
            $profesionales = $userRepository->findByRoles($ctrsArray, $searchTerm);
        } elseif (!empty($searchTerm)) {
            // Si hay búsqueda por texto sin filtro de roles, mostrar todos los usuarios habilitados
            $profesionales = $userRepository->findAllEnabled($searchTerm);
        } else {
            // Por defecto, listamos todos los usuarios con roles activos
            $profesionales = $userRepository->findByRoles($rolesParaBusqueda, $searchTerm);
        }

        return $this->render('liquidaciones/profesionales.html.twig', [
            'doctors' => $profesionales,
            'contratos' => ['todos' => $rolesParaVista],
            'ctrsArray' => $ctrsArray,
            'searchTerm' => $searchTerm
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
        
        // Fecha límite para cambiar de estrategia: datos históricos usan cliente, datos nuevos usan historial
        $fechaLimiteHistorial = new \DateTime('2025-01-01');

        foreach ($evoluciones as $evolucion) {
            $paciente = $evolucion->getPaciente();
            if ($paciente === null) {
                continue;
            }

            // CRÍTICO: Usar la fecha de la evolución específica, no la fecha final del período
            // Esto asegura que clasificamos según el estado del paciente en el momento exacto de la evolución
            $fechaEvolucion = $evolucion->getFecha();
            
            // Si la evolución no tiene fecha, usar la fecha final del período como fallback
            if (!$fechaEvolucion) {
                $fechaEvolucion = $fechaHasta;
            }

            // ESTRATEGIA HÍBRIDA: Usar tabla cliente para datos históricos, historial para datos nuevos
            if ($fechaEvolucion < $fechaLimiteHistorial) {
                // DATOS HISTÓRICOS (antes de 2025): Usar tabla cliente (ya corregida)
                $modalidadUsada = $paciente->getModalidad();
            } else {
                // DATOS NUEVOS (2025 en adelante): Usar historia_paciente con la fecha EXACTA de la evolución
                // Buscar el estado del paciente en el momento exacto de la evolución
                $historia = $historiaRepository->findLastModalidadChange(
                    $paciente->getId(),
                    $fechaEvolucion->format('Y-m-d H:i:s')
                );
                
                if (isset($historia[0])) {
                    // Usar la modalidad del historial en la fecha de la evolución
                    $modalidadUsada = $historia[0]->getModalidad();
                } else {
                    // Si no hay historial para esa fecha, fallback a tabla cliente
                    $modalidadUsada = $paciente->getModalidad();
                }
            }

            $nombreObraSocial = $paciente->getObraSocial()?->getNombre() ?? 'Sin obra social';

            // Clasificar basado en la modalidad obtenida
            if ($modalidadUsada == 2) {
                $evolucionesCountActivos++;
                $evolucionesPivotOsActivos[$nombreObraSocial][] = $evolucion;
            } else if ($modalidadUsada == 1 || $modalidadUsada == 4) {
                $evolucionesCountAmbulatorios++;
                $evolucionesPivotOsAmbulatorios[$nombreObraSocial][] = $evolucion;
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