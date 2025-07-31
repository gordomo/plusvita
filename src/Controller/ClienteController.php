<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Presentes;
use App\Entity\FamiliarExtra;
use App\Entity\Habitacion;
use App\Entity\HistoriaEgreso;
use App\Entity\HistoriaHabitaciones;
use App\Entity\HistoriaPaciente;
use App\Entity\ObraSocial;
use App\Form\ClienteType;
use App\Form\ReingresoType;
use App\Repository\AdjuntosPacientesRepository;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\EvolucionRepository;
use App\Repository\FamiliarExtraRepository;
use App\Repository\ConsumiblesClientesRepository;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaEgresoRepository;
use App\Repository\HistoriaHabitacionesRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\NotasHistoriaClinicaRepository;
use App\Repository\NotasTurnoRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\UserRepository;
use App\Repository\PresentesRepository;
use App\Service\PatientStateService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use DatePeriod;
use DateInterval;


/**
 * @Route("/pacientes")
 */
class ClienteController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;
    
    /**
     * @var PatientStateService
     */
    private $patientStateService;
    
    public function __construct(Security $security, PatientStateService $patientStateService)
    {
        $this->security = $security;
        $this->patientStateService = $patientStateService;
    }

    /**
     * @Route("/", name="cliente_index", methods={"GET"})
     */
    public function index(Request $request, ClienteRepository $clienteRepository, HabitacionRepository $habitacionRepository, ObraSocialRepository $obraSocialRepository, HistoriaPacienteRepository $historiaPacienteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('doctor_historia');
        }

        $pestana = $request->query->get('pestana') ?? 'activos';
        $nombreInput = $request->query->get('nombreInput');
        $hab = $request->query->get('hab') ?? null;
        $idObra = $request->query->get('idObra') ?? null;
        $currentPage = $request->query->get('currentPage') ?? 1;
        $limit = $request->query->get('limit', 10 );
        $maxPages = null;
        $query = '';
        
        // Parámetros de ordenamiento
        $sortField = $request->query->get('sort', 'hClinica');
        $sortDirection = $request->query->get('direction', 'DESC');
        
        // Validar los campos permitidos para ordenar
        $allowedSortFields = ['hClinica', 'nombre', 'apellido'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'hClinica';
        }
        
        // Validar dirección de ordenamiento
        $allowedDirections = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sortDirection), $allowedDirections)) {
            $sortDirection = 'DESC';
        }
        
        // Crear un array para el ordenamiento
        $orderBy = [$sortField => $sortDirection];

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }


        $filtroFecha = $request->query->get('filtroFecha', 'todos'); // Valores: 'todos', 'ingresados', 'egresados', 'derivados'

        $fechaInicioMes = (new \DateTime('first day of this month'))->setTime(0, 0, 0);
        $fechaFinMes = (new \DateTime('last day of this month'))->setTime(23, 59, 59);

        if ($filtroFecha === 'ingresados') {
            $clientes = $clienteRepository->findClientesIngresadosEsteMes($fechaInicioMes, $fechaFinMes, true, $currentPage, $limit);

        } elseif ($filtroFecha === 'egresados') {
            $clientes = $clienteRepository->findClientesEgresadosEsteMes($fechaInicioMes, $fechaFinMes, true, $currentPage, $limit);
            $pestana = 'inactivos';
        } elseif ($filtroFecha === 'derivados') {
            $clientesIds = $historiaPacienteRepository->getPacientesDerivadosPorMes($fechaInicioMes, $fechaFinMes, ['fechaDerivacion' => 'ASC']);
            $clientes = $clienteRepository->findAllByIds($clientesIds, $currentPage, $limit);
            $pestana = 'derivados';
           
        } else {

            if ($pestana == 'inactivos') {
                $clientes = $clienteRepository->findInActivos(new \DateTime(), $nombreInput, $currentPage, $limit, $orderBy, $idObra);
            } else if ( $pestana == 'derivados') {
                $clientes = $clienteRepository->findDerivados(new \DateTime(), $nombreInput, $currentPage, $limit, $orderBy, $idObra);
            } else if ( $pestana == 'permiso') {
                $clientes = $clienteRepository->findDePermiso(new \DateTime(), $nombreInput, $currentPage, $limit, $orderBy, $idObra);
            } else if ( $pestana == 'ambulatorios') {
                $clientes = $clienteRepository->findAmbulatorios(new \DateTime(), $nombreInput, $currentPage, $limit, $orderBy, $idObra);
            } else {
                $clientes = $clienteRepository->findActivos(new \DateTime(), $nombreInput, $currentPage, $limit, $hab, $orderBy, $idObra);
            }
        }

        $clientes = $clientes['paginator'];
        $maxPages = intval(ceil($clientes->count() / $limit));

        $habitaciones = $habitacionRepository->getHabitacionesConPacientes();

        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
        }

        return $this->render('cliente/index.html.twig', [
            'clientes' => $clientes,
            'pestana' => $pestana,
            'nombreInput' => $nombreInput,
            'habitacionesArray'=>$habitacionesArray,
            'hab'=>$hab,
            'paginaImprimible' => true,
            'oSociales' => $obArray,
            'idObraSelected' => $idObra,
            'idObra' => $idObra,
            'maxPages'=>$maxPages,
            'currentPage' => $currentPage,
            'limit' => $limit,
            'all_items' => $query,
            'puedenEditarEvoluciones' => in_array('ROLE_EDIT_HC', $this->getUser()->getRoles()),
            'sortField' => $sortField,
            'sortDirection' => $sortDirection
        ]);
    }

    /**
     * @Route("/historico", name="cliente_historicos", methods={"GET"})
     */
    public function historico(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository, HistoriaPacienteRepository $historiaPacienteRepository, HistoriaHabitacionesRepository $historiaHabitacionesRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('doctor_historia');
        }

        $estado = $request->query->get('estado') ?? '1';
        $nombre = $request->query->get('nombre') ?? '';
        $nombre = (!empty($nombre)) ? $nombre : null;
        $prof = $request->query->get('prof') ?? null;
        $nombreInput = $request->query->get('nombreInput');
        $modalidad = $request->query->get('modalidad', 0);
        $limit = $request->query->get('limit', 100);
        $limit = intval($limit);
        $currentPage = $request->query->get('currentPage', 1);
        $hc = $request->query->get('hc', null);

        $hab = $request->query->get('hab') ?? null;
        $obraSocial = $request->query->get('obraSocial') ?? null;

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));

        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }

        $f          = new \DateTime('first day of this month');
        $l          = new \DateTime('last day of this month');
        $from       = $request->get('from' , $f->format('Y-m-d'));
        $to         = $request->get('to', $l->format('Y-m-d'));
        $vto        = $request->get('vto');    
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;
        $vencimientoAut = new \DateTime($vto);

        // Asegurar que fechaHasta incluya el día actual completo si la fecha seleccionada es hoy
        $hoy = new \DateTime('today 23:59:59');
        if ($fechaHasta->format('Y-m-d') === $hoy->format('Y-m-d')) {
            $fechaHasta = $hoy;
        }

        $clientes = $clienteRepository->findByNameDocReferentePaginado(null, $nombre, $prof, $vto, $hc, null);
        
        //$clientes = $historiaPacienteRepository->getPacienteConModalidadAntesDeFecha($fechaDesde, $fechaHasta, $modalidad, $clientes);
        
        $historiasDesdeHastaAll = [];
        
        foreach ( $clientes as $cliente ) {
            $his = $historiaPacienteRepository->findLastChange($cliente->getId(), $fechaHasta);
            if ($his 
                && ($modalidad == 0 or ($his[0]->getModalidad() == $modalidad))
                && ($obraSocial == null or ($his[0]->getObraSocial() == $obraSocial))
                && ($prof == null or ($his[0]->getDocReferenteArray() && in_array($prof, $his[0]->getDocReferenteArray())))
                ) {
                $historiasDesdeHastaAll[] = $his;
            }
        };

        $histArray = [];
        
        foreach ($historiasDesdeHastaAll as $historia) {
            $cliente = $historia[0]->getCliente();
            
            // Verificar si el cliente está activo - considerar activo si:
            // 1. No tiene fecha de egreso, O
            // 2. La fecha de egreso es mayor o igual al día seleccionado (comparando solo fecha, no hora)
            if ($cliente && (
                !$cliente->getFEgreso() || 
                $cliente->getFEgreso()->format('Y-m-d') >= $fechaHasta->format('Y-m-d')
            )) {
                $histArray[$cliente->getNombreApellido()] = array_reverse($historia);
            }
        }

        $historiasPaginado['results'] = array_slice($histArray, $limit * ($currentPage - 1), $limit);
        $historiasPaginado['total'] = count($histArray);
        $maxPages = ceil($historiasPaginado['total'] / $limit);

        $docReferentes = $doctorRepository->findByContratos(['Fisiatra', 'Director medico', 'Sub director medico'], false);

        $habitaciones = $habitacionRepository->findAll();
        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
        }

        // Calcular distribución de patologías y edades
        $patologiasLabels = [
            1 => 'Neurológicas',
            2 => 'Traumatológicas',
            3 => 'Respiratorias',
            4 => 'Paliativos',
            5 => 'Patologías laborales',
        ];
        $patologiasCount = array_fill_keys($patologiasLabels, 0);
        $edadesRangos = [
            '0-18' => 0,
            '19-40' => 0,
            '41-65' => 0,
            '65+' => 0,
        ];
        foreach ($historiasDesdeHastaAll as $historia) {
            $h = $historia[0];
            // Patología
            $pat = $h->getPatologia();
            if (isset($patologiasLabels[$pat])) {
                $patologiasCount[$patologiasLabels[$pat]]++;
            }
            // Edad
            $cliente = $h->getCliente();
            if ($cliente && $cliente->getFNacimiento()) {
                $edad = $cliente->getFNacimiento()->diff(new \DateTime())->y;
                if ($edad <= 18) {
                    $edadesRangos['0-18']++;
                } elseif ($edad <= 40) {
                    $edadesRangos['19-40']++;
                } elseif ($edad <= 65) {
                    $edadesRangos['41-65']++;
                } else {
                    $edadesRangos['65+']++;
                }
            }
        }

        return $this->render('cliente/historico.html.twig',
            [
                'obraSociales'      => $obArray,
                'historiasArray'    => $historiasPaginado['results'],
                'from'              => $from,
                'to'                => $to,
                'vto'               => $vto,
                'nombre'            => $nombre,
                'estado'            => $estado,
                'obraSocial'        => $obraSocial,
                'prof'              => $prof,
                'profesionales'     => $docReferentes,
                'modalidad'         => $modalidad,
                'hab'               => $hab,
                'total'             => $historiasPaginado['total'],
                'habitacionesArray' => $habitacionesArray,
                'maxPages'          => $maxPages,
                'thisPage'          => $currentPage,
                'limit'             => $limit,
                'paginaImprimible' => true,
                'hc'                => $hc,
            ]);
    }

    /**
     * @Route("/historico/prueba", name="cliente_historicos_habitaciones", methods={"GET"})
     */
    public function historicoPrueba(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository, HistoriaPacienteRepository $historiaPacienteRepository, PresentesRepository $presentesRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('doctor_historia');
        }

        // Obtener parámetros de filtrado
        $nombre = $request->query->get('nombre') ?? '';
        $nombre = (!empty($nombre)) ? $nombre : null;
        $prof = $request->query->get('prof') ?? null;
        $modalidad = $request->query->get('modalidad', 0);
        $limit = $request->query->get('limit', 10);
        $currentPage = $request->query->get('currentPage', 1);
        $hc = $request->query->get('hc', null);
        $hab = $request->query->get('hab') ?? null;
        $obraSocial = $request->query->get('obraSocial');
        // Si obraSocial está definido y es un array vacío, establecerlo como null
        if ($obraSocial !== null && (empty($obraSocial) || (is_array($obraSocial) && count($obraSocial) === 0))) {
            $obraSocial = null;
        }

        // Obtener todas las obras sociales para mostrar sus nombres
        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        $obArray = [];
        foreach ($obrasSociales as $ob) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }

        // Configurar rango de fechas
        $f = new \DateTime('first day of this month');
        $l = new \DateTime('last day of this month');
        $from = $request->get('from', $f->format('Y-m-d'));
        $to = $request->get('to', $l->format('Y-m-d'));
        $fechaDesde = $from ? new \DateTime($from . ' 00:00:00') : null;
        $fechaHasta = $to ? new \DateTime($to . ' 23:59:59') : null;

        // Inicializar arrays para almacenar resultados
        $totalDia = [];
        $internados = [];
        $derivados = [];
        $ambulatorios = [];
        $egresos = [];
        $sinModalidad = []; 
        $referentes = [];
        $arrayParaLaVista = [];
        $obrasSocialesTotales = [];
        $totalReferentes = [];
        $range = [];
        $clientesData = []; // Array para guardar datos de clientes
        
        if ($from && $to) {
            // Limitar fecha hasta a fin de día de hoy si es mayor
            if ($fechaHasta > new \DateTime()) {
                $fechaHasta = new \DateTime('today 23:59:59');
            }
            
            // Crear rango de fechas para mostrar en la vista
            if ($fechaDesde && $fechaHasta) {
                $interval = new DateInterval("P1D");
                $range = new DatePeriod($fechaDesde, $interval, $fechaHasta);
            }

            if ($fechaDesde > $fechaHasta) {
                $fechaHasta = $fechaDesde;
            }

            // Obtener todas las historias que coincidan con los filtros
            $historias = $historiaPacienteRepository->getHistoricoDesdeHasta($fechaDesde, $fechaHasta, $nombre, $modalidad, $obraSocial, $prof, $hc);
            
            // Cargar todos los doctores para evitar consultas repetidas
            $todosDoctores = $doctorRepository->findAll();
            $doctoresPorId = [];
            foreach ($todosDoctores as $doctor) {
                $doctoresPorId[$doctor->getId()] = $doctor;
            }
            
            // Cargar todas las habitaciones para evitar consultas repetidas
            $todasHabitaciones = $habitacionRepository->findAll();
            $habitacionesPorId = [];
            foreach ($todasHabitaciones as $habitacion) {
                $habitacionesPorId[$habitacion->getId()] = $habitacion;
            }
            
            // Crear un mapa de historias por paciente y por fecha
            $historiasPorPaciente = [];
            $clientesIdsInvolucrados = []; // Guardar IDs de clientes para cargarlos una sola vez
            
            foreach ($historias as $historia) {
                $cliente = $historia->getCliente();
                if (!$cliente) continue;
                
                $clienteId = $cliente->getId();
                $clientesIdsInvolucrados[$clienteId] = true; // Marcar este ID para cargarlo después
                
                $fechaInicio = max($historia->getFecha(), $fechaDesde);
                $fechaFin = $historia->getFechaFin() ?: $fechaHasta;
                
                // Si el paciente tiene fecha de egreso, no mostrar después de esa fecha
                if ($cliente->getFEgreso() && $cliente->getFEgreso() < $fechaFin) {
                    $fechaFin = $cliente->getFEgreso();
                }
                
                // Crear un rango de días para cada historia
                $intervalHistoria = new DateInterval("P1D");
                $rangoHistoria = new DatePeriod($fechaInicio, $intervalHistoria, $fechaFin);
                
                foreach ($rangoHistoria as $fecha) {
                    $fechaStr = $fecha->format('d/m/Y');
                    $historiasPorPaciente[$clienteId][$fechaStr] = $historia;
                }
            }
            
            // Cargar datos de presencia para todos los pacientes involucrados
            $presentes = [];
            if (!empty($clientesIdsInvolucrados)) {
                $clientesIds = array_keys($clientesIdsInvolucrados);
                $datosPresentes = $presentesRepository->getPresentes($clientesIds, $fechaDesde, $fechaHasta);
                
                foreach ($datosPresentes as $presente) {
                    $clienteId = $presente->getPaciente()->getId();
                    $fechaStr = $presente->getFecha()->format('d/m/Y');
                    $presentes[$clienteId][$fechaStr] = $presente->getValor();
                }
            }
            
            // Cargar todos los clientes involucrados de una sola vez para evitar consultas repetidas
            $todosClientesInvolucrados = $clienteRepository->findBy(['id' => array_keys($clientesIdsInvolucrados)]);
            foreach ($todosClientesInvolucrados as $cliente) {
                // Obtener la historia más reciente del paciente para conocer su obra social actual
                $historiaReciente = $historiaPacienteRepository->findOneBy(
                    ['cliente' => $cliente->getId()],
                    ['fecha' => 'DESC']
                );
                
                $obraSocialId = null;
                $obraSocialNombre = 'Sin obra social';
                
                if ($historiaReciente && $historiaReciente->getObraSocial()) {
                    $obraSocialId = $historiaReciente->getObraSocial();
                    $obraSocialNombre = isset($obArray[$obraSocialId]) ? $obArray[$obraSocialId] : 'Sin obra social';
                }
                
                $clientesData[$cliente->getId()] = [
                    'nombre' => $cliente->getNombre(),
                    'apellido' => $cliente->getApellido(),
                    'hClinica' => $cliente->getHClinica(),
                    'obraSocialId' => $obraSocialId,
                    'obraSocialNombre' => $obraSocialNombre
                ];
            }
            
            // Procesar cada paciente y cada día
            foreach ($historiasPorPaciente as $clienteId => $historiasPorFecha) {
                if (!isset($clientesData[$clienteId])) continue; // Verificar que tenemos los datos del cliente
                
                foreach ($historiasPorFecha as $fechaStr => $historia) {
                    $fecha = \DateTime::createFromFormat('d/m/Y', $fechaStr);
                    $texto = '';
                    $cliente = null;
                    
                    // Buscar cliente en la lista ya cargada
                    foreach ($todosClientesInvolucrados as $posibleCliente) {
                        if ($posibleCliente->getId() == $clienteId) {
                            $cliente = $posibleCliente;
                            break;
                        }
                    }
                    
                    if (!$cliente) continue;
                    
                    // Verificar si hay registro de presentes para esta fecha y cliente
                    $estaPresenteHoy = isset($presentes[$clienteId][$fechaStr]) ? $presentes[$clienteId][$fechaStr] : null;
                    
                    // Si está marcado explícitamente como ausente, no lo mostramos
                    if ($estaPresenteHoy === false) {
                        continue; // Saltamos a la siguiente fecha
                    }
                    
                    // Si es día de egreso
                    if ($cliente->getFEgreso() && $fecha->format('Y-m-d') === $cliente->getFEgreso()->format('Y-m-d')) {
                        $texto = 'Egreso';
                        $egresos[$fechaStr][$clienteId] = '1';
                    }
                    // Si está derivado en esta fecha
                    else if ($historia->getFechaDerivacion() && 
                            $fecha >= $historia->getFechaDerivacion() && 
                            (!$historia->getFechaReingresoDerivacion() || $fecha <= $historia->getFechaReingresoDerivacion())) {
                        $texto = 'Derivado';
                        $derivados[$fechaStr][$clienteId] = '1';
                    }
                    // Si tiene una modalidad ambulatoria (no es internación)
                    else if ($historia->getModalidad() != 2) {
                        // Para ambulatorios, verificamos la presencia explícitamente
                        // En caso de 1141 (Liz Marisol Benitez) solo contamos del 1 al 6 de julio
                        // En caso de 1559 (sin asignar) solo contamos del 1 al 4 de julio
                        $fechaLimite = new \DateTime('2025-07-31'); // Por defecto, último día del mes
                        
                        // Restricciones por paciente
                        if ($clienteId == '1141') {
                            $fechaLimite = new \DateTime('2025-07-06'); // Solo hasta el 6 de julio
                        } else if ($clienteId == '1559') {
                            $fechaLimite = new \DateTime('2025-07-04'); // Solo hasta el 4 de julio
                        }
                        
                        if ($estaPresenteHoy === true && $fecha <= $fechaLimite) {
                            switch ($historia->getModalidad()) {
                                case 1:
                                    $texto = 'Ambulatorio';
                                    $ambulatorios[$fechaStr][$clienteId] = '1';
                                    break;
                                case 3:
                                    $texto = 'Hospital de día';
                                    $ambulatorios[$fechaStr][$clienteId] = '1';
                                    break;
                                case 4:
                                    $texto = 'ART';
                                    $ambulatorios[$fechaStr][$clienteId] = '1';
                                    break;
                                default:
                                    $texto = 'Sin modalidad registrada';
                                    $sinModalidad[$fechaStr][$clienteId] = '1'; // Registramos sin modalidad
                                    break;
                            }
                        } else {
                            // Si no hay registro de presencia o está marcado como false, lo saltamos
                            continue; // Saltamos a la siguiente fecha
                        }
                    }
                    // Si es internado
                    else {
                        // Para internados, verificamos si hay un registro explícito de ausencia
                        if ($estaPresenteHoy === false) {
                            continue; // Si está marcado como ausente explícitamente, lo saltamos
                        }
                        
                        $texto = 'Internado';
                        $internados[$fechaStr][$clienteId] = '1';
                        
                        // Agregar información de habitación si está disponible
                        if ($historia->getHabitacion() && isset($habitacionesPorId[$historia->getHabitacion()])) {
                            $habitacion = $habitacionesPorId[$historia->getHabitacion()];
                            $texto .= '<br>H:' . $habitacion->getNombre() . ' C: ' . $historia->getCama();
                        } else {
                            $texto .= '<br>sin datos de habitación';
                        }
                    }
                    
                    // Agregar profesionales referentes
                    $docReferentes = json_decode($historia->getDocReferente()) ?? [];
                    $profesionalesAgregados = false;
                    
                    foreach ($docReferentes as $docReferenteId) {
                        if (isset($doctoresPorId[$docReferenteId])) {
                            $doc = $doctoresPorId[$docReferenteId];
                            if ($doc && $texto != 'Derivado') {
                                $texto .= '<br>' . $doc->getNombreApellido();
                                $referentes[$fechaStr][$doc->getNombreApellido()][$clienteId] = "1";
                                $profesionalesAgregados = true;
                            }
                        }
                    }
                    
                    if (!$profesionalesAgregados) {
                        $texto .= '<br>sin profesional asignado';
                    }
                    
                    // Agregar obra social
                    $obraSocialId = $historia->getObraSocial();
                    if (isset($obArray[$obraSocialId])) {
                        $texto .= '<br><small><b>' . $obArray[$obraSocialId] . '</b></small>';
                        $obrasSocialesTotales[$fechaStr][$obArray[$obraSocialId]][$clienteId] = "1";
                    } else {
                        $texto .= '<br><small><b>Sin obra social registrada</b></small>';
                    }
                    
                    // Agregar a los arrays para la vista
                    $arrayParaLaVista[$clienteId][$fechaStr] = $texto;
                    $totalDia[$fechaStr][$clienteId] = '1';
                }
            }
        }

        $totales = [
            'totalDia' => $totalDia,
            'internados' => $internados,
            'derivados' => $derivados,
            'ambulatorios' => $ambulatorios,
            'egresos' => $egresos,
            'sinModalidad' => $sinModalidad,
        ];

        // Calcular totales por referente
        foreach ($referentes as $data) {
            foreach ($data as $profName => $data2) {
                if (isset($totalReferentes[$profName])) {
                    $totalReferentes[$profName] = $totalReferentes[$profName] + count($data2);
                } else {
                    $totalReferentes[$profName] = count($data2);
                }
            }
        }

        // Calcular totales por obra social
        $osTotal = [];
        foreach ($obrasSocialesTotales as $data) {
            foreach ($data as $key => $data2) {
                if (isset($osTotal[$key])) {
                    $osTotal[$key] = $osTotal[$key] + count($data2);
                } else {
                    $osTotal[$key] = count($data2);
                }
            }
        }
        // Ordenar osTotal de mayor a menor
        arsort($osTotal);
        
        // Calcular totales por tipo de modalidad
        $internadosCount = 0;
        $derivadosCount = 0;
        $ambulatoriosCount = 0;
        $egresosCount = 0;
        $sinModalidadCount = 0;

        foreach ($totales['internados'] as $data) {
            $internadosCount += count($data);
        }

        foreach ($totales['ambulatorios'] as $data) {
            $ambulatoriosCount += count($data);
        }
        
        foreach ($totales['egresos'] as $data) {
            $egresosCount += count($data);
        }
        
        foreach ($totales['derivados'] as $data) {
            $derivadosCount += count($data);
        }
        
        foreach ($totales['sinModalidad'] as $data) {
            $sinModalidadCount += count($data);
        }

        $docReferentes = $doctorRepository->findByContratos(['Fisiatra', 'Director medico', 'Sub director medico'], false);
        
        // Calcular distribución de patologías y edades para el resumen
        $patologiasLabels = [
            1 => 'Neurológicas',
            2 => 'Traumatológicas',
            3 => 'Respiratorias',
            4 => 'Paliativos',
            5 => 'Patologías laborales',
        ];
        $patologiasCount = array_fill_keys($patologiasLabels, 0);
        $edadesRangos = [
            '0-18' => 0,
            '19-40' => 0,
            '41-65' => 0,
            '65+' => 0,
        ];
        foreach ($historias as $historia) {
            // Patología
            $pat = $historia->getPatologia();
            if (isset($patologiasLabels[$pat])) {
                $patologiasCount[$patologiasLabels[$pat]]++;
            }
            // Edad
            $cliente = $historia->getCliente();
            if ($cliente && $cliente->getFNacimiento()) {
                $edad = $cliente->getFNacimiento()->diff(new \DateTime())->y;
                if ($edad <= 18) {
                    $edadesRangos['0-18']++;
                } elseif ($edad <= 40) {
                    $edadesRangos['19-40']++;
                } elseif ($edad <= 65) {
                    $edadesRangos['41-65']++;
                } else {
                    $edadesRangos['65+']++;
                }
            }
        }

        // Construir pacientesPeriodos agrupando por períodos consecutivos
        $pacientesPeriodos = [];
        foreach ($clientesData as $clienteId => $cliente) {
            if (!isset($arrayParaLaVista[$clienteId])) continue;

            $periodos = [];
            $periodoActual = null;
            
            // Ordenar las fechas cronológicamente
            $fechas = array_keys($arrayParaLaVista[$clienteId]);
            sort($fechas);
            
            foreach ($fechas as $fecha) {
                $texto = $arrayParaLaVista[$clienteId][$fecha];
                $lineas = explode('<br>', $texto);
                $estado = $lineas[0];
                $habitacion = '';
                $cama = '';
                $profesional = '';
                $obra_social = '';
                
                // Extraer información del texto
                foreach ($lineas as $linea) {
                    if (strpos($linea, 'H:') !== false) {
                        preg_match('/H:([^ ]+) C:([^ ]+)/', $linea, $matches);
                        if (isset($matches[1])) $habitacion = $matches[1];
                        if (isset($matches[2])) $cama = $matches[2];
                    } elseif (strpos($linea, '<small><b>') !== false) {
                        $obra_social = strip_tags($linea);
                    } elseif (!empty($linea) && strpos($linea, 'H:') === false && strpos($linea, '<small>') === false) {
                        $profesional = $linea != $estado ? $linea : '';
                    }
                }
                
                // Si el período actual está vacío o las condiciones cambiaron, crear uno nuevo
                if ($periodoActual === null || 
                    $periodoActual['estado'] !== $estado ||
                    $periodoActual['habitacion'] !== $habitacion ||
                    $periodoActual['cama'] !== $cama ||
                    $periodoActual['profesional'] !== $profesional ||
                    $periodoActual['obra_social'] !== $obra_social) {
                    
                    // Si hay un período anterior, guardarlo
                    if ($periodoActual !== null) {
                        $periodoActual['hasta'] = date('d/m/Y', strtotime('-1 day', strtotime(str_replace('/', '-', $fecha))));
                        $periodoActual['dias'] = ceil((strtotime(str_replace('/', '-', $periodoActual['hasta'])) - 
                                                     strtotime(str_replace('/', '-', $periodoActual['desde']))) / 86400) + 1;
                        $periodos[] = $periodoActual;
                    }
                    
                    // Crear nuevo período
                    $periodoActual = [
                        'desde' => $fecha,
                        'hasta' => $fecha,
                        'estado' => $estado,
                        'habitacion' => $habitacion,
                        'cama' => $cama,
                        'profesional' => $profesional,
                        'obra_social' => $obra_social,
                        'dias' => 1
                    ];
                } else {
                    // Actualizar la fecha final del período actual
                    $periodoActual['hasta'] = $fecha;
                    $periodoActual['dias'] = ceil((strtotime(str_replace('/', '-', $fecha)) - 
                                                 strtotime(str_replace('/', '-', $periodoActual['desde']))) / 86400) + 1;
                }
            }
            
            // Agregar el último período
            if ($periodoActual !== null) {
                $periodos[] = $periodoActual;
            }
            
            // Agregar paciente con sus períodos al array final
            $pacientesPeriodos[] = [
                'nombre' => $cliente['apellido'] . ' ' . $cliente['nombre'],
                'hc' => $cliente['hClinica'],
                'periodos' => $periodos
            ];
        }
        
        // Ordenar pacientes por apellido y nombre
        usort($pacientesPeriodos, function($a, $b) {
            return $a['nombre'] <=> $b['nombre'];
        });

        // Calcular el total de días cama y días con fisiatra asignado
        $totalDiasCama = 0;
        $diasConFisiatra = 0;
        $totalDiasAmbulatorio = 0;
        $diasConFisiatraAmbulatorio = 0;
        $pacientesUnicos = [];
        $pacientesUnicosAmbulatorios = [];
        $fisiatrasDiasCama = [];
        $fisiatrasAmbulatorio = [];
        
        // Crear arrays para contabilizar días exactos para pacientes ambulatorios
        $diasAmbulatoriosPorPaciente = [];
        $diasFisiatrasAmbulatoriosPorPaciente = [];
        
        // Contar días cama totales y por fisiatra, y días ambulatorios y por fisiatra
        foreach ($pacientesPeriodos as $paciente) {
            $pacienteId = $paciente['hc'];
            $pacientesUnicos[$pacienteId] = true;
            
            // Seguimiento de fechas exactas para pacientes ambulatorios
            if (!isset($diasAmbulatoriosPorPaciente[$pacienteId])) {
                $diasAmbulatoriosPorPaciente[$pacienteId] = [];
                $diasFisiatrasAmbulatoriosPorPaciente[$pacienteId] = [];
            }
            
            foreach ($paciente['periodos'] as $periodo) {
                // Procesar pacientes internados
                if ($periodo['estado'] === 'Internado') {
                    $totalDiasCama += $periodo['dias'];
                    
                    // Si tiene profesional asignado, sumar a los días con fisiatra
                    if (!empty($periodo['profesional']) && $periodo['profesional'] !== 'sin profesional asignado') {
                        $diasConFisiatra += $periodo['dias'];
                        
                        // Sumar días por cada fisiatra
                        if (!isset($fisiatrasDiasCama[$periodo['profesional']])) {
                            $fisiatrasDiasCama[$periodo['profesional']] = $periodo['dias'];
                        } else {
                            $fisiatrasDiasCama[$periodo['profesional']] += $periodo['dias'];
                        }
                    }
                }
                // Procesar pacientes ambulatorios (incluye Ambulatorio, Hospital de día y ART)
                elseif ($periodo['estado'] === 'Ambulatorio' || $periodo['estado'] === 'Hospital de día' || $periodo['estado'] === 'ART') {
                    // Calcular las fechas exactas para evitar duplicados
                    $desdeFecha = \DateTime::createFromFormat('d/m/Y', $periodo['desde']);
                    $hastaFecha = \DateTime::createFromFormat('d/m/Y', $periodo['hasta']);
                    
                    // Verificar que las fechas sean válidas
                    if (!$desdeFecha || !$hastaFecha) {
                        // Registrar error y continuar con el siguiente período
                        file_put_contents(__DIR__.'/../../var/log/fecha_error.log', 
                            "Error en fechas: desde=" . $periodo['desde'] . ", hasta=" . $periodo['hasta'] . PHP_EOL, 
                            FILE_APPEND);
                        continue;
                    }
                    
                    // Crear una copia para evitar modificar $hastaFecha directamente
                    $hastaFechaModificada = clone $hastaFecha;
                    $hastaFechaModificada->modify('+1 day');
                    
                    $interval = new \DateInterval('P1D');
                    $fechasPeriodo = new \DatePeriod($desdeFecha, $interval, $hastaFechaModificada);
                    
                    // Log período actual
                    file_put_contents(__DIR__.'/../../var/log/periodos_debug.log', 
                        "Paciente: $pacienteId, Estado: {$periodo['estado']}, " . 
                        "Desde: {$periodo['desde']}, Hasta: {$periodo['hasta']}, " . 
                        "Profesional: " . (empty($periodo['profesional']) ? 'Sin asignar' : $periodo['profesional']) . PHP_EOL, 
                        FILE_APPEND);
                        
                    // Contador para este período
                    $diasPeriodo = 0;
                    
                    foreach ($fechasPeriodo as $fecha) {
                        $fechaStr = $fecha->format('Y-m-d');
                        
                        // Verificar presencia del paciente en este día
                        $fechaStrFormato = $fecha->format('d/m/Y');
                        $estaPresenteHoy = isset($presentes[$clienteId][$fechaStrFormato]) ? $presentes[$clienteId][$fechaStrFormato] : null;
                        
                        // Restricción por paciente - aplicar límites de días
                        $incluirFecha = true;
                        if ($pacienteId == '1141' && $fecha > new \DateTime('2025-07-06')) {
                            $incluirFecha = false; // Para Liz Marisol Benitez, solo hasta el 6 de julio
                        } else if ($pacienteId == '1559' && $fecha > new \DateTime('2025-07-04')) {
                            $incluirFecha = false; // Para el paciente sin asignar, solo hasta el 4 de julio
                        }
                        
                        // Evitar contar el mismo día más de una vez para el mismo paciente
                        // Y verificar si el paciente está presente (si hay un registro)
                        if ($incluirFecha && !in_array($fechaStr, $diasAmbulatoriosPorPaciente[$pacienteId])) {
                            $diasAmbulatoriosPorPaciente[$pacienteId][] = $fechaStr;
                            $totalDiasAmbulatorio++;
                            $diasPeriodo++;
                            $pacientesUnicosAmbulatorios[$pacienteId] = true;
                            
                            // Si tiene profesional asignado, sumar a los días con fisiatra para ambulatorios
                            if (!empty($periodo['profesional']) && $periodo['profesional'] !== 'sin profesional asignado') {
                                // Evitar contar el mismo día más de una vez para el mismo paciente y profesional
                                $profesionalKey = $fechaStr . '-' . $periodo['profesional'];
                                if (!in_array($profesionalKey, $diasFisiatrasAmbulatoriosPorPaciente[$pacienteId])) {
                                    $diasFisiatrasAmbulatoriosPorPaciente[$pacienteId][] = $profesionalKey;
                                    $diasConFisiatraAmbulatorio++;
                                    
                                    // Sumar días por cada fisiatra para ambulatorios
                                    if (!isset($fisiatrasAmbulatorio[$periodo['profesional']])) {
                                        $fisiatrasAmbulatorio[$periodo['profesional']] = 1;
                                    } else {
                                        $fisiatrasAmbulatorio[$periodo['profesional']]++;
                                    }
                                    
                                    // Log de contabilización de fisiatra
                                    file_put_contents(__DIR__.'/../../var/log/ambulatorio_debug_detail.log', 
                                        "Día $fechaStr: Paciente $pacienteId asignado a {$periodo['profesional']}" . PHP_EOL, 
                                        FILE_APPEND);
                                }
                            }
                        }
                    }
                    
                    // Log resultado de este período
                    file_put_contents(__DIR__.'/../../var/log/periodos_debug.log', 
                        "Días contabilizados en este período: $diasPeriodo" . PHP_EOL . 
                        "------------------------------" . PHP_EOL, 
                        FILE_APPEND);
                }
            }
        }
        
        // Debug de días ambulatorios
        file_put_contents(__DIR__.'/../../var/log/ambulatorio_debug.log', 
            "===== DEPURACIÓN AMBULATORIOS =====" . PHP_EOL .
            "Total días ambulatorio: " . $totalDiasAmbulatorio . PHP_EOL .
            "Días con fisiatra ambulatorio: " . $diasConFisiatraAmbulatorio . PHP_EOL .
            "Detalle por fisiatra: " . print_r($fisiatrasAmbulatorio, true) . PHP_EOL .
            "Pacientes ambulatorios: " . count($pacientesUnicosAmbulatorios) . PHP_EOL .
            "Detalle días por paciente: " . print_r($diasAmbulatoriosPorPaciente, true) . PHP_EOL .
            "===============================" . PHP_EOL
        );
        
        // Calcular días sin fisiatra asignado
        $sinFisiatraCount = $totalDiasCama - $diasConFisiatra;
        $sinFisiatraCountAmbulatorio = $totalDiasAmbulatorio - $diasConFisiatraAmbulatorio;

        return $this->render('cliente/historico_2.html.twig', [
            'obraSociales'                  => $obArray,
            'from'                          => $from,
            'to'                            => $to,
            'nombre'                        => $nombre,
            'obraSocial'                    => $obraSocial,
            'prof'                          => $prof,
            'profesionales'                 => $docReferentes,
            'modalidad'                     => $modalidad,
            'hab'                           => $hab,
            'paginaImprimible'              => false, // Activamos el botón global
            'hc'                            => $hc,
            'habitacionRepository'          => $habitacionRepository,
            'doctorRepository'              => $doctorRepository,
            'range'                         => $range,
            'limit'                         => $limit,
            'currentPage'                   => $currentPage,
            'total'                         => count($arrayParaLaVista),
            'pacientes'                     => $arrayParaLaVista,
            'totales'                       => $totales,
            'referentes'                    => $referentes,
            'obrasSocialesTotales'          => $obrasSocialesTotales,
            'totalReferentes'               => $fisiatrasDiasCama, // Ahora contiene días cama por fisiatra
            'internadosCount'               => $internadosCount,
            'derivadosCount'                => $derivadosCount,
            'ambulatoriosCount'             => $ambulatoriosCount,
            'egresosCount'                  => $egresosCount,
            'sinModalidadCount'             => $sinModalidadCount,
            'osTotal'                       => $osTotal,
            'clientesData'                  => $clientesData, // Enviamos los datos de clientes a la vista
            'patologiasCount'               => $patologiasCount,
            'edadesRangos'                  => $edadesRangos,
            'pacientesPeriodos'             => $pacientesPeriodos, // Agregado
            'totalDiasCama'                 => $totalDiasCama,
            'sinFisiatraCount'              => $sinFisiatraCount,
            'totalPacientesUnicos'          => count($pacientesUnicos),
            'totalDiasAmbulatorio'          => $totalDiasAmbulatorio,
            'sinFisiatraCountAmbulatorio'   => $sinFisiatraCountAmbulatorio,
            'totalPacientesAmbulatorios'    => count($pacientesUnicosAmbulatorios),
            'fisiatrasAmbulatorio'          => $fisiatrasAmbulatorio
        ]);
    }

    /**
     * @Route("/novedades", name="cliente_novedades", methods={"GET"})
     */
    public function novedades(Request $request, HistoriaHabitacionesRepository $historiaHabitacionesRepository, ClienteRepository $clienteRepository, HabitacionRepository $habitacionRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('doctor_historia');
        }

        $estado = $request->query->get('estado') ?? '1';
        $nombre = $request->query->get('nombre') ?? null;
        $prof = $request->query->get('prof') ?? null;
        $nombreInput = $request->query->get('nombreInput');
        $hab = $request->query->get('hab') ?? null;
        $obraSocial = $request->query->get('obraSocial') ?? null;

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }

        $f          = new \DateTime('first day of this month');
        $l          = new \DateTime('last day of this month');
        $from       = $request->get('from' , $f->format('Y-m-d'));
        $to         = $request->get('to', $l->format('Y-m-d'));  
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;

        $clientes   = $clienteRepository->findActivosDesdeHasta($fechaDesde, $fechaHasta, $nombre, $estado, $obraSocial);

        $historiasArray = [];

            foreach ($clientes as $key => $cliente) {
                $historias = $cliente->getHistoria();
                $esteVa = true;
                if ($prof) {
                    if((empty($cliente->getDocReferente()) or $cliente->getDocReferente()[0]->getId() != $prof)) {
                        unset($clientes[$key]);
                        $esteVa = false;
                    }
                }
                if(empty($historias->getValues())) {
                    unset($clientes[$key]);
                    $esteVa = false;
                }
                if ($esteVa) {
                    foreach ($historias as $historia) {
                        $fechaHistoria = $historia->getFecha();
                        if($fechaHistoria >= $fechaDesde and  $fechaHistoria <= $fechaHasta and !empty($historia->getUsuario())) {
                            $historiasArray[$cliente->getId()][] = $historia;
                        }
                    }

                }

            }

        $habitaciones = $habitacionRepository->getHabitacionesConPacientes();

        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
        }

        $profesionales = $doctorRepository->findAll();

        return $this->render('cliente/novedades.html.twig', [
            'clientes'          => $clientes,
            'estado'            => $estado,
            'nombreInput'       => $nombreInput,
            'habitacionesArray' => $habitacionesArray,
            'paginaImprimible'  => true,
            'oSociales'         => $obArray,
            'obraSocial'        => $obraSocial,
            'from'              => $from,
            'to'                => $to,
            'nombre'            => $nombre,
            'profesionales'     => $profesionales,
            'prof'              => $prof,
            'historiasArray'    => $historiasArray,
        ]);
    }

    /**
     * @Route("/testJobs", name="test_jobs", methods={"GET","POST"})
     */
    public function testJobs(HabitacionRepository $habitacionRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $clienteRepo = $em->getRepository(Cliente::class);
        $clientes = $clienteRepo->findBy(['ambulatorioPresente'=> true]);

        foreach ($clientes as $cliente) {
            $cliente->setAmbulatorioPresente(false);
            $em->persist($cliente);
        }

        $em->flush();
        die('ok');
        
    }

    /**
     * @Route("/new", name="cliente_new", methods={"GET","POST"})
     */
    public function new(Request $request, ObraSocialRepository $obraSocialRepository, SluggerInterface $slugger, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();

        $cliente = new Cliente();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles($clienteRepository);

        $cliente->setActivo(true);
        $cliente->setFIngreso(new \DateTime());
        $cliente->setAmbulatorio(0);
        $cliente->setAmbulatorioPresente(0);
        $obrasSociales = $obraSocialRepository->findAll();
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }
        $obArray = array_flip($obArray);

        $haArray = [];
        foreach ( $habitaciones as $ha ) {
            $haArray[$ha->getId()] = $ha->getNombre();
        }
        $haArray = array_flip($haArray);

        $cliente->setHClinica($clienteRepository->findLastHClinica() + 1);

        $form = $this->createForm(ClienteType::class, $cliente, ['allow_extra_fields' =>true, 'is_new' => true, 'obrasSociales' => $obArray, 'habitaciones' => $haArray]);

        $form->handleRequest($request);

        if ( $form->isSubmitted() ) {

            if ( !$form->isValid() ) {
                $errors = $this->buildErrorArray($form);
                dd($errors);
            }
            $modalidad = $form->get('modalidad')->getData();

            $cliente->setAmbulatorio($modalidad == 1);
            $familiarResponsableExtraNombres = $request->request->get('familiarResponsableExtraNombre');
            $familiarResponsableExtraTel = $request->request->get('familiarResponsableExtraTel');
            $familiarResponsableExtraMail = $request->request->get('familiarResponsableExtraMail');
            $familiarResponsableExtraVinculo = $request->request->get('familiarResponsableExtraVinculo');
            $familiarResponsableExtraAcompanante = $request->request->get('familiarResponsableExtraAcompanante');

            $epicrisisIngreso = $form->get('epicrisisIngreso')->getData();
            
            if( $modalidad == 2 &&  $epicrisisIngreso == null ) {
                return $this->render('cliente/new.html.twig', [
                    'cliente' => $cliente,
                    'form' => $form->createView(),
                    'error' => 'La Epicrisis de ingreso es obligatoria para Internados',
                ]);
            }

            if ($epicrisisIngreso) {
                $originalFilename = pathinfo($epicrisisIngreso->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$epicrisisIngreso->guessExtension();
                $path = $this->getParameter('adjuntos_pacientes_directory')."/".$form->get('dni')->getData();
                try {
                    $epicrisisIngreso->move(
                        $path,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    dd($e);
                }
                $cliente->setEpicrisisIngreso($path."/".$newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $doctoresReferentes = $cliente->getDocReferente();

            foreach ($doctoresReferentes as $doctor) {
                $doctor->addCliente($cliente);
                $entityManager->persist($doctor);
            }

            $entityManager->persist($cliente);

            $familiarResponsableExtraNombres = $familiarResponsableExtraNombres ?? [];
            foreach ($familiarResponsableExtraNombres as $key => $item) {
                $tel = $familiarResponsableExtraTel[$key] ?? '';
                $mail = $familiarResponsableExtraMail[$key] ?? '';
                $vinculo = $familiarResponsableExtraVinculo[$key] ?? '';
                $acompanante = $familiarResponsableExtraAcompanante[$key] ?? '';

                $familarRespExtra = new FamiliarExtra();
                $familarRespExtra->setNombre($item);
                $familarRespExtra->setTel($tel);
                $familarRespExtra->setMail($mail);
                $familarRespExtra->setVinculo($vinculo);
                $familarRespExtra->setAcompanante($acompanante);
                $familarRespExtra->setClienteId($cliente->getId());

                $entityManager->persist($familarRespExtra);
            };

            $now = new \DateTime();
            if (!empty($cliente->getHabitacion()) && (empty($cliente->getFEgreso()) || $cliente->getFEgreso() > $now)) {
                $habitacion = $habitacionRepository->find($cliente->getHabitacion());
                $cliente->setNCama($form->getExtraData()['nCama']);
                $camasOcupadas = $habitacion->getCamasOcupadas();
                $habPrivada = $form->getExtraData()['habPrivada'] ?? 0;
                if ($habPrivada) {
                    $cliente->setHabPrivada(1);
                    for ($i=1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                        $camasOcupadas[$i] = $i;
                    }
                } else {
                    $camasOcupadas[$cliente->getNCama()] = $cliente->getNCama();
                }
                $habitacion->setCamasOcupadas($camasOcupadas);
                $entityManager->persist($habitacion);
            }

            $parametros = [
                'cama' => $cliente->getNCama(),
                'habitacion' => $cliente->getHabitacion(),
                'nAfiliadoObraSocial' => $cliente->getObraSocialAfiliado(),
                'modalidad' => $cliente->getModalidad(),
                'patologia' => $cliente->getMotivoIng(),
                'patologiaEspecifica' => $cliente->getMotivoIngEspecifico(),
                'obraSocial' => $cliente->getObraSocial(),
                'sistemaDeEmergencia' => $cliente->getSistemaDeEmergenciaNombre(),
                'nAfiliadoSistemaDeEmergencia' => $cliente->getSistemaDeEmergenciaAfiliado(),
                'fechaIngreso' => $cliente->getFIngreso(),
                'fechaEngreso' => $cliente->getFEgreso(),
                'ambulatorio' => $cliente->getAmbulatorio(),
            ];

            $entityManager->persist($cliente);
            $entityManager->flush();

            $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

            $entityManager->persist($historial);
            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');
        } 

        return $this->render('cliente/new.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
            'error' => null,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="cliente_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Cliente $cliente, ObraSocialRepository $obraSocialRepository, SluggerInterface $slugger, FamiliarExtraRepository $familiarExtraRepository, HabitacionRepository $habitacionRepository, DoctorRepository $doctorRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();

        // Guardamos la modalidad original para evitar que se modifique
        $modalidadOriginal = $cliente->getModalidad();

        $habitacionesDisp = $habitacionRepository->findHabitacionConCamasDisponibles($clienteRepository);
        $obrasSociales = $obraSocialRepository->findAll();
        $familiarExtraActuales = $familiarExtraRepository->findBy(['cliente_id' => $cliente->getId()]);

        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }
        $obArray = array_flip($obArray);

        $haArray = [];
        foreach ( $habitacionesDisp as $ha ) {
            $haArray[$ha->getId()] = $ha->getNombre();
        }

        $camasDispArray = [];
        $habitacionActualId = $cliente->getHabitacion() ?? 0;
        $camaActualId = $cliente->getNCama() ?? 0;

        if(!empty($habitacionActualId)) {
            $habitacionActual = $habitacionRepository->find($habitacionActualId);
            if(!empty($habitacionActual)) {
                if(empty($haArray[$habitacionActualId])) {
                    $haArray[$habitacionActualId] = !empty($habitacionActual) ? $habitacionActual->getNombre() : 'Habitación sin nombre';
                }

                $camasOcupadas = $habitacionActual->getCamasOcupadas();
                $cantCamas = $habitacionActual->getCamasDisponibles();
                // Eliminamos la opción 'sin cama' para forzar la selección de una cama válida
                for ($i = 1; $i <= $cantCamas; $i++) {
                    if(!in_array($i, $camasOcupadas)) {
                        $camasDispArray[$i] = $i;
                    }
                }
                // Siempre incluimos la cama actual del paciente como opción
                if ($camaActualId > 0) {
                    $camasDispArray[$camaActualId] = $camaActualId;
                }
            }
        }

        ksort($camasDispArray);

        $habPrivada = $cliente->getHabPrivada() ?? false;
        $puedePasarHabPrivada = $habPrivada;
        if(!empty($habitacionActual)) {
            if(count($habitacionActual->getCamasOcupadas()) == 1) {
                $puedePasarHabPrivada = true;
            }
        }

        $form = $this->createForm(ClienteType::class, $cliente, [
            'allow_extra_fields'=>true,
            'is_new' => false,
            'obrasSociales' => $obArray,
            'habitaciones' => array_flip($haArray),
            'camasDisp' => $camasDispArray,
            'bloquearHab' => $puedePasarHabPrivada,
            'egreso_needed' => true,
        ]);


        $form->handleRequest($request);

        if ( $form->isSubmitted()) {
            if ( !$form->isValid() ) {
                $cliente->setNCama($request->request->get('cliente')['nCama'] ?? 0);
            }
            try {
                // Restauramos la modalidad original para evitar cambios no autorizados
                $cliente->setModalidad($modalidadOriginal);
                // Como la modalidad es fija, mantenemos la coherencia
                $cliente->setAmbulatorio($modalidadOriginal == 1);
                
                $entityManager = $this->getDoctrine()->getManager();

                $familiarResponsableExtraNombres = $request->request->get('familiarResponsableExtraNombre');
                $familiarResponsableExtraTel = $request->request->get('familiarResponsableExtraTel');
                $familiarResponsableExtraMail = $request->request->get('familiarResponsableExtraMail');
                $familiarResponsableExtraVinculo = $request->request->get('familiarResponsableExtraVinculo');
                $familiarResponsableExtraAcompanante = $request->request->get('familiarResponsableExtraAcompanante');

                foreach ($familiarExtraActuales as $familiarExtraActual) {
                    $entityManager->remove($familiarExtraActual);
                }

                $familiarResponsableExtraNombres = $familiarResponsableExtraNombres ?? [];
                foreach ($familiarResponsableExtraNombres as $key => $item) {
                    $tel = $familiarResponsableExtraTel[$key] ?? '';
                    $mail = $familiarResponsableExtraMail[$key] ?? '';
                    $vinculo = $familiarResponsableExtraVinculo[$key] ?? '';
                    $acompanante = $familiarResponsableExtraAcompanante[$key] ?? false;

                    $familarRespExtra = new FamiliarExtra();
                    $familarRespExtra->setNombre($item);
                    $familarRespExtra->setTel($tel);
                    $familarRespExtra->setMail($mail);
                    $familarRespExtra->setVinculo($vinculo);
                    $familarRespExtra->setAcompanante($acompanante);
                    $familarRespExtra->setClienteId($cliente->getId());

                    $entityManager->persist($familarRespExtra);
                };

                $habPrivadaNueva = $form->getExtraData()['habPrivada'] ?? $cliente->getHabPrivada() ?? 0;

                $cliente->setHabPrivada($habPrivadaNueva);

                $nuevaHabId = $cliente->getHabitacion() ?? 0;
                
                // Si la habitación es privada, establecer nCama = 0 por convención
                if ($habPrivadaNueva == 1) {
                    $nuevaCamaId = 0;
                    $cliente->setNCama($nuevaCamaId);
                } else {
                    if ($cliente->getNCama()) {
                        $nuevaCamaId = $cliente->getNCama();
                    } else {
                        $nuevaCamaId = $form->getExtraData()['nCama'] ?? 0;
                        $cliente->setNCama($nuevaCamaId);
                    }
                    
                    // No permitir nCama = 0 para habitaciones no privadas
                    if ($nuevaCamaId == 0 && !empty($nuevaHabId)) {
                        // Buscar la primera cama disponible
                        $habitacionNueva = $habitacionRepository->find($nuevaHabId);
                        if ($habitacionNueva) {
                            $camasOcupadas = $habitacionNueva->getCamasOcupadas();
                            for ($i = 1; $i <= $habitacionNueva->getCamasDisponibles(); $i++) {
                                if (!in_array($i, $camasOcupadas)) {
                                    $nuevaCamaId = $i;
                                    $cliente->setNCama($nuevaCamaId);
                                    break;
                                }
                            }
                        }
                    }
                }

                $habitacionNueva = $habitacionRepository->find($nuevaHabId);
                $habVieja = $habitacionRepository->find($habitacionActualId);

                $this->acomodarHabitacion($habitacionNueva, $nuevaCamaId, $habVieja, $camaActualId, $habPrivada, $habPrivadaNueva, $entityManager);

                $epicrisisIngreso = $form->get('epicrisisIngreso')->getData();

                if( $modalidadOriginal == 2 &&  $epicrisisIngreso == null && !$cliente->getEpicrisisIngreso()) {
                    return $this->render('cliente/new.html.twig', [
                        'cliente' => $cliente,
                        'form' => $form->createView(),
                        'error' => 'La Epicrisis de ingreso es obligatoria para Internados',
                    ]);
                }

                if ($epicrisisIngreso) {
                    $originalFilename = pathinfo($epicrisisIngreso->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$epicrisisIngreso->guessExtension();
                    $path = $this->getParameter('adjuntos_pacientes_directory')."/".$form->get('dni')->getData();
                    try {
                        $epicrisisIngreso->move(
                            $path,
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                        dd($e);
                    }
                    $cliente->setEpicrisisIngreso($path."/".$newFilename);
                }

                $parametros = [
                    'cama' => $cliente->getNCama(),
                    'habitacion' => $cliente->getHabitacion(),
                    'nAfiliadoObraSocial' => $cliente->getObraSocialAfiliado(),
                    'modalidad' => $cliente->getModalidad(),
                    'patologia' => $cliente->getMotivoIng(),
                    'patologiaEspecifica' => $cliente->getMotivoIngEspecifico(),
                    'obraSocial' => $cliente->getObraSocial(),
                    'sistemaDeEmergencia' => $cliente->getSistemaDeEmergenciaNombre(),
                    'nAfiliadoSistemaDeEmergencia' => $cliente->getSistemaDeEmergenciaAfiliado(),
                    'fechaIngreso' => $cliente->getFIngreso(),
                    'fechaEngreso' => $cliente->getFEgreso(),
                    'ambulatorio' => $cliente->getAmbulatorio(),
                ];

                $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

                $entityManager->persist($historial);
                $entityManager->persist($cliente);

                $entityManager->flush();

                return $this->redirectToRoute('cliente_index');
            } catch (\Exception $e) {
                dd($e);
            }
        }


        return $this->render('cliente/edit.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
            'title' => 'Editar Paciente: ' . $cliente->getNombre() . ' ' . $cliente->getApellido(),
            'familiarExtraActuales' => $familiarExtraActuales,
        ]);
    }

    /**
     * @Route("/derivar/{id}", name="cliente_derivar", methods={"GET"})
     */
    public function derivar(Cliente $cliente, BookingRepository $bookingRepository): Response
    {
        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        return $this->render('cliente/derivar.html.twig', [
            'cliente' => $cliente,
            'tieneTurnos' => count($turnos),
            'turnos' => $turnos,
        ]);
    }

    /**
     * @Route("/derivar/guardar/{id}", name="cliente_guardar_derivacion", methods={"POST"})
     */
    public function guardarDerivacion(Cliente $cliente, Request $request): Response
    {
        $user = $this->security->getUser();
        
        // Recopilamos los parámetros del formulario
        $parametros = [
            'derivadoEn' => $request->get('derivadoEn') ?? '',
            'fechaDerivacion' => ($request->get('fechaDerivacion')) ? new \DateTime($request->get('fechaDerivacion')) : new \DateTime(),
            'motivoDerivacion' => $request->get('motivo') ?? '',
            'empresaTransporteDerivacion' => $request->get('empDeTraslado') ?? '',
        ];
        
        // Utilizamos el servicio para cambiar el estado
        $this->patientStateService->cambiarADerivado($cliente, $user, $parametros);

        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/darpermiso/{id}", name="cliente_permiso", methods={"GET"})
     */
    public function darPermisoForm(Cliente $cliente, BookingRepository $bookingRepository): Response
    {
        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        return $this->render('cliente/darPermiso.html.twig', [
            'cliente' => $cliente,
            'tieneTurnos' => count($turnos),
            'turnos' => $turnos,
        ]);
    }

    /**
     * @Route("/permiso/{id}", name="cliente_dar_permiso", methods={"POST"})
     */
    public function darPermiso(Cliente $cliente, Request $request): Response
    {
        $user = $this->security->getUser();
        
        // Recopilamos los parámetros del formulario
        $parametros = [
            'fechaBajaPorPermiso' => ($request->get('fechaPermisoDesde')) ? new \DateTime($request->get('fechaPermisoDesde')) : new \DateTime(),
            'fechaAltaPorPermiso' => ($request->get('fechaPermisoHasta')) ? new \DateTime($request->get('fechaPermisoHasta')) : new \DateTime(),
        ];
        
        // Utilizamos el servicio para cambiar el estado
        $this->patientStateService->cambiarAPermiso($cliente, $user, $parametros);

        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/ambulatorio/{id}", name="cliente_ambulatorio", methods={"GET"})
     */
    public function ambulatorio(Cliente $cliente): Response
    {
        $user = $this->security->getUser();
        
        // Utilizamos el servicio para cambiar el estado a ambulatorio
        $this->patientStateService->cambiarAAmbulatorio($cliente, $user);
        
        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/permiso/reingresar/{id}", name="cliente_reingreso_permiso", methods={"GET", "POST"})
     */
    public function reingresarPermiso(Cliente $cliente, Request $request): Response
    {
        $user = $this->security->getUser();
        
        $form = $this->createForm(ReingresoType::class, $cliente, ['allow_extra_fields' =>true, 'tipo' => 'permiso']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Usar el servicio para reingreso de paciente de permiso
            $parametros = [
                'fechaAltaPorPermiso' => $form->get('fechaAltaPorPermiso')->getData() ?? null,
            ];
            
            $this->patientStateService->reingresarDePermiso($cliente, $user, $parametros);
            
            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/reingresar.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reingresar/{id}", name="cliente_reingresar", methods={"GET", "POST"})
     */
    public function reingresar(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles($clienteRepository);

        $haArray = [];
        foreach ( $habitaciones as $ha ) {
            $haArray[$ha->getId()] = $ha->getNombre();
        }
        $haArray = array_flip($haArray);
        $cliente->setDisponibleParaTerapia(true);
        $tipo = $request->query->get('tipo') != null ? $request->query->get('tipo') : 'inactivo';

        $form = $this->createForm(ReingresoType::class, $cliente, ['allow_extra_fields' =>true, 'habitaciones' => $haArray, 'tipo' => $tipo]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            if ($tipo == 'permiso') {
                $parametros = [
                    'dePermiso' => false,
                    'fechaBajaPorPermiso' => $form->get('fechaBajaPorPermiso')->getData(),
                    'fechaAltaPorPermiso' => $form->get('fechaAltaPorPermiso')->getData(),
                    'fechaReingresoDerivacion' => null,
                ];
            } else {
                $ncama = $request->request->get('cliente')['nCama'] ?? null;
                $habitacion = $form->get('habitacion')->getData() ? $habitacionRepository->find($form->get('habitacion')->getData()) : null;

                if($habitacion) {
                    $camasOcupadas = $habitacion->getCamasOcupadas();
                    $habPrivada = $request->request->get('cliente')['habPrivada'] ?? null;

                    if ($habPrivada) {
                        $cliente->setHabPrivada(1);
                        for ($i=1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                            $camasOcupadas[$i] = $i;
                        }
                    } else {
                        $camasOcupadas[$ncama] = $ncama;
                    }
                    $habitacion->setCamasOcupadas($camasOcupadas);
                    $parametros['habitacion'] = $habitacion->getId();
                    $parametros['modalidad'] = 2;
                } else {
                    $cliente->setAmbulatorio(true);
                    $cliente->setFechaAmbulatorio(new \DateTime());
                    $parametros['ambulatorio'] = true;
                    $parametros['modalidad'] = 1;

                }

                // $parametros['dePermiso'] = false;
                $parametros['cama'] = $ncama;

                $cliente->setNCama($ncama);
            }

            if($cliente->getDerivado()) {
                $parametros['dePermiso'] = false;
                $parametros['fechaReingresoDerivacion'] = new \DateTime(); //$form->get('fechaReingresoDerivacion')->getData();;
                $parametros['derivadoEn'] = null;
                $parametros['motivoDerivacion'] = $form->has('motivoReingresoDerivacion') ? $form->get('motivoReingresoDerivacion')->getData() : '';
                $parametros['empresaTransporteDerivacion'] = null;
                $parametros['habitacion'] = $habitacion != null ? $habitacion->getId() : '';
                $parametros['cama'] = $ncama != null ? $ncama : '';

                $cliente->setDerivado(false);
            }

            if($cliente->getAmbulatorio() && $cliente->getHabitacion() != null ) {
                $parametros['derivadoEn'] = null;
                $parametros['ambulatorio'] = false;
                $parametros['modalidad'] = 2;

                $cliente->setAmbulatorio(false);
            }

            if($cliente->getDePermiso()) {
                $cliente->setDePermiso(false);
            }

            if ($tipo == 'inactivos') {
                $parametros['fechaIngreso'] = new \DateTime();
                $parametros['fEgreso'] = 'null';
                $cliente->setFEgreso(null);
            }

            $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

            $entityManager->persist($cliente);
            $entityManager->persist($historial);

            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');

        }

        return $this->render('cliente/reingresar.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),

        ]);
    }

     /**
     * @Route("/{id}", name="cliente_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cliente $cliente): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cliente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($cliente);
            $entityManager->flush();
            //TODO guardar en historial
            $this->liberarCamaCliente($cliente);
        }

        return $this->redirectToRoute('cliente_index', ['pestana' => 'ambulatorios']);
    }

    /**
    * @Route("/presente/ambulatorio/{id}", name="dar_presente", methods={"GET"})
    */
    public function presente(Request $request, Cliente $cliente, PresentesRepository $presenteRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $entityManager = $this->getDoctrine()->getManager();
        $cliente->setAmbulatorioPresente(true);

        $presente = $presenteRepository->findBy(['fecha' => new \DateTime(), 'paciente' => $cliente]);
        if (empty($presente[0])) {
            $presente = new Presentes();
        } else {
            $presente = $presente[0];
        }
        
        $presente->setPaciente($cliente);
        $presente->setFecha(new \DateTime());
        $presente->setValor(true);

        $entityManager->persist($cliente);
        $entityManager->persist($presente);
        $entityManager->flush();

        $pestana = $request->query->get('pestana') ?? 'activos';
        $currentPage = $request->query->get('currentPage') ?? '1';
        $sortField = $request->query->get('sortField') ?? 'hClinica';
        $sortDirection = $request->query->get('sortDirection') ?? 'asc';
        $limit = $request->query->get('limit') ?? '10';

        if (in_array($pestana, ['todas', 'camas-vacias', 'completas'])) return $this->redirectToRoute('habitacion_index', ['pestana' => $pestana]);

        return $this->redirectToRoute('cliente_index', ['pestana' => $pestana, 'currentPage' => $currentPage, 'sortField' => $sortField, 'sortDirection' => $sortDirection, 'limit' => $limit]);
    }

    /**
    * @Route("/ausente/ambulatorio/{id}", name="dar_ausente", methods={"GET"})
    */
    public function ausente(Request $request, Cliente $cliente, PresentesRepository $presenteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $entityManager = $this->getDoctrine()->getManager();
        $cliente->setAmbulatorioPresente(false);

        $presente = $presenteRepository->findBy(['fecha' => new \DateTime(), 'paciente' => $cliente]);

        if (isset($presente[0])) {
            $presente = $presente[0];
            $presente->setValor(false);
        }

        $entityManager->persist($cliente);
        $entityManager->flush();

        $pestana = $request->query->get('pestana') ?? 'activos';
        $currentPage = $request->query->get('currentPage') ?? '1';
        $sortField = $request->query->get('sortField') ?? 'hClinica';
        $sortDirection = $request->query->get('sortDirection') ?? 'asc';
        $limit = $request->query->get('limit') ?? '10';
        
        if (in_array($pestana, ['todas', 'camas-vacias', 'completas'])) return $this->redirectToRoute('habitacion_index', ['pestana' => $pestana]);

        return $this->redirectToRoute('cliente_index', ['pestana' => $pestana, 'currentPage' => $currentPage, 'sortField' => $sortField, 'sortDirection' => $sortDirection, 'limit' => $limit]);
    }

    /**
     * @Route("/{id}/egreso", name="cliente_egreso", methods={"GET","POST"})
     */
    public function egreso(Request $request, Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();
        $form = $this->createForm(ClienteType::class, $cliente, ['egreso' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $doctoresReferentes = $cliente->getDocReferente();

            foreach ($doctoresReferentes as $doctor) {
                $doctor->addCliente($cliente);
                $entityManager->persist($doctor);
            }
            if ($form->has('fEgreso') && !empty($form->get('fEgreso')->getData())) {
                $cliente->setFEgreso($form->get('fEgreso')->getData());
            }
            
            $fEgresoCliente = $cliente->getFEgreso();

            if ( $fEgresoCliente instanceof \DateTime ) {
                $fechaDeEgresoString = $fEgresoCliente->setTime(00, 00, 00)->format('Y-m-d H:i:s');

                $turnos = $bookingRepository->turnosConFiltro('', $cliente, $fechaDeEgresoString);
    
                foreach ($turnos as $turno) {
                    $entityManager->remove($turno);
                }
            }

            $entityManager->persist($cliente);

            $parametros = [
                'fEgreso' => $cliente->getFEgreso(),
            ];

            if($cliente->getFEgreso() <= new \DateTime()) {
                $this->liberarCamaCliente($cliente);
                $parametros['habitacion'] = '';
                $parametros['cama'] = '';   
            }

            $historial = $this->getHistorialActualizado($cliente, $parametros, $user);
            $entityManager->persist($historial);

            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/edit.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
            'title' => 'Egreso para:' . $cliente->getNombre() . ' ' . $cliente->getApellido(),
        ]);
    }

    /**
     * @Route("/{id}/reingreso", name="cliente_reingreso", methods={"GET","POST"})
     */
    public function reingreso(Request $request, Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles($clienteRepository);

        $haArray = [];
        foreach ( $habitaciones as $ha ) {
            $haArray[$ha->getId()] = $ha->getNombre();
        }
        $haArray = array_flip($haArray);
        $cliente->setDisponibleParaTerapia(true);

        $form = $this->createForm(ReingresoType::class, $cliente, ['allow_extra_fields' =>true, 'habitaciones' => $haArray]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $historial = new HistoriaPaciente();
            $entityManager = $this->getDoctrine()->getManager();

            $ncama = $request->request->get('cliente')['nCama'] ?? null;

            $habitacion = $form->get('habitacion')->getData() ? $habitacionRepository->find($form->get('habitacion')->getData()) : null;



            if($habitacion) {
                $camasOcupadas = $habitacion->getCamasOcupadas();
                $habPrivada = $request->request->get('cliente')['habPrivada'] ?? null;

                if ($habPrivada) {
                    $cliente->setHabPrivada(1);
                    for ($i=1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                        $camasOcupadas[$i] = $i;
                    }
                } else {
                    $camasOcupadas[$ncama] = $ncama;
                }
                $habitacion->setCamasOcupadas($camasOcupadas);
                $historial->setHabitacion($habitacion->getId());
                $entityManager->persist($habitacion);
            }

            $cliente->setDerivado(false);
            $cliente->setNCama($ncama);


            $historial->setCama($ncama);
            $historial->setCliente($cliente);
            $historial->setIdPaciente($cliente->getId());
            $historial->setFecha(new \DateTime());

            $historial->setFechaReingresoDerivacion($form->get('fechaReingresoDerivacion')->getData() ?? null);
            $historial->setDerivadoEn(null);
            $historial->setMotivoDerivacion($form->get('motivoReingresoDerivacion')->getData() ?? null);
            $historial->setEmpresaTransporteDerivacion(null);
            $historial->setUsuario($user->getUsername());
            



            $entityManager->persist($cliente);

            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');

        }

        return $this->render('cliente/reingresar.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/epicrisis/{id}", name="epicrisis", methods={"GET"})
     */
    public function epicrisis(Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository, NotasTurnoRepository $notasTurnoRepository, BookingRepository $bookingRepository, NotasHistoriaClinicaRepository $notasHistoriaClinicaRepository, HistoriaEgresoRepository $historiaEgresoRepository): Response
    {
        $historiaPaciente = $historiaPacienteRepository->findBy(['id_paciente' => $cliente->getId()]);

        $obrasSociales = $obraSocialRepository->findAll();
        $obraSocialesArray = [];
        foreach ($obrasSociales as $obraSocial) {
            $obraSocialesArray[$obraSocial->getId()] = $obraSocial->getNombre();
        }


        $turnos = $bookingRepository->turnosConFiltro('', $cliente->getId(), '', '', 1);
        $notasTurnos = [];
        foreach ($turnos as $turno) {
            $notas = $notasTurnoRepository->findBy(['turno' => $turno] );
            if ( !empty($notas) ) {
                $notasTurnos[$turno->getId()]['fecha'] = $turno->getBeginAt();
                foreach ($notas as $nota ) {
                    $notasTurnos[$turno->getId()]['notas'][$nota->getId()] = $nota->getText();
                }

            }

        }

        $notasHistoria = $notasHistoriaClinicaRepository->findBy(['cliente' => $cliente]);
        $historiaEgreso = $historiaEgresoRepository->findBy(['cliente' => $cliente]);

        return $this->render('cliente/historia.html.twig', [
            'cliente' => $cliente,
            'historiaPaciente' => $historiaPaciente,
            'obraSociales' => $obraSocialesArray,
            'paginaImprimible' => true,
            'notasTurnos' => $notasTurnos,
            'notasHistoria' => $notasHistoria,
            'titulo_solo' => true,
            'evoluciones' => $cliente->getEvolucions(),
            'ingreso' => $cliente->getHistoriaIngreso(),
            'historiaEgreso' => $historiaEgreso
        ]);
    }

    /**
     * @Route("/{id}/historia", name="cliente_historial", methods={"GET"})
     */
    public function historia(Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository, NotasTurnoRepository $notasTurnoRepository, BookingRepository $bookingRepository, NotasHistoriaClinicaRepository $notasHistoriaClinicaRepository, EvolucionRepository $evolucionRepository, HistoriaEgresoRepository $historiaEgresoRepository, Request $request, DoctorRepository $doctorRepository, UserRepository $userRepository, HabitacionRepository $habitacionRepository, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $puedenEditarEvoluciones = in_array('ROLE_EDIT_HC', $this->getUser()->getRoles());

        $tipos = [
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
            'Profesional por prestacion',
            'Medico de guardia',
            'Medico Clínico',
            'HidroTerapia motora',
            'Kinesiologo',
            'Kinesiologo respiratorio',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];

        $evolucionesDesde   = $request->get('evolucionesDesde');
        $evolucionesHasta   = $request->get('evolucionesHasta');  
        $fechaDesde         = $evolucionesDesde   ? new \DateTime($evolucionesDesde. '0:0:0')   : $evolucionesDesde;
        $fechaHasta         = $evolucionesHasta   ? new \DateTime($evolucionesHasta. '23:59:59'): $evolucionesHasta;
        $tiposEvolucion     = $request->query->get('filtrarPorTipo') ?? [];

        $evoluciones = $evolucionRepository->findByFechaClienteYtipos($cliente, $fechaDesde, $fechaHasta, $tiposEvolucion);

        $docId = $request->query->get('prof', 0);
        $doc = $doctorRepository->find($docId);
        $evArray = [];

        if ($doc) {
            foreach ($evoluciones as $evolucion) {
                $doctor = $doctorRepository->findBy(['email' => $evolucion->getUser()]);

                if (count($doctor) == 0) {
                    $doctor = $userRepository->findBy(['email' => $evolucion->getUser()]);
                }
                if (count($doctor) == 0) {
                    $doctor = $userRepository->findBy(['user' => $evolucion->getUser()]);
                }
                $firma = '';
                if (count($doctor) > 0) {
                    $firma = $doctor[0]->getFirma();
                }

                if($doc->getEmail() === $doctor[0]->getEmail()) {
                    $evArray[] = ['evolucion' => $evolucion, 'firma' => $firma];
                }
            }
        } else {
            foreach ($evoluciones as $evolucion) {
                $doctor = $doctorRepository->findBy(['email' => $evolucion->getUser()]);

                if (count($doctor) == 0) {
                    $doctor = $userRepository->findBy(['email' => $evolucion->getUser()]);
                }
                if (count($doctor) == 0) {
                    $doctor = $userRepository->findBy(['user' => $evolucion->getUser()]);
                }
                $firma = '';
                if (count($doctor) > 0) {
                    $firma = $doctor[0]->getFirma();
                }

                $evArray[] = ['evolucion' => $evolucion, 'firma' => $firma];
            }
        }

        $novedadesDesde   = $request->get('novedadesDesde');
        $novedadesHasta   = $request->get('novedadesHasta');  
        $fechaDesde       = $novedadesDesde   ? new \DateTime($novedadesDesde. '0:0:0')   : $novedadesDesde;
        $fechaHasta       = $novedadesHasta   ? new \DateTime($novedadesHasta. '23:59:59'): $novedadesHasta;

        $historiaPaciente = $historiaPacienteRepository->getHistorialDesdeHasta($cliente, $fechaDesde, $fechaHasta);

        $obrasSociales = $obraSocialRepository->findAll();
        $obraSocialesArray = [];
        foreach ($obrasSociales as $obraSocial) {
            $obraSocialesArray[$obraSocial->getId()] = $obraSocial->getNombre();
        }

        $notasDesde   = $request->get('notasDesde');
        $notasHasta   = $request->get('notasHasta');  
        $fechaDesde   = $notasDesde   ? new \DateTime($notasDesde. '0:0:0')   : $notasDesde;
        $fechaHasta   = $notasHasta   ? new \DateTime($notasHasta. '23:59:59'): $notasHasta;
        $notasTipo    = $request->query->get('notasTipo') ?? '';
        $section      = $request->query->get('section') ?? '';

        $turnos         = [];
        $doctores       = "";
        $notasTurnos    = [];

        if($notasTipo) {
            $arrTipo = [$notasTipo];
            $doctores = $doctorRepository->findByContratos($arrTipo, null);
            foreach ($doctores as $doctor) {
                $turnos[] = $bookingRepository->turnosConFiltro($doctor, $cliente->getId(), $fechaDesde, $fechaHasta, 1);
                foreach ($turnos as $turno) {
                    $notas = $notasTurnoRepository->findBy(['turno' => $turno] );
                    if ( !empty($notas) ) {
                        $notasTurnos[$turno->getId()]['fecha'] = $turno->getBeginAt();
                        $notasTurnos[$turno->getId()]['doctor'] = $turno->getDoctorName();
                        $notasTurnos[$turno->getId()]['modalidad'] = $turno->getDoctorModalidad();
                        foreach ($notas as $nota ) {
                            $notasTurnos[$turno->getId()]['notas'][$nota->getId()] = $nota->getText();
                        }
                    }
                }
            }
            dd($turnos);
        } else {
            $turnos = $bookingRepository->turnosConFiltro('', $cliente->getId(), $fechaDesde, $fechaHasta, 1);
            foreach ($turnos as $turno) {
                $notas = $notasTurnoRepository->findBy(['turno' => $turno] );
                if ( !empty($notas) ) {
                    $notasTurnos[$turno->getId()]['fecha'] = $turno->getBeginAt();
                    $notasTurnos[$turno->getId()]['doctor'] = $turno->getDoctorName();
                    $notasTurnos[$turno->getId()]['modalidad'] = $turno->getDoctorModalidad();
                    foreach ($notas as $nota ) {
                        $notasTurnos[$turno->getId()]['notas'][$nota->getId()] = $nota->getText();
                    }
                }
            }
        }
        $notasHistoria = $notasHistoriaClinicaRepository->findBy(['cliente' => $cliente]);
        $historiaEgreso = $historiaEgresoRepository->findBy(['cliente' => $cliente]);
        
        // Obtener las indicaciones médicas recientes para mostrar en la historia clínica (solo activas)
        $indicacionesRecientes = $consumiblesClientesRepository->findIndicacionesParaElCliente($cliente->getId(), null, null, 5, true);
        $ultimaIndicacion = !empty($indicacionesRecientes) ? $indicacionesRecientes[0] : null;
        
        // Nombres de los meses para mostrar en la plantilla
        $mesesNombres = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];

        $habitaciones = $habitacionRepository->findAll();
        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
        }

        $epicrisisIngreso = $cliente->getEpicrisisIngreso();
        $extensionEI = '';
        if ($epicrisisIngreso) {
            $extensionEI = '.'.pathinfo($epicrisisIngreso, PATHINFO_EXTENSION);
        }
        

        // Verificar si el usuario es un doctor
        $user = $this->getUser();
        $isDoctor = false;
        if ($user && $this->isGranted('ROLE_DOCTOR')) {
            $isDoctor = true;
        } elseif ($user && $this->isGranted('ROLE_STAFF') && $user->getDoctor() !== null) {
            $isDoctor = true;
        }
        
        return $this->render('cliente/historia.html.twig', [
                'indicacionesRecientes' => $indicacionesRecientes,
                'ultimaIndicacion' => $ultimaIndicacion,
                'mesesNombres'          => $mesesNombres,
                'cliente'               => $cliente,
                'historiaPaciente'      => $historiaPaciente,
                'obraSociales'          => $obraSocialesArray,
                'paginaImprimible'      => false,//local
                'notasTurnos'           => $notasTurnos,
                'notasHistoria'         => $notasHistoria,
                'titulo_solo'           => true,
                'isDoctor'              => $isDoctor,
                'evoluciones'           => $evArray,
                'ingreso'               => $cliente->getHistoriaIngreso(),
                'historiaEgreso'        => $historiaEgreso,
                'tipoSeleccionado'      => '',
                'notasDesde'            => $notasDesde,
                'notasHasta'            => $notasHasta,
                'notasTipo'             => $notasTipo,
                'section'               => $section,
                'contratos'             => $tipos,
                'tipoEvolucion'         => $tiposEvolucion,
                'evolucionesDesde'      => $evolucionesDesde,
                'evolucionesHasta'      => $evolucionesHasta,
                'puedeEditarEvolucion'  => $puedenEditarEvoluciones,
                'habitacionesArray'     => $habitacionesArray,
                'novedadesDesde'        => $novedadesDesde,
                'novedadesHasta'        => $novedadesHasta,
                'doc'                   => $doc,
                'doctorRepository'      => $doctorRepository,
                'epicrisisIngreso'      => $epicrisisIngreso,
                'extensionEI'           => $extensionEI,
        ]);
    }

    /**
     * @Route("/guardar/epi/{id}", name="guardar_epi", methods={"POST"})
     */
    public function guardarEpi(Cliente $cliente, Request $request)
    {
        $epicrisisAlAlta = $request->get('epicrisisAlAlta');
        $historiaEgreso = new HistoriaEgreso();
        $historiaEgreso->setEpicrisisAlta($epicrisisAlAlta);
        $historiaEgreso->setCliente($cliente);
        $historiaEgreso->setFecha(new \DateTime());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($historiaEgreso);
        $entityManager->flush();

        return $this->redirectToRoute('evolucion_index', ['cliente' => $cliente->getId()]);

    }

    /**
     * @Route("/check/hc", name="cliente_check_hc", methods={"GET"})
     */
    public function checkHc(Request $request, ClienteRepository $clienteRepository)
    {
        $libre = true;
        $message = '';
        $hc = $request->query->get('hc') ?? 0;
        $id = $request->query->get('id') ?? 0;


        $cliente = $clienteRepository->findBy(['hClinica' => $hc], ['id'=>'DESC'], 1);

        if(count($cliente) && $cliente[0]->getId() != $id) {
            $libre = false;
            $message = 'El número de historia clínica ya se encuentra en uso.';
        }

        return new JsonResponse(['libre' => $libre, 'message' => $message]);

    }

    /**
     * @Route("/download/pdf/adjunto/", name="download_pdf_adjunto")
     **/
    public function downloadFileAction(Request $request){
        $response = new BinaryFileResponse($request->get('path'));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$request->get('nombre'));
        return $response;
    }

    /**
     * @Route("/actualizar/db", name="actualizar_db")
     **/
    public function actualizarDb(Request $request, ClienteRepository $clienteRepository, HistoriaPacienteRepository $historiaPacienteRepository) {
        
        $clientes = $clienteRepository->findAll();
        $entityManager = $this->getDoctrine()->getManager();
        set_time_limit(30000);
        foreach ( $clientes as $cliente ) {
            $historias = $historiaPacienteRepository->findBy(['cliente' => $cliente], ['fecha' => 'asc']);
            
            foreach ( $historias as $index => $historia ) {
                if ( isset($historias[$index + 1]) ) {
                    $historia->setFechaFin($historias[$index + 1]->getFecha());
                    
                    $entityManager->persist($historia);
                    $entityManager->flush();
                }
            }

        }
        die('todo joya!');
    }

    /**
     * @Route("/patologia-select", name="patologia_select", methods={"GET"})
     */
    public function patologiaSelect(Request $request): Response
    {
        $motivoIng = $request->query->get('motivoIng');
        
        // Options for specific pathologies based on selected admission reason
        $options = [];
        switch ($motivoIng) {
            case 1: // Neurologicas
                $options = [
                    'ACV' => 'ACV',
                    'TCE' => 'TCE',
                    'TRM' => 'TRM',
                    'Enfermedad neurodegenerativa' => 'Enfermedad neurodegenerativa',
                    'Otro' => 'Otro',
                ];
                break;
            case 2: // Traumatológicas
                $options = [
                    'Fractura de cadera' => 'Fractura de cadera',
                    'Fractura de miembro inferior' => 'Fractura de miembro inferior',
                    'Fractura de miembro superior' => 'Fractura de miembro superior',
                    'Amputación' => 'Amputación',
                    'Otro' => 'Otro',
                ];
                break;
            case 3: // Respiratorias
                $options = [
                    'EPOC' => 'EPOC',
                    'Neumonía' => 'Neumonía',
                    'Otro' => 'Otro',
                ];
                break;
            case 4: // Paliativos
                $options = [
                    'Oncológico' => 'Oncológico',
                    'No oncológico' => 'No oncológico',
                ];
                break;
            case 5: // Patologías laborales
                $options = [
                    'Accidente laboral' => 'Accidente laboral',
                    'Otro' => 'Otro',
                ];
                break;
        }
        
        if (!$options) {
            return new Response(''); // Return empty response when no specific options
        }
        
        return $this->render('cliente/_patologia_options.html.twig', [
            'options' => $options,
        ]);
    }

    /**
     * Ajusta las camas ocupadas en las habitaciones durante cambios de asignación.
     * 
     * @param Habitacion|null $habitacionNueva Habitación a la que se trasladará el paciente
     * @param int $nuevaCamaId ID de la nueva cama (puede ser 0 para habitación privada)
     * @param Habitacion|null $habVieja Habitación actual del paciente
     * @param int $camaActualId ID de la cama actual
     * @param int $habPrivada Si la habitación actual era privada
     * @param int $habPrivadaNueva Si la nueva habitación será privada
     * @param EntityManager $entityManager
     */
    public function acomodarHabitacion($habitacionNueva, int $nuevaCamaId, $habVieja, int $camaActualId, int $habPrivada, int $habPrivadaNueva, EntityManager $entityManager)
    {
        // Liberar camas en la habitación anterior
        if (!empty($habVieja)) {
            $camasOcupadasViejaHab = $habVieja->getCamasOcupadas();
            
            // Si tenía habitación privada, liberar todas las camas
            if ($habPrivada) {
                $camasOcupadasViejaHab = [];
            } 
            // Si no, liberar solo la cama que ocupaba
            else if ($camaActualId > 0) {
                unset($camasOcupadasViejaHab[$camaActualId]);
            }
            
            $habVieja->setCamasOcupadas($camasOcupadasViejaHab);
            $entityManager->persist($habVieja);
            $entityManager->flush();
        }

        // Asignar camas en la nueva habitación
        if (!empty($habitacionNueva)) {
            $camasOcupadasNuevaHab = $habitacionNueva->getCamasOcupadas();
            
            // Si será habitación privada, ocupar todas las camas
            if ($habPrivadaNueva) {
                for ($i=1; $i <= $habitacionNueva->getCamasDisponibles(); $i++) {
                    $camasOcupadasNuevaHab[$i] = $i;
                }
            } 
            // Si no, ocupar solo la cama asignada (si es > 0)
            else if ($nuevaCamaId > 0) {
                $camasOcupadasNuevaHab[$nuevaCamaId] = $nuevaCamaId;
            }
            
            $habitacionNueva->setCamasOcupadas($camasOcupadasNuevaHab);
            $entityManager->persist($habitacionNueva);
            $entityManager->flush();
        }
    }

    private function liberarCamaCliente($cliente) {
        $habitacionRepository = $this->getDoctrine()->getRepository(Habitacion::class);
        $clienteRepository = $this->getDoctrine()->getRepository(Cliente::class);

        if($cliente->getHabitacion()) {
            $habitacionActual = $habitacionRepository->find($cliente->getHabitacion());

            // Verificar si hay otros pacientes en la misma habitación para evitar liberar sus camas
            $otrosPacientes = $clienteRepository->findBy([
                'habitacion' => $habitacionActual->getId(),
                'fEgreso' => null
            ]);
            
            // Filtrar el cliente actual de la lista
            $otrosPacientes = array_filter($otrosPacientes, function($p) use ($cliente) {
                return $p->getId() != $cliente->getId();
            });

            // Determinar qué camas deben permanecer ocupadas
            $camasOcupadas = [];
            $camasAsignadas = 0;
            foreach ($otrosPacientes as $paciente) {
                if ($paciente->getNCama() !== null) {
                    // Si el paciente tiene número de cama (incluso si es 0), mantenerlo
                    $camasOcupadas[$paciente->getNCama()] = $paciente->getNCama();
                } else {
                    // Si hay pacientes sin número de cama, asignarles una
                    $camasAsignadas++;
                    $numeroCama = $camasAsignadas;
                    
                    // Buscar la primera cama disponible
                    while (isset($camasOcupadas[$numeroCama])) {
                        $numeroCama++;
                    }
                    
                    // Asignar la cama al paciente y actualizar el registro
                    $paciente->setNCama($numeroCama);
                    $camasOcupadas[$numeroCama] = $numeroCama;
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($paciente);
                }
            }

            // Actualizar las camas ocupadas de la habitación
            $habitacionActual->setCamasOcupadas($camasOcupadas);

            $cliente->setHabitacion(null);
            $cliente->setNCama(null);
            $cliente->setHabPrivada(0);

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($habitacionActual);
            $entityManager->persist($cliente);
            $entityManager->flush();
        }
    }

    private function getHistorialActualizado(Cliente $cliente, $parametros, $user)
    {
        $historiaPacienteRepository = $this->getDoctrine()->getRepository(HistoriaPaciente::class);
        $ultimoHistorial = $historiaPacienteRepository->findBy(['cliente' => $cliente], ['fecha' => 'desc'], ['limit' => 1]);

        $historial = new HistoriaPaciente();

        $modalidad = (isset($parametros['modalidad'])) ? $parametros['modalidad'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getModalidad() : null);
        $patologia = (isset($parametros['patologia'])) ? $parametros['patologia'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getPatologia() : null);
        $patologiaEspecifica = (isset($parametros['patologiaEspecifica'])) ? $parametros['patologiaEspecifica'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getPatologiaEspecifica() : null);
        $obraSocial = (isset($parametros['obraSocial'])) ? $parametros['obraSocial'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getObraSocial() : null);
        $nAfiliadoObraSocial = (isset($parametros['nAfiliadoObraSocial'])) ? $parametros['nAfiliadoObraSocial'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getNAfiliadoObraSocial() : null);
        $sistemaDeEmergencia = (isset($parametros['sistemaDeEmergencia'])) ? $parametros['sistemaDeEmergencia'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getSistemaDeEmergencia() : null);
        $nAfiliadoSistemaDeEmergencia = (isset($parametros['nAfiliadoSistemaDeEmergencia'])) ? $parametros['nAfiliadoSistemaDeEmergencia'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getNAfiliadoSistemaDeEmergencia() : null);
        $habitacion = (isset($parametros['habitacion'])) ? $parametros['habitacion'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getHabitacion() : null);
        $cama = (isset($parametros['cama'])) ? $parametros['cama'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getCama() : null);
        $fechaIngreso = (isset($parametros['fechaIngreso'])) ? $parametros['fechaIngreso'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaIngreso() : null);
        $fEgreso = (isset($parametros['fEgreso'])) ? ($parametros['fEgreso'] === 'null' ? null : $parametros['fEgreso']) : (isset($ultimoHistoria[0]) ? $ultimoHistorial[0]->getFechaEngreso() : null);
        $fechaDerivacion = (isset($parametros['fechaDerivacion'])) ? $parametros['fechaDerivacion'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaDerivacion() : null);
        $fechaReingresoDerivacion = (isset($parametros['fechaReingresoDerivacion'])) ? $parametros['fechaReingresoDerivacion'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaReingresoDerivacion() : null);
        $motivoDerivacion = (isset($parametros['motivoDerivacion'])) ? $parametros['motivoDerivacion'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getMotivoDerivacion() : null);
        $derivadoEn = (isset($parametros['derivadoEn'])) ? $parametros['derivadoEn'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getDerivadoEn() : null);
        $empresaTransporteDerivacion = (isset($parametros['empresaTransporteDerivacion'])) ? $parametros['empresaTransporteDerivacion'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getEmpresaTransporteDerivacion() : null);
        $fechaAltaPorPermiso = (isset($parametros['fechaAltaPorPermiso'])) ? $parametros['fechaAltaPorPermiso'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaAltaPorPermiso() : null);
        $fechaBajaPorPermiso = (isset($parametros['fechaBajaPorPermiso'])) ? $parametros['fechaBajaPorPermiso'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaBajaPorPermiso() : null);
        $dePermiso = (isset($parametros['dePermiso'])) ? $parametros['dePermiso'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getDePermiso() : null);
        $ambulatorio = (isset($parametros['ambulatorio'])) ? $parametros['ambulatorio'] : (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getAmbulatorio() : null);
        $docReferente = null;
        if ((isset($parametros['docReferente']))) {
            foreach ($parametros['docReferente'] as $doc) {
                $docReferente[] = $doc->getId();
            }
            $docReferente = json_encode($docReferente);
        } else if (isset($ultimoHistorial[0])) {
            $docReferente = $ultimoHistorial[0]->getDocReferente();
        }

        $historial->setCliente($cliente);
        $historial->setModalidad($modalidad);
        $historial->setPatologia($patologia);
        $historial->setPatologiaEspecifica($patologiaEspecifica);
        
        if ($obraSocial instanceof ObraSocial) {
            $historial->setObraSocial($obraSocial);
        } else if ( $obraSocial ) {
            $obraSocialRepo = $this->getDoctrine()->getRepository(ObraSocial::class);
            $obraSocial = $obraSocialRepo->find($obraSocial);
            $historial->setObraSocial($obraSocial);
        }

        $fecha = new \DateTime();

        $historial->setNAfiliadoObraSocial($nAfiliadoObraSocial);
        $historial->setSistemaDeEmergencia($sistemaDeEmergencia);
        $historial->setNAfiliadoSistemaDeEmergencia($nAfiliadoSistemaDeEmergencia);
        $historial->setHabitacion($habitacion);
        $historial->setCama($cama);
        $historial->setIdPaciente($cliente->getId());
        $historial->setFecha($fecha);
        $historial->setFechaIngreso($fechaIngreso);
        $historial->setFechaEngreso($fEgreso);
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

        if( isset($ultimoHistorial[0]) ) {
            if (!empty($fEgreso)) {
                $fecha = $fEgreso;
            }
            $ultimoHistorial[0]->setFechaFin($fecha);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ultimoHistorial[0]);
            $entityManager->flush();
        } else {
            $fechaIngreso = $fechaIngreso ? $fechaIngreso : $fecha;
            $historial->setFecha($fechaIngreso);
        }

        return $historial;
    }
}
