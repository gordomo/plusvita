<?php

namespace App\Controller;


use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Controller\ExportToExcel;
use App\Entity\HistoriaPaciente;
use App\Repository\ClienteRepository;
use App\Repository\ConsumibleRepository;
use App\Repository\DoctorRepository;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaHabitacionesRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\HorarioTomaRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\PresentesRepository;
use App\Repository\SignosVitalesRepository;
use App\Service\TurnoService;
use App\Service\PatientStateService;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use PhpParser\Comment\Doc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard_index", methods={"GET"})
     */
    public function index(
        HabitacionRepository $habitacionRepository, 
        HistoriaPacienteRepository $historiaPacienteRepository, 
        PresentesRepository $presentesRepository, 
        ClienteRepository $clienteRepository,
        HorarioTomaRepository $horarioTomaRepository = null,
        ConsumibleRepository $consumibleRepository = null,
        SignosVitalesRepository $signosVitalesRepository = null,
        TurnoService $turnoService = null,
        PatientStateService $patientStateService = null
    ): Response
    {
        // Verificar permisos para acceder al dashboard
        // Permitir acceso a usuarios autenticados con roles básicos
        if (!$this->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('No tienes permisos para acceder al dashboard');
        }

        $user = $this->getUser();
        
        // Redirigir según el tipo de usuario
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->dashboardAdmin($habitacionRepository, $historiaPacienteRepository, $presentesRepository, $clienteRepository);
        } elseif ($this->isDoctor()) {
            return $this->dashboardDoctor();
        } elseif ($this->isEnfermero()) {
            // Si no se inyectaron los servicios, obtenerlos manualmente
            if (!$horarioTomaRepository) {
                $horarioTomaRepository = $this->getDoctrine()->getRepository(\App\Entity\HorarioToma::class);
            }
            if (!$consumibleRepository) {
                $consumibleRepository = $this->getDoctrine()->getRepository(\App\Entity\Consumible::class);
            }
            if (!$signosVitalesRepository) {
                $signosVitalesRepository = $this->getDoctrine()->getRepository(\App\Entity\SignosVitales::class);
            }
            if (!$turnoService) {
                $turnoService = $this->container->get(TurnoService::class);
            }
            if (!$patientStateService) {
                $patientStateService = $this->container->get(PatientStateService::class);
            }
            return $this->dashboardEnfermero($horarioTomaRepository, $consumibleRepository, $clienteRepository, $signosVitalesRepository, $turnoService, $patientStateService);
        }

        // Dashboard por defecto para otros usuarios autenticados
        return $this->render('dashboard/default.html.twig', [
            'dashboardActive' => 'active',
        ]);
    }

    /**
     * Dashboard para administradores
     */
    private function dashboardAdmin(HabitacionRepository $habitacionRepository, HistoriaPacienteRepository $historiaPacienteRepository, PresentesRepository $presentesRepository, ClienteRepository $clienteRepository): Response
    {
        $isDoctor = $this->isDoctor();
        $isEnfermero = $this->isEnfermero();

        $user = $this->getUser();
        
        $infoHabitaciones = $habitacionRepository->findCamasOcupadasYDisponibles();

        // Obtener el primer día del mes en curso
        $startDate = (new \DateTime('first day of this month'))->setTime(0, 0, 0);
        // Obtener el último día del mes en curso
        $endDate = (new \DateTime('last day of this month'))->setTime(23, 59, 59);

        $ambulatoriosHoy = $historiaPacienteRepository->getAmbulatoriosIds(new \DateTime(), new \DateTime());
        $ambulatoriosMes = $historiaPacienteRepository->getAmbulatoriosIds($startDate, $endDate);
        $ambuPresentesHoy = $presentesRepository->getPresentes($ambulatoriosHoy, new \DateTime(), new \DateTime());
        $ambuPresentesMes = $presentesRepository->getPresentes($ambulatoriosMes, $startDate, $endDate);

        $ingresosEsteMes = $clienteRepository->findClientesIngresadosEsteMes($startDate, $endDate);
        $egresosEsteMes = $clienteRepository->findClientesEgresadosEsteMes($startDate, $endDate);
        $ingresadosHoy = $clienteRepository->findClientesIngresadosHoy();
        $egresadosHoy = $clienteRepository->findClientesEgresadosHoy();

        $derivadosEsteMes = $historiaPacienteRepository->getPacientesDerivadosPorMes($startDate, $endDate, ['fechaDerivacion' => 'ASC']);
        $reingresoDerivadosEsteMes = $historiaPacienteRepository->getPacientesReingresoDerivadosPorMes($startDate, $endDate);
        $derivadosHoy = $historiaPacienteRepository->getPacientesDerivadosPorMes(new \DateTime(), new \DateTime(), ['fechaDerivacion' => 'ASC']);
        $ReingresadosDerivadosHoy = $historiaPacienteRepository->getPacientesReingresoDerivadosPorMes(new \DateTime(), new \DateTime());


        $permisosEsteMes = $historiaPacienteRepository->getPacientesDePermisoPorMes($startDate, $endDate);
        $reingresoPermisosEsteMes = $historiaPacienteRepository->getPacientesReingresoDerivadosPorMes($startDate, $endDate);
        $permisosHoy = $historiaPacienteRepository->getPacientesDePermisoPorMes(new \DateTime(), new \DateTime());
        $ReingresadosPermisosHoy = $historiaPacienteRepository->getPacientesReingresoDerivadosPorMes(new \DateTime(), new \DateTime());
        
        $egresadosDesdeInternacion = array_filter(
            $egresosEsteMes,
            fn($c) => !$c->getDerivado() && !$c->getDePermiso()
        );
        $estadaMedia = $this->calcularEstadaMedia($egresosEsteMes);
        $rotacionCamas = $this->calcularRotacionCamas(count($egresadosDesdeInternacion), $infoHabitaciones);
        
        return $this->render('dashboard/admin.html.twig',
            [
                'dashboardActive' => 'active',
                'isDoctor' => $isDoctor,
                'isEnfermero' => $isEnfermero,
                'infoHabitacionesTotalyPorPiso' => $infoHabitaciones,
                'totalAmbulatoriosHoy' => count($ambulatoriosHoy),
                'ambuPresentesHoy' => count($ambuPresentesHoy),
                'totalAmbulatoriosMes' => count($ambulatoriosMes),
                'ambuPresentesMes' => count($ambuPresentesMes),
                'ingresosEsteMes' => count($ingresosEsteMes),
                'egresosEsteMes' => count($egresosEsteMes),
                'ingresadosHoy' => count($ingresadosHoy),
                'egresadosHoy' => count($egresadosHoy),
                'derivadosEsteMes' => count($derivadosEsteMes),
                'reingresoDerivadosEsteMes' => count($reingresoDerivadosEsteMes),
                'derivadosHoy' => count($derivadosHoy),
                'ReingresadosDerivadosHoy' => count($ReingresadosDerivadosHoy),
                'permisosEsteMes' => count($permisosEsteMes),
                'reingresoPermisosEsteMes' => count($reingresoPermisosEsteMes),
                'permisosHoy' => count($permisosHoy),
                'ReingresadosPermisosHoy' => count($ReingresadosPermisosHoy),
                'estadaMedia' => $estadaMedia,
                'rotacionCamas' => $rotacionCamas,
            ]);
    }

    /**
     * Dashboard para doctores
     */
    private function dashboardDoctor(): Response
    {
        $user = $this->getUser();
        
        // Aquí puedes agregar lógica específica para doctores
        // Por ejemplo: turnos del día, pacientes asignados, etc.
        
        return $this->render('dashboard/doctor.html.twig', [
            'dashboardActive' => 'active',
            'user' => $user,
            // Agrega aquí las variables que necesites para el dashboard del doctor
        ]);
    }

    /**
     * Dashboard para enfermeros
     */
    private function dashboardEnfermero(
        HorarioTomaRepository $horarioTomaRepository = null, 
        ConsumibleRepository $consumibleRepository = null, 
        ClienteRepository $clienteRepository = null,
        SignosVitalesRepository $signosVitalesRepository = null,
        TurnoService $turnoService = null,
        PatientStateService $patientStateService = null
    ): Response
    {
        $user = $this->getUser();
        
        // Obtener indicaciones próximas y pendientes para el dashboard
        $indicacionesProximas = [];
        $consumiblesArray = [];
        
        if ($horarioTomaRepository && $consumibleRepository && $clienteRepository) {
            // Obtener horarios próximos/pendientes
            $horarios = $horarioTomaRepository->findIndicacionesProximasParaDashboard(new \DateTime(), 30);
            
            // Organizar datos para el template
            foreach ($horarios as $horario) {
                $indicacion = $horario->getIndicacion();
                $clienteId = $indicacion->getClienteId();
                
                // Obtener cliente
                $cliente = $clienteRepository->find($clienteId);
                if (!$cliente) {
                    continue;
                }
                
                // Obtener nombre del consumible si es medicamento
                $nombreMedicamento = 'N/D';
                if ($indicacion->getConsumibleId()) {
                    if (!isset($consumiblesArray[$indicacion->getConsumibleId()])) {
                        $consumible = $consumibleRepository->find($indicacion->getConsumibleId());
                        if ($consumible) {
                            $consumiblesArray[$indicacion->getConsumibleId()] = $consumible->getNombre();
                        }
                    }
                    $nombreMedicamento = $consumiblesArray[$indicacion->getConsumibleId()] ?? 'N/D';
                } elseif ($indicacion->getProcedimientoPersonalizado()) {
                    $nombreMedicamento = $indicacion->getProcedimientoPersonalizado();
                }
                
                // Verificar si está en ventana de administración
                $enVentana = $horario->estaEnVentanaAdministracion();
                
                // Determinar estado
                $ahora = new \DateTime();
                $fechaHoraProgramada = clone $horario->getFecha();
                $horarioTime = $horario->getHorario();
                $fechaHoraProgramada->setTime(
                    (int)$horarioTime->format('H'),
                    (int)$horarioTime->format('i'),
                    (int)$horarioTime->format('s')
                );
                
                $diferencia = $ahora->diff($fechaHoraProgramada);
                $minutosDiferencia = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;
                
                $estado = 'pendiente';
                if ($enVentana) {
                    $estado = 'en_ventana';
                } elseif ($minutosDiferencia < 0 && abs($minutosDiferencia) <= 480) { // 8 horas = 480 minutos
                    $estado = 'vencido';
                }
                
                $indicacionesProximas[] = [
                    'horario' => $horario,
                    'indicacion' => $indicacion,
                    'cliente' => $cliente,
                    'nombreMedicamento' => $nombreMedicamento,
                    'fechaHora' => $fechaHoraProgramada,
                    'enVentana' => $enVentana,
                    'estado' => $estado,
                    'minutosDiferencia' => $minutosDiferencia,
                    'tipo' => 'indicacion', // Tipo para identificar que es una indicación médica
                ];
            }
            
            // Agregar signos vitales pendientes para pacientes internados
            if ($signosVitalesRepository && $turnoService && $patientStateService && $clienteRepository) {
                $ahora = new \DateTime();
                $turnoActual = $turnoService->obtenerTurnoActual($ahora);
                $fechaBaseTurno = $turnoService->obtenerFechaBaseTurno($ahora, $turnoActual);
                
                // Obtener todos los pacientes internados
                $pacientesInternados = $clienteRepository->findBy([
                    'modalidad' => 2, // Modalidad 2 = internado
                    'activo' => true
                ]);
                
                foreach ($pacientesInternados as $paciente) {
                    // Verificar que realmente esté internado (no derivado, no de permiso, sin fecha de egreso)
                    $estadoActual = $patientStateService->getEstadoActual($paciente);
                    if ($estadoActual !== PatientStateService::ESTADO_INTERNADO) {
                        continue;
                    }
                    
                    // Verificar si los signos vitales ya fueron completados para este turno
                    if ($signosVitalesRepository->estaCompletado($paciente, $fechaBaseTurno, $turnoActual)) {
                        continue; // Ya están completados, no mostrar
                    }
                    
                    // Verificar si hay indicaciones pendientes que bloqueen los signos vitales
                    $indicacionesPendientesEnTurno = $horarioTomaRepository->findIndicacionesPendientesEnTurno(
                        $paciente->getId(),
                        $turnoActual,
                        $fechaBaseTurno
                    );
                    
                    $bloqueado = !empty($indicacionesPendientesEnTurno);
                    
                    // Crear entrada para signos vitales
                    $indicacionesProximas[] = [
                        'horario' => null,
                        'indicacion' => null,
                        'cliente' => $paciente,
                        'nombreMedicamento' => 'Medir Signos Vitales',
                        'fechaHora' => $ahora, // Usar hora actual para ordenamiento
                        'enVentana' => !$bloqueado, // En ventana si no está bloqueado
                        'estado' => $bloqueado ? 'bloqueado' : 'en_ventana',
                        'minutosDiferencia' => 0, // Prioridad alta
                        'tipo' => 'signos_vitales', // Tipo para identificar que son signos vitales
                        'turno' => $turnoActual,
                        'bloqueado' => $bloqueado,
                        'indicacionesPendientes' => count($indicacionesPendientesEnTurno),
                    ];
                }
            }
            
            // Agrupar indicaciones por paciente y crear resumen
            $indicacionesPorPaciente = [];
            foreach ($indicacionesProximas as $item) {
                $clienteId = $item['cliente']->getId();
                
                if (!isset($indicacionesPorPaciente[$clienteId])) {
                    $indicacionesPorPaciente[$clienteId] = [
                        'cliente' => $item['cliente'],
                        'indicaciones' => [],
                        'prioridadMaxima' => 999, // Para ordenamiento
                        'fechaHoraMinima' => null, // Para ordenamiento
                        'totalIndicaciones' => 0,
                        'tieneSignosVitales' => false,
                        'signosVitalesBloqueados' => false,
                        'indicacionesEnVentana' => 0,
                        'indicacionesVencidas' => 0,
                        'estadoGeneral' => 'pendiente',
                    ];
                }
                
                $indicacionesPorPaciente[$clienteId]['indicaciones'][] = $item;
                $indicacionesPorPaciente[$clienteId]['totalIndicaciones']++;
                
                // Calcular prioridad para ordenamiento del paciente
                $prioridadEstado = ['en_ventana' => 1, 'bloqueado' => 2, 'vencido' => 3, 'pendiente' => 4];
                $prioridad = $prioridadEstado[$item['estado']] ?? 4;
                
                if ($prioridad < $indicacionesPorPaciente[$clienteId]['prioridadMaxima']) {
                    $indicacionesPorPaciente[$clienteId]['prioridadMaxima'] = $prioridad;
                    $indicacionesPorPaciente[$clienteId]['estadoGeneral'] = $item['estado'];
                }
                
                // Guardar fecha/hora mínima para ordenamiento
                if (!$indicacionesPorPaciente[$clienteId]['fechaHoraMinima'] || 
                    $item['fechaHora'] < $indicacionesPorPaciente[$clienteId]['fechaHoraMinima']) {
                    $indicacionesPorPaciente[$clienteId]['fechaHoraMinima'] = $item['fechaHora'];
                }
                
                // Contar tipos de indicaciones
                if ($item['tipo'] === 'signos_vitales') {
                    $indicacionesPorPaciente[$clienteId]['tieneSignosVitales'] = true;
                    if ($item['bloqueado']) {
                        $indicacionesPorPaciente[$clienteId]['signosVitalesBloqueados'] = true;
                    }
                }
                
                if ($item['enVentana']) {
                    $indicacionesPorPaciente[$clienteId]['indicacionesEnVentana']++;
                }
                
                if ($item['estado'] === 'vencido') {
                    $indicacionesPorPaciente[$clienteId]['indicacionesVencidas']++;
                }
            }
            
            // Ordenar pacientes por prioridad y luego por fecha/hora
            uasort($indicacionesPorPaciente, function($a, $b) {
                if ($a['prioridadMaxima'] !== $b['prioridadMaxima']) {
                    return $a['prioridadMaxima'] <=> $b['prioridadMaxima'];
                }
                return $a['fechaHoraMinima'] <=> $b['fechaHoraMinima'];
            });
        }
        
        return $this->render('dashboard/enfermero.html.twig', [
            'dashboardActive' => 'active',
            'user' => $user,
            'indicacionesPorPaciente' => $indicacionesPorPaciente ?? [],
        ]);
    }

    /**
     * @Route("/old", name="dashboard_index_old", methods={"GET"})
     */
    public function old(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository): Response
    {
        // Verificar permisos para acceder al dashboard
        // Permitir acceso a usuarios autenticados con roles básicos
        if (!$this->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('No tienes permisos para acceder al dashboard');
        }
        $isDoctor = $this->isDoctor();
        $isEnfermero = $this->isEnfermero();

        $user = $this->getUser();
        
        // Obtener rol principal del usuario para mostrar en dashboard
        $modalidad = 'Usuario del Sistema';
        if ($user instanceof \App\Entity\User) {
            $roles = $user->getRoleEntities();
            if (count($roles) > 0) {
                $firstRole = $roles->first();
                if ($firstRole) {
                    $modalidad = $firstRole->getDisplayName();
                }
            }
        }
        
        $habitacionesYpacientes = $this->getHabitacionesYpacientes();

        $osArray = $this->getOSarray($obraSocialRepository);

        $isContratosVencidos = $this->hayContratosVencidos($doctorRepository);
        $vencenEsteMes = $this->hayVencenEsteMes($doctorRepository);

        $colorCampana = 'grey';

        if ($isContratosVencidos) {
            $colorCampana = 'red';
        }

        return $this->render('dashboard.html.twig',
            [
                'dashboardActive' => 'active',
                'isDoctor' => $isDoctor,
                'isEnfermero' => $isEnfermero,
                'habitacionesYpacientes' => $habitacionesYpacientes,
                'obrasSociales' => $osArray,
                'paginaImprimible' => !$isDoctor && !$isEnfermero,
                'hayContratosVencidos' => $isContratosVencidos,
                'hayVencenEsteMes' => $vencenEsteMes,
                'colorCampana' => $colorCampana,
                'modalidad' => $modalidad,
                'habitacionRepository' => $habitacionRepository
            ]);
    }

    /**
     * @Route("/get/pacientes", name="dashboard_index_filtro_cantidad_pacientes", methods={"POST", "GET"})
     */
    public function getPacientesFromTo(Request $request, ObraSocialRepository $obraSocialRepository, HistoriaHabitacionesRepository $historiaHabitacionesRepository) {

        $from       = $request->get('from');
        $to         = $request->get('to');    
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from ;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;

        $historias = $historiaHabitacionesRepository->findByDate($fechaDesde,  $fechaHasta);

        $obrasSociales = $this->getOSarray($obraSocialRepository);

        $arrHistorias = [];

        $arrHistorias['clientes'] = [];

        foreach ( $historias as $historia ) {
            $cliente = $historia->getCliente();
            if ( (!empty($cliente->getFEgreso()) && $cliente->getFEgreso() >= $historia->getFecha()) or (empty($cliente->getFEgreso())) ) {
                try {
                    $docReferente = $cliente->getDocReferente();
                } catch (EntityNotFoundException $e) {
                    $docReferente = [];
                }
                foreach ($docReferente as $doc) {
                    if (isset ($arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()])) {
                        $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] = $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] + 1;
                    } else {
                        $arrHistorias['docReferentes'][$historia->getFecha()->format("Y-m-d")][$doc->getNombreApellido()] = 1;
                    }
                }

                $arrHistoria = ['habitacion' => $historia->getHabitacion()->getNombre(), 'cama' => $historia->getNCama()];
                if ($historia->getFecha()->format("Y-m-d") !== $historia->getFecha()->format("Y-m-d") && empty($arrHistorias['clientes'][$cliente->getNombre() . ' ' . $cliente->getApellido()][$cliente->getObraSocial()->getNombre()][$historia->getFecha()->format("Y-m-d")])) {
                    $arrHistoria = [];
                }

                $arrHistorias['clientes'][$cliente->getNombre() . ' ' . $cliente->getApellido()][$cliente->getObraSocial()->getNombre()][$historia->getFecha()->format("Y-m-d")] = $arrHistoria;
                $arrHistorias['totales'][$historia->getFecha()->format("Y-m-d")] = $historiaHabitacionesRepository->countByDate($historia->getFecha()->format("Y-m-d"));   
            }
        }

        return new JsonResponse($arrHistorias);

    }

    /**
     * @Route("/excel", name="to_excel", methods={"POST"})
     */

    public function toExcel(Request $request, RouterInterface $router) {
        return ExportToExcel::toExcel($request->get('html'), $router, $request->request->get('tituloExcel'));
    }

    private function isDoctor()
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return false;
        }

        // Usar el método hasMedicalRole() que verifica la categoría del rol
        return $user->hasMedicalRole();
    }

    private function isEnfermero()
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return false;
        }

        // Verificar si tiene algún rol de enfermería
        return $user->hasRole('enfermero') || 
               $user->hasRole('auxiliar_enfermeria') || 
               $user->hasRole('asistente_enfermeria') ||
               $user->hasRole('coordinador_enfermeria');
    }

    private function getModalidades(int $contrato)
    {
        $empleado = [
            'Mucamo/a',
            'Enfermero/a',
            'Auxiliar de enfermeria',
            'Asistente de enfermeria',
            'Mantenimiento',
            'Cocinero',
            'Ayudante de cocina',
            'Administrativo',
            'Recepcionista',
            'Coordinador de pisos',
            'Coordinador general',
            'Coordinador de enfermeria'
        ];
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
            'Kinesiologo motora',
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

        $modalidades = [
            1 => array_combine($empleado, $empleado),
            2 => array_combine($directo, $directo),
            3 => array_combine($prestacion,$prestacion),
            4 => array_combine($sinContrato, $sinContrato)
        ];
        return $modalidades[$contrato];
    }

    private function getHabitacionesYpacientes()
    {
        $habitacionRepository = $this->getDoctrine()->getRepository(Habitacion::class);
        $clienteRepository = $this->getDoctrine()->getRepository(Cliente::class);

        // Usando findBy con el criterio de habitacion no nula
        $clientesConHabitacion = $clienteRepository->findBy(['habitacion' => ['isNotNull' => true]]);
        $data = [];

        foreach($clientesConHabitacion as $cliente) {
            if ($cliente->getHabitacion()) {
                $habitacion = $habitacionRepository->find($cliente->getHabitacion());
                if ($habitacion) {
                    $data[$habitacion->getId()]['clientes'][] = $cliente;
                    $data[$habitacion->getId()]['totales'] = $habitacion->getCamasDisponibles();
                    $data[$habitacion->getId()]['disponibles'] = $cliente->getHabPrivada() ? 0 : (isset($data[$habitacion->getId()]['disponibles']) ? $data[$habitacion->getId()]['disponibles'] - 1 : $habitacion->getCamasDisponibles() - 1); 
                }
            }
        }

        return $data;
    }
    
    private function hayContratosVencidos(DoctorRepository $doctorRepository)
    {
        return count($doctorRepository->findAllVencidos());
    }

    private function hayVencenEsteMes(DoctorRepository $doctorRepository)
    {
        return count($doctorRepository->findAllVencenEsteMes());
    }

    private function getOSarray($obraSocialRepository)
    {
        $obrasSociales = $obraSocialRepository->findAll();
        $osArray = [];
        foreach ($obrasSociales as $os) {
            $osArray[$os->getId()] = $os->getNombre();
        }

        return $osArray;
    }

    private function calcularEstadaMedia(array $egresados): float
    {
        $totalDias = 0;
        $totalPacientes = 0;

        foreach ($egresados as $cliente) {
            $ingreso = $cliente->getFIngreso();
            $egreso = $cliente->getFEgreso();
            if ($ingreso && $egreso && $egreso >= $ingreso) {
                $totalDias += $ingreso->diff($egreso)->days + 1;
                $totalPacientes++;
            }
        }

        return $totalPacientes > 0 ? round($totalDias / $totalPacientes, 1) : 0.0;
    }

    /**
     * Calcula la rotación de camas (número de egresos por cama disponible)
     */
    private function calcularRotacionCamas(int $numeroEgresos, array $infoHabitaciones): float
    {
        $totalCamas = $infoHabitaciones['total']['total_camas'] ?? 0;
        
        if ($totalCamas <= 0) {
            return 0.0;
        }
        
        return round($numeroEgresos / $totalCamas, 2);
    }
}
