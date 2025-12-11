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
use Doctrine\ORM\EntityManagerInterface;
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
        } else if (!$this->isGranted('patient.view')) {
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
            'puedenEditarEvoluciones' => $this->isGranted('patient.evolve'),
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
        } else if (!$this->isGranted('patient.view')) {
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
    public function historicoPrueba(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository, UserRepository $userRepository, HistoriaPacienteRepository $historiaPacienteRepository, PresentesRepository $presentesRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!$this->isGranted('patient.history')) {
            return $this->redirectToRoute('doctor_historia');
        }

        // Obtener parámetros de filtrado
        $nombre = $request->query->get('nombre') ?? '';
        $nombre = (!empty($nombre)) ? $nombre : null;
        $prof = $request->query->get('prof') ?? null;
        $modalidad = (int)$request->query->get('modalidad', 0);
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
            // IMPORTANTE: Verificar que las fechas son válidas para Agosto 2025
            // Si no se especificó fecha, usar el mes actual (Agosto 2025)
            if (!$from || !$to) {
                $fechaDesde = new \DateTime('2025-08-01 00:00:00');
                $fechaHasta = new \DateTime('2025-08-31 23:59:59');
            }
            
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
            
            // Contar modalidades
            $conteoModalidades = [];
            foreach ($historias as $historia) {
                $mod = $historia->getModalidad();
                if (!isset($conteoModalidades[$mod])) {
                    $conteoModalidades[$mod] = 0;
                }
                $conteoModalidades[$mod]++;
            }

            
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
            
            // Ordenar historias de más antigua a más reciente para que las recientes sobrescriban
            usort($historias, function($a, $b) {
                return $a->getFecha() <=> $b->getFecha();
            });
            
            foreach ($historias as $historia) {
                $cliente = $historia->getCliente();
                if (!$cliente) continue;
                
                $clienteId = $cliente->getId();
                $clientesIdsInvolucrados[$clienteId] = true; // Marcar este ID para cargarlo después
                
                // El rango de validez de esta historia es desde su 'fecha' hasta su 'fecha_fin'
                // NO usar fecha_ingreso porque eso es la entrada original del paciente
                $fechaInicio = max($historia->getFecha(), $fechaDesde);
                $fechaFin = $historia->getFechaFin() ?: $fechaHasta;
                
                // Si el paciente tiene fecha de egreso, no mostrar después de esa fecha
                if ($cliente->getFEgreso() && $cliente->getFEgreso() < $fechaFin) {
                    $fechaFin = $cliente->getFEgreso();
                }
                
                // Crear un rango de días para cada historia
                $intervalHistoria = new DateInterval("P1D");
                // Agregar 1 día a fechaFin para incluir el último día en DatePeriod
                $fechaFinInclusiva = clone $fechaFin;
                $fechaFinInclusiva->add(new DateInterval("P1D"));
                $rangoHistoria = new DatePeriod($fechaInicio, $intervalHistoria, $fechaFinInclusiva);
                
                foreach ($rangoHistoria as $fecha) {
                    $fechaStr = $fecha->format('d/m/Y');
                    // Sobrescribir siempre para que las historias más recientes prevalezcan
                    $historiasPorPaciente[$clienteId][$fechaStr] = $historia;
                }
            }
            
            // Cargar datos de presencia para todos los pacientes involucrados
            $presentes = [];
            if (!empty($clientesIdsInvolucrados)) {
                $clientesIds = array_keys($clientesIdsInvolucrados);
                $datosPresentes = $presentesRepository->getPresentes($clientesIds, $fechaDesde, $fechaHasta);
                
                // Contador para valores true/1 (presentes)
                $conteoPresentes = 0;
                
                foreach ($datosPresentes as $presente) {
                    $clienteId = $presente->getPaciente()->getId();
                    $fechaStr = $presente->getFecha()->format('d/m/Y');
                    $valor = $presente->getValor();
                    $presentes[$clienteId][$fechaStr] = $valor;
                    
                    // Contar los valores que indican presencia
                    if ($valor === true || $valor === 1 || $valor === '1') {
                        $conteoPresentes++;
                    }
                }
            }
            
            
            // Cargar todos los clientes involucrados de una sola vez para evitar consultas repetidas
            $todosClientesInvolucrados = $clienteRepository->findBy(['id' => array_keys($clientesIdsInvolucrados)]);
            foreach ($todosClientesInvolucrados as $cliente) {
                // Obtener la historia más reciente del paciente para conocer su obra social en ese momento
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
                    
                    // Verificar primero si está derivado en esta fecha
                    // LÓGICA CORREGIDA:
                    // Un paciente está DERIVADO si tiene fecha_derivacion Y:
                    // - NO tiene fecha_reingreso, O
                    // - fecha_reingreso es ANTERIOR a fecha_derivacion (reingreso de ciclo anterior, no del actual), O
                    // - la fecha actual es ANTES de la fecha_reingreso (aún no ha reingresado)
                    $estaDerivado = false;
                    if ($historia->getFechaDerivacion() && $fecha >= $historia->getFechaDerivacion()) {
                        // Si no hay fecha de reingreso, está derivado
                        if (!$historia->getFechaReingresoDerivacion()) {
                            $estaDerivado = true;
                        }
                        // Si fecha_reingreso es ANTERIOR a fecha_derivacion, es de un ciclo anterior
                        // Por lo tanto, para esta derivación actual, aún no ha reingresado
                        else if ($historia->getFechaReingresoDerivacion() < $historia->getFechaDerivacion()) {
                            $estaDerivado = true;
                        }
                        // Si fecha_reingreso es posterior a fecha_derivacion, verificar si ya ocurrió
                        else if ($fecha < $historia->getFechaReingresoDerivacion()) {
                            $estaDerivado = true;
                        }
                    }
                    
                    // Si está derivado y se está filtrando por modalidad específica (no "Todos"), excluirlo
                    // Los derivados solo aparecen cuando modalidad = 0 (Todos) o cuando se filtra específicamente por derivados
                    if ($estaDerivado && $modalidad != 0) {
                        continue; // Excluir derivados cuando se filtra por modalidad específica
                    }
                    
                    // Si es día de egreso
                    if ($cliente->getFEgreso() && $fecha->format('Y-m-d') === $cliente->getFEgreso()->format('Y-m-d')) {
                        $texto = 'Egreso';
                        $egresos[$fechaStr][$clienteId] = '1';
                    }
                    // Si está derivado en esta fecha
                    else if ($estaDerivado) {
                        $texto = 'Derivado';
                        $derivados[$fechaStr][$clienteId] = '1';
                    }
                    
                    // FILTRO IMPORTANTE: Si se seleccionó una modalidad específica, solo mostrar esa modalidad
                    // Este filtro se aplica DESPUÉS de verificar derivados para excluirlos correctamente
                    if ($modalidad != 0) {
                        $historiaModalidad = $historia->getModalidad();
                        if ($modalidad == 1) {
                            // Ambulatorios incluye modalidad 1 y 4 (ART)
                            if ($historiaModalidad != 1 && $historiaModalidad != 4) {
                                continue; // Saltar esta fecha si no coincide con la modalidad filtrada
                            }
                        } elseif ($modalidad == 2) {
                            // Internados: solo modalidad 2
                            if ($historiaModalidad != 2) {
                                continue; // Saltar esta fecha si no coincide con la modalidad filtrada
                            }
                        } elseif ($modalidad == 4) {
                            // ART: solo modalidad 4
                            if ($historiaModalidad != 4) {
                                continue; // Saltar esta fecha si no coincide con la modalidad filtrada
                            }
                        } else {
                            // Cualquier otra modalidad específica
                            if ($historiaModalidad != $modalidad) {
                                continue; // Saltar esta fecha si no coincide con la modalidad filtrada
                            }
                        }
                    }
                    
                    // Si tiene una modalidad ambulatoria (no es internación) y no está derivado
                    if (empty($texto) && $historia->getModalidad() != 2) {
                        // Para ambulatorios: SOLO mostrar si están explícitamente marcados como presentes
                        // Esto asegura que solo se muestren los días con registros en la tabla presentes
                        if ($estaPresenteHoy === true || $estaPresenteHoy === 1 || $estaPresenteHoy === '1') {
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
                            // Si está explícitamente marcado como ausente o no cumple las condiciones, lo saltamos
                            continue; // Saltamos a la siguiente fecha
                        }
                    }
                    // Si es internado y no se ha asignado otro estado
                    else if (empty($texto)) {
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
                    $docReferentes = $historia->getDocReferenteArray() ?? [];

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
        

        $docReferentes = $userRepository->findByRoles(['Fisiatra', 'Director medico', 'Sub director medico']);
        
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
            // Saltar si no hay datos para este cliente
            if (!isset($arrayParaLaVista[$clienteId])) continue;
            
            $periodos = [];
            $periodoActual = null;
            $estadoAnterior = null;
            
            // Ordenar las fechas cronológicamente
            $fechas = array_keys($arrayParaLaVista[$clienteId]);
            sort($fechas);
            
            // Filtrar fechas que estén dentro del rango seleccionado
            $fechasEnRango = [];
            
            // Convertir las fechas string a objetos DateTime
            $fromObj = \DateTime::createFromFormat('Y-m-d', $from);
            $toObj = \DateTime::createFromFormat('Y-m-d', $to);
            
            if (!$fromObj || !$toObj) {
                continue; // Si hay error en las fechas, saltar este paciente
            }
            
            $fromStart = clone $fromObj;
            $fromStart->setTime(0, 0, 0);
            $toEnd = clone $toObj;
            $toEnd->setTime(23, 59, 59);
            
            foreach ($fechas as $fecha) {
                $fechaObj = \DateTime::createFromFormat('d/m/Y', $fecha);
                if ($fechaObj && $fechaObj >= $fromStart && $fechaObj <= $toEnd) {
                    $fechasEnRango[] = $fecha;
                }
            }
            
            // Si no hay fechas en el rango, saltar este paciente
            if (empty($fechasEnRango)) continue;
            
            // Usar las fechas filtradas
            $fechas = $fechasEnRango;
            
            foreach ($fechas as $fecha) {
                $texto = $arrayParaLaVista[$clienteId][$fecha];
                $lineas = explode('<br>', $texto);
                $estado = trim($lineas[0]);
                
                // Para ambulatorios, cada día es un período separado
                // Para otros estados, agrupamos días consecutivos
                $esAmbulatorio = (strpos($estado, 'Ambulatorio') !== false);
                
                if ($periodoActual !== null) {
                    if ($esAmbulatorio) {
                        // Para ambulatorios, siempre guardamos el período anterior
                        $periodos[] = $periodoActual;
                    } else if ($estado === $estadoAnterior) {
                        // Para otros estados, continuamos el período si es el mismo estado
                        $periodoActual['hasta'] = $fecha;
                        // Actualizar los días del período
                        $desde = \DateTime::createFromFormat('d/m/Y', $periodoActual['desde']);
                        $hasta = \DateTime::createFromFormat('d/m/Y', $periodoActual['hasta']);
                        $periodoActual['dias'] = $hasta->diff($desde)->days + 1;
                        continue;
                    } else {
                        // Si cambió el estado, guardamos el período anterior
                        $periodos[] = $periodoActual;
                    }
                }
                $habitacion = '';
                $cama = '';
                $profesional = '';
                $obra_social = '';
                
                // Extraer información de las líneas
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if (strpos($linea, 'H:') === 0) {
                        $habitacionCama = explode(' C:', $linea);
                        $habitacion = trim(str_replace('H:', '', $habitacionCama[0]));
                        if (count($habitacionCama) > 1) {
                            $cama = trim($habitacionCama[1]);
                        }
                    } elseif (strpos($linea, 'sin profesional asignado') === false && 
                             strpos($linea, 'H:') === false && 
                             strpos($linea, 'sin obra social') === false &&
                             !in_array($linea, ['Internado', 'Derivado', 'Egreso'])) {
                        $profesional = $linea;
                    } elseif (strpos($linea, 'sin obra social') === false) {
                        $obra_social = $linea;
                    }
                }
                // Crear nuevo período
                // Calcular los días del período
                $desde = \DateTime::createFromFormat('d/m/Y', $fecha);
                $hasta = \DateTime::createFromFormat('d/m/Y', $fecha);
                $dias = $desde->diff($hasta)->days + 1;

                $periodoActual = [
                    'estado' => $estado,
                    'desde' => $fecha,
                    'hasta' => $fecha,
                    'habitacion' => $habitacion,
                    'cama' => $cama,
                    'profesional' => $profesional,
                    'obra_social' => $obra_social,
                    'dias' => $dias
                ];
                
                $estadoAnterior = $estado;
            }
            
            // Agregar el último período
            if ($periodoActual !== null) {
                $periodos[] = $periodoActual;
            }
            // Agregar paciente con sus períodos al array final
            if (!empty($periodos)) {
                $pacientesPeriodos[] = [
                    'nombre' => $cliente['apellido'] . ' ' . $cliente['nombre'],
                    'hc' => $cliente['hClinica'],
                    'periodos' => $periodos
                ];
            }
        }
        
        // Ordenar pacientes por apellido y nombre
        usort($pacientesPeriodos, function($a, $b) {
            return $a['nombre'] <=> $b['nombre'];
        });
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
                        $nombreProfesional = strip_tags($periodo['profesional']);
                        if (!isset($fisiatrasDiasCama[$nombreProfesional])) {
                            $fisiatrasDiasCama[$nombreProfesional] = $periodo['dias'];
                        } else {
                            $fisiatrasDiasCama[$nombreProfesional] += $periodo['dias'];
                        }
                    }
                }
                // Procesar pacientes ambulatorios (incluye Ambulatorio, Hospital de día y ART)
                elseif ($periodo['estado'] === 'Ambulatorio' || $periodo['estado'] === 'Hospital de día' || $periodo['estado'] === 'ART') {
                    // Calcular las fechas exactas para evitar duplicados
                    $desdeFecha = \DateTime::createFromFormat('d/m/Y', $periodo['desde']);
                    $hastaFecha = \DateTime::createFromFormat('d/m/Y', $periodo['hasta']);
                    
                    // Crear una copia para evitar modificar $hastaFecha directamente
                    $hastaFechaModificada = clone $hastaFecha;
                    $hastaFechaModificada->modify('+1 day');
                    
                    $interval = new \DateInterval('P1D');
                    $fechasPeriodo = new \DatePeriod($desdeFecha, $interval, $hastaFechaModificada);

                    // Contador para este período
                    $diasPeriodo = 0;
                    
                    foreach ($fechasPeriodo as $fecha) {
                        $fechaStr = $fecha->format('Y-m-d');
                        
                        // Verificar presencia del paciente en este día
                        $fechaStrFormato = $fecha->format('d/m/Y');
                        $estaPresenteHoy = isset($presentes[$clienteId][$fechaStrFormato]) ? $presentes[$clienteId][$fechaStrFormato] : null;
                        
                        // Evitar contar el mismo día más de una vez para el mismo paciente
                        // Y verificar si el paciente está presente (si hay un registro)
                        if (!in_array($fechaStr, $diasAmbulatoriosPorPaciente[$pacienteId])) {
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
                                    $nombreProfesional = strip_tags($periodo['profesional']);
                                    if (!isset($fisiatrasAmbulatorio[$nombreProfesional])) {
                                        $fisiatrasAmbulatorio[$nombreProfesional] = 1;
                                    } else {
                                        $fisiatrasAmbulatorio[$nombreProfesional]++;
                                    }
                                    
                                }
                            }
                        }
                    }
                    
                }
            }
        }
        

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
        } else if (!$this->isGranted('patient.view')) {
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
                $cliente->setNCama($form->getExtraData()['nCama']);
                $habPrivada = $form->getExtraData()['habPrivada'] ?? 0;
                
                if ($habPrivada) {
                    $cliente->setHabPrivada(1);
                }
                // Ya no necesitamos actualizar el campo camasOcupadas - se calcula dinámicamente
            }

            $docReferenteIds = [];
            foreach ($cliente->getDocReferente() as $doc) {
                if (is_object($doc) && method_exists($doc, 'getId')) {
                    $docReferenteIds[] = $doc->getId();
                } elseif (is_numeric($doc)) {
                    $docReferenteIds[] = $doc;
                }
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
                'docReferente' => $docReferenteIds,
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
     * @Route("/{id}/show", name="cliente_show", methods={"GET"})
     */
    public function show(Cliente $cliente, ObraSocialRepository $obraSocialRepository, FamiliarExtraRepository $familiarExtraRepository, HabitacionRepository $habitacionRepository, DoctorRepository $doctorRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obtener datos relacionados para mostrar
        $familiarExtraActuales = $familiarExtraRepository->findBy(['cliente_id' => $cliente->getId()]);
        
        // Obtener información de la habitación si existe
        $habitacion = null;
        if ($cliente->getHabitacion()) {
            $habitacion = $habitacionRepository->find($cliente->getHabitacion());
        }
        
        // Obtener información de los doctores referentes si existen
        $doctoresReferentes = $cliente->getDocReferente();
        
        // Obtener el texto del motivo de ingreso
        $motivoIngresoTexto = $this->getMotivoIngresoTexto($cliente->getMotivoIng());

        return $this->render('cliente/show.html.twig', [
            'cliente' => $cliente,
            'familiarExtraActuales' => $familiarExtraActuales,
            'habitacion' => $habitacion,
            'doctoresReferentes' => $doctoresReferentes,
            'motivoIngresoTexto' => $motivoIngresoTexto,
        ]);
    }

    /**
     * Obtiene el texto del motivo de ingreso basado en el número
     */
    private function getMotivoIngresoTexto(?int $motivoIng): string
    {
        $motivos = [
            1 => 'Neurológicas',
            2 => 'Traumatológicas', 
            3 => 'Respiratorias',
            4 => 'Paliativos',
            5 => 'Otros'
        ];
        
        return $motivos[$motivoIng] ?? 'No especificado';
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

                // Calcular camas ocupadas reales consultando pacientes activos
                $pacientesConCamaFisica = $clienteRepository->findClienteEnHabitacion($habitacionActual, true, true);
                $camasOcupadas = [];
                foreach ($pacientesConCamaFisica as $paciente) {
                    if ($paciente->getNCama() > 0) {
                        $camasOcupadas[] = $paciente->getNCama();
                    }
                }
                
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
            // Verificar si solo hay 1 cama ocupada (el paciente actual)
            if(count($camasOcupadas) == 1) {
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
                            // Calcular camas ocupadas reales consultando pacientes activos
                            $pacientesConCamaFisica = $clienteRepository->findClienteEnHabitacion($habitacionNueva, true, true);
                            $camasOcupadas = [];
                            foreach ($pacientesConCamaFisica as $paciente) {
                                if ($paciente->getNCama() > 0) {
                                    $camasOcupadas[] = $paciente->getNCama();
                                }
                            }
                            
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
                    $baseDir = $this->getParameter('adjuntos_pacientes_directory');
                    $dniDir = $form->get('dni')->getData();
                    $path = $baseDir."/".$dniDir;
                    
                    try {
                        // Asegurarnos que el directorio base existe
                        if (!file_exists($baseDir)) {
                            mkdir($baseDir, 0755, true);
                        }
                        
                        // Asegurarnos que el directorio del DNI existe
                        if (!file_exists($path)) {
                            mkdir($path, 0755, true);
                        }
                        
                        // Intentar mover el archivo
                        $epicrisisIngreso->move(
                            $path,
                            $newFilename
                        );
                    } catch (FileException $e) {
                        error_log("Error al subir archivo: " . $e->getMessage());
                        error_log("Path intentado: " . $path);
                        error_log("Permisos del directorio base: " . substr(sprintf('%o', fileperms($baseDir)), -4));
                        dd($e);
                    }
                    $cliente->setEpicrisisIngreso($path."/".$newFilename);
                }

                $docReferenteIds = [];
                foreach ($cliente->getDocReferente() as $doc) {
                    if (is_object($doc) && method_exists($doc, 'getId')) {
                        $docReferenteIds[] = $doc->getId();
                    } elseif (is_numeric($doc)) {
                        $docReferenteIds[] = $doc;
                    }
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
                    'docReferente' => $docReferenteIds,
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

                if($habitacion && $ncama) {
                    // Reingreso como internado (con habitación y cama)
                    $habPrivada = $request->request->get('cliente')['habPrivada'] ?? null;

                    if ($habPrivada) {
                        $cliente->setHabPrivada(1);
                    } else {
                        $cliente->setHabPrivada(0);
                    }
                    
                    $cliente->setModalidad(2);
                    $cliente->setAmbulatorio(false);
                    $cliente->setHabitacion($habitacion->getId());
                    $cliente->setNCama($ncama);
                    $cliente->setFechaAmbulatorio(null);
                    
                    $parametros['habitacion'] = $habitacion->getId();
                    $parametros['modalidad'] = 2;
                    $parametros['ambulatorio'] = false;
                } else {
                    // Reingreso como ambulatorio (sin habitación o sin cama)
                    $cliente->setModalidad(1);
                    $cliente->setAmbulatorio(true);
                    $cliente->setFechaAmbulatorio(new \DateTime());
                    // Limpiar campos de habitación
                    $cliente->setHabitacion(null);
                    $cliente->setNCama(null);
                    $cliente->setHabPrivada(0);
                    
                    $parametros['ambulatorio'] = true;
                    $parametros['modalidad'] = 1;
                    $parametros['habitacion'] = null;
                    $ncama = null; // Asegurar que no se asigne cama
                }

                $parametros['cama'] = $ncama;
            }

            if($cliente->getDerivado()) {
                $parametros['dePermiso'] = false;
                $parametros['fechaReingresoDerivacion'] = new \DateTime();
                $parametros['derivadoEn'] = null;
                $parametros['motivoDerivacion'] = $form->has('motivoReingresoDerivacion') ? $form->get('motivoReingresoDerivacion')->getData() : '';
                $parametros['empresaTransporteDerivacion'] = null;
                
                // Si no se estableció habitación arriba, asegurar que los parámetros sean consistentes
                if (!isset($parametros['habitacion']) || $parametros['habitacion'] === null) {
                    $parametros['habitacion'] = null;
                    $parametros['cama'] = null;
                    $parametros['modalidad'] = 1;
                    $parametros['ambulatorio'] = true;
                }

                $cliente->setDerivado(false);
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
                $habPrivada = $request->request->get('cliente')['habPrivada'] ?? null;

                if ($habPrivada) {
                    $cliente->setHabPrivada(1);
                }
                // Ya no necesitamos actualizar el campo camasOcupadas - se calcula dinámicamente
                $historial->setHabitacion($habitacion->getId());
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
        $puedenEditarEvoluciones = $this->isGranted('patient.edit_evolve');

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
        $doc = $userRepository->find($docId);
        $evArray = [];

        // Procesar cada evolución
        foreach ($evoluciones as $evolucion) {
            // Si hay filtro de doctor, verificar si coincide
            if ($doc && $doc->getEmail() !== $evolucion->getUser()) {
                continue; // Saltar esta evolución si no coincide con el filtro
            }

            $firma = '';
            $doctorData = ['nombre' => '', 'apellido' => '', 'matricula' => ''];
            
            // 1. PRIMERO: Verificar si la evolución ya tiene datos de firma guardados (nuevo sistema)
            if ($evolucion->getFirmaDoctorPath()) {
                $firma = $evolucion->getFirmaDoctorPath();
                $doctorData['nombre'] = $evolucion->getFirmaDoctorNombre() ?: '';
                $doctorData['apellido'] = $evolucion->getFirmaDoctorApellido() ?: '';
                $doctorData['matricula'] = $evolucion->getFirmaDoctorMatricula() ?: '';
            } else {
                // 2. SEGUNDO: Buscar firma activa del usuario en tabla User
                $userDoctor = $userRepository->findOneBy(['email' => $evolucion->getUser()]);
                if ($userDoctor) {
                    // Obtener datos del usuario
                    $doctorData['nombre'] = $userDoctor->getNombre() ?: '';
                    $doctorData['apellido'] = $userDoctor->getApellido() ?: '';
                    $doctorData['matricula'] = $userDoctor->getLegajo() ?: '';
                    
                    // Buscar firma activa en las firmas del usuario
                    $firmas = $userDoctor->getFirmas();
                    if ($firmas && count($firmas) > 0) {
                        foreach ($firmas as $firmaItem) {
                            if ($firmaItem->getIsActive() && $firmaItem->getFilePath()) {
                                $firma = $firmaItem->getFilePath();
                                break; // Solo necesitamos la primera firma activa
                            }
                        }
                    }
                }
                
                // 3. TERCERO: Si no encontró firma en User, buscar en Doctor (sistema viejo)
                if (empty($firma)) {
                    $doctorObj = $doctorRepository->findOneBy(['email' => $evolucion->getUser()]);
                    if ($doctorObj) {
                        $firmaDoctor = $doctorObj->getFirma();
                        if ($firmaDoctor) {
                            $firma = $firmaDoctor;
                        }
                        
                        // Si no obtuvimos datos de User, usar los de Doctor
                        if (empty($doctorData['nombre'])) {
                            $doctorData['nombre'] = $doctorObj->getNombre() ?: '';
                        }
                        if (empty($doctorData['apellido'])) {
                            $doctorData['apellido'] = $doctorObj->getApellido() ?: '';
                        }
                        if (empty($doctorData['matricula'])) {
                            $doctorData['matricula'] = $doctorObj->getLegajo() ?: '';
                        }
                    }
                }
            }

            $evArray[] = ['evolucion' => $evolucion, 'firma' => $firma, 'doctorData' => $doctorData];
        }

        // Revertir para mostrar las más nuevas primero
        $evArray = array_reverse($evArray);

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
        
        // Obtener las indicaciones médicas actuales para mostrar en la historia clínica (solo activas)
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
        } elseif ($user && $this->isGranted('ROLE_STAFF') && $user->hasRole('doctor')) {
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
                'userRepository'        => $userRepository,
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
     * @Route("/actualizar/doc-referente", name="actualizar_doc_referente", methods={"GET"})
     */
    public function actualizarDocReferente(Request $request, EntityManagerInterface $em, HistoriaPacienteRepository $historiaPacienteRepository, ClienteRepository $clienteRepository)
    {
        // Calcular fecha de hace 3 meses
        $fechaDesde = new \DateTime();
        $fechaDesde->modify('-3 months');
        $fechaDesde->setTime(0, 0, 0);

        // Obtener registros de los últimos 3 meses
        $historias = $historiaPacienteRepository->createQueryBuilder('h')
            ->where('h.fecha >= :fechaDesde')
            ->setParameter('fechaDesde', $fechaDesde)
            ->getQuery()
            ->getResult();
        
        $actualizados = 0;
        $errores = 0;
        
        foreach ($historias as $historia) {
            try {
                // Obtener el cliente
                $cliente = $historia->getCliente();
                if (!$cliente) {
                    $errores++;
                    continue;
                }
                
                // Obtener los doctores referentes del cliente
                $docReferentes = $cliente->getDocReferente();
                if ($docReferentes->isEmpty()) {
                    continue; // No tiene doctores referentes
                }
                
                // Convertir los doctores a array de IDs
                $docReferenteIds = [];
                foreach ($docReferentes as $doc) {
                    $docReferenteIds[] = $doc->getId();
                }
                
                // Actualizar el registro
                $historia->setDocReferente(json_encode($docReferenteIds));
                $em->persist($historia);
                $actualizados++;
                
                // Hacer flush cada 100 registros para no sobrecargar la memoria
                if ($actualizados % 100 === 0) {
                    $em->flush();
                }
            } catch (\Exception $e) {
                $errores++;
            }
        }
        
        // Flush final
        $em->flush();
        
        return new JsonResponse([
            'mensaje' => 'Proceso completado',
            'fecha_desde' => $fechaDesde->format('Y-m-d'),
            'actualizados' => $actualizados,
            'errores' => $errores
        ]);
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
     * Build an array of form errors for debugging
     */
    private function buildErrorArray(FormInterface $form): array
    {
        $errors = [];
        
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $child) {
            if ($child->getErrors()->count() > 0) {
                $fieldName = $child->getName();
                $fieldErrors = [];
                foreach ($child->getErrors() as $error) {
                    $fieldErrors[] = $error->getMessage();
                }
                $errors[$fieldName] = $fieldErrors;
            }
        }
        
        return $errors;
    }

    /**
     * @Route("/patologia-select", name="patologia_select", methods={"GET"})
     */
    public function patologiaSelect(Request $request): Response
    {
        $motivoIng = $request->query->get('motivoIng');
        
        // Use the same options as the form
        $options = [];
        switch ($motivoIng) {
            case 1: // Neurologicas
                $options = [
                    'pop' => 'pop',
                    'acv izquemico' => 'acv izquemico',
                    'acv hemorragico' => 'acv hemorragico',
                    'tec' => 'tec',
                    'em' => 'em',
                    'ela' => 'ela',
                    'guillain barre' => 'guillain barre',
                    'trauma medular' => 'trauma medular',
                    'otras' => 'otras'
                ];
                break;
            case 2: // Traumatológicas
                $options = [
                    'pop' => 'pop',
                    'politrauma' => 'politrauma',
                    'amputaciones' => 'amputaciones',
                    'otras' => 'otras'
                ];
                break;
            case 3: // Respiratorias
                $options = [
                    'rehabilitacion respiratoria' => 'rehabilitacion respiratoria',
                    'pop' => 'pop'
                ];
                break;
            case 4: // Paliativos
                $options = [
                    'ca' => 'ca',
                    'otros' => 'otros'
                ];
                break;
            case 5: // Otros
                $options = [
                    'otros' => 'otros'
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
        // Ya no necesitamos actualizar camasOcupadas - se calcula dinámicamente
        // Este método se mantiene para compatibilidad pero ya no manipula el campo JSON
    }

    private function liberarCamaCliente($cliente) {
        $habitacionRepository = $this->getDoctrine()->getRepository(Habitacion::class);
        $clienteRepository = $this->getDoctrine()->getRepository(Cliente::class);

        if($cliente->getHabitacion()) {
            // Limpiar la asignación del cliente
            $cliente->setHabitacion(null);
            $cliente->setNCama(null);
            $cliente->setHabPrivada(0);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cliente);
            $entityManager->flush();
            
            // Ya no necesitamos actualizar camasOcupadas - se calcula dinámicamente
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
            
            // Si es un array de objetos Doctor, extraer los IDs
            if (is_array($parametros['docReferente'])) {
                foreach ($parametros['docReferente'] as $doc) {
                    if (is_object($doc) && method_exists($doc, 'getId')) {
                        $docReferente[] = $doc->getId();
                    } elseif (is_numeric($doc)) {
                        // Si ya es un ID numérico
                        $docReferente[] = $doc;
                    }
                }
            } 
            // Si es una colección de Doctrine
            elseif (is_object($parametros['docReferente']) && method_exists($parametros['docReferente'], 'toArray')) {
                foreach ($parametros['docReferente']->toArray() as $doc) {
                    $docReferente[] = $doc->getId();
                }
            }
            
            if ($docReferente !== null) {
                $docReferente = json_encode($docReferente);

            }
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
