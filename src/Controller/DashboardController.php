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
    public function index(HabitacionRepository $habitacionRepository, HistoriaPacienteRepository $historiaPacienteRepository, PresentesRepository $presentesRepository, ClienteRepository $clienteRepository): Response
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
            $horarioTomaRepository = $this->getDoctrine()->getRepository(\App\Entity\HorarioToma::class);
            $consumibleRepository = $this->getDoctrine()->getRepository(\App\Entity\Consumible::class);
            return $this->dashboardEnfermero($horarioTomaRepository, $consumibleRepository, $clienteRepository);
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
        
        // Calcular Estada Media y Rotación de Camas
        $estadaMedia = $this->calcularEstadaMedia($clienteRepository, $startDate, $endDate);
        
        // Debug temporal - remover después
        $numeroEgresos = count($egresosEsteMes);
        $totalCamas = $infoHabitaciones['total']['total_camas'] ?? 0;
        // Para debugging: puedes agregar un var_dump o error_log aquí si necesitas
        
        $rotacionCamas = $this->calcularRotacionCamas($numeroEgresos, $infoHabitaciones);
        
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
    private function dashboardEnfermero(HorarioTomaRepository $horarioTomaRepository = null, ConsumibleRepository $consumibleRepository = null, ClienteRepository $clienteRepository = null): Response
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
                ];
            }
            
            // Ordenar por fecha/hora (más urgentes primero)
            usort($indicacionesProximas, function($a, $b) {
                // Primero por estado (en_ventana > vencido > pendiente)
                $prioridadEstado = ['en_ventana' => 1, 'vencido' => 2, 'pendiente' => 3];
                $prioridadA = $prioridadEstado[$a['estado']] ?? 3;
                $prioridadB = $prioridadEstado[$b['estado']] ?? 3;
                
                if ($prioridadA !== $prioridadB) {
                    return $prioridadA <=> $prioridadB;
                }
                
                // Luego por fecha/hora
                return $a['fechaHora'] <=> $b['fechaHora'];
            });
        }
        
        return $this->render('dashboard/enfermero.html.twig', [
            'dashboardActive' => 'active',
            'user' => $user,
            'indicacionesProximas' => $indicacionesProximas,
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

    /**
     * Calcula la estada media (días promedio de internación) para un período
     */
    private function calcularEstadaMedia(ClienteRepository $clienteRepository, \DateTime $startDate, \DateTime $endDate): float
    {
        // Obtener pacientes egresados en el período con sus fechas de ingreso y egreso
        $entityManager = $this->getDoctrine()->getManager();
        
        try {
            $sql = "SELECT 
                        DATEDIFF(f_egreso, f_ingreso) + 1 as dias_estancia
                    FROM cliente 
                    WHERE f_egreso IS NOT NULL 
                      AND f_egreso BETWEEN :start AND :end 
                      AND f_ingreso IS NOT NULL
                      AND f_ingreso <= f_egreso";
            
            $connection = $entityManager->getConnection();
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('start', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endDate->format('Y-m-d H:i:s'));
            
            $result = $stmt->executeQuery();
            $estancias = $result->fetchAllAssociative();
            
            if (empty($estancias)) {
                return 0.0;
            }
            
            // Calcular el promedio de días de estancia
            $totalDias = 0;
            $totalPacientes = count($estancias);
            
            foreach ($estancias as $estancia) {
                $totalDias += (int) $estancia['dias_estancia'];
            }
            
            return $totalPacientes > 0 ? round($totalDias / $totalPacientes, 1) : 0.0;
            
        } catch (\Exception $e) {
            // En caso de error, retornar 0
            return 0.0;
        }
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
