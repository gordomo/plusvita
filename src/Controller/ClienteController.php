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
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaEgresoRepository;
use App\Repository\HistoriaHabitacionesRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\NotasHistoriaClinicaRepository;
use App\Repository\NotasTurnoRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\UserRepository;
use App\Repository\PresentesRepository;
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
    
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/", name="cliente_index", methods={"GET"})
     */
    public function index(Request $request, ClienteRepository $clienteRepository, HabitacionRepository $habitacionRepository, ObraSocialRepository $obraSocialRepository): Response
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
        $limit = $request->query->get('limit', 100);
        $maxPages = null;
        $query = '';

        $obrasSociales = $obraSocialRepository->findBy(array(), array('nombre' => 'ASC'));
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }

        if ($pestana == 'inactivos') {
            $clientes = $clienteRepository->findInActivos(new \DateTime(), $nombreInput, $currentPage, $limit, null, $idObra);
            } else if ( $pestana == 'derivados') {
            $clientes = $clienteRepository->findDerivados(new \DateTime(), $nombreInput, $currentPage, $limit, null, $idObra);
        } else if ( $pestana == 'permiso') {
            $clientes = $clienteRepository->findDePermiso(new \DateTime(), $nombreInput, $currentPage, $limit, null, $idObra);
        } else if ( $pestana == 'ambulatorios') {
            $clientes = $clienteRepository->findAmbulatorios(new \DateTime(), $nombreInput, $currentPage, $limit, null, $idObra);
        } else {
            $clientes = $clienteRepository->findActivos(new \DateTime(), $nombreInput, $currentPage, $limit, $hab, null, $idObra);
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
            'puedenEditarEvoluciones' => in_array('ROLE_EDIT_HC', $this->getUser()->getRoles())
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
            if ($cliente && (!$cliente->getFEgreso() or $cliente->getFEgreso() >= $fechaHasta)) {
                $histArray[$cliente->getNombreApellido()] = array_reverse($historia);
            }
        }

        //$histArray = array_reverse($histArray);

        $historiasPaginado['results'] = array_slice($histArray, $limit * ($currentPage - 1), $limit);
        $historiasPaginado['total'] = count($histArray);
        $maxPages = ceil($historiasPaginado['total'] / $limit);

        $docReferentes = $doctorRepository->findByContratos(['Fisiatra', 'Director medico', 'Sub director medico'], false);

        $habitaciones = $habitacionRepository->findAll();
        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
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
    public function historicoPrueba(Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, ObraSocialRepository $obraSocialRepository, DoctorRepository $doctorRepository, HistoriaPacienteRepository $historiaPacienteRepository, HistoriaHabitacionesRepository $historiaHabitacionesRepository, PresentesRepository $presenteRepository, EvolucionRepository $evolucionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        } else if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('doctor_historia');
        }

        //$estado = $request->query->get('estado') ?? '1';
        $nombre = $request->query->get('nombre') ?? '';
        $nombre = (!empty($nombre)) ? $nombre : null;
        $prof = $request->query->get('prof') ?? null;
        $nombreInput = $request->query->get('nombreInput');
        $modalidad = $request->query->get('modalidad', 0);
        $limit = $request->query->get('limit', 10);
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

        $totalDia               = [];
        $internados             = [];
        $derivados              = [];
        $sinModalidad           = [];
        $ambulatorios           = [];
        $egresos                = [];

        $referentes             = [];
        $arrayParaLaVista       = [];
        $obrasSocialesTotales   = [];
        $totalReferentes        = [];
        $range                  = [];
        $historias              = null;

        if($from && $to) {
            $vencimientoAut = \DateTime::createFromFormat("d/m/Y", $vto);   
            if ($fechaHasta > new \DateTime()) {
                $fechaHasta = new \DateTime();
            }
            if ($fechaDesde && $fechaHasta) {
                $interval = new DateInterval("P1D");
                $range = new DatePeriod($fechaDesde, $interval, $fechaHasta);
            }

            if($fechaDesde > $fechaHasta) {
                $fechaHasta = $fechaDesde;
            }

            $historias = $historiaPacienteRepository->getHistoricoDesdeHasta($fechaDesde, $fechaHasta, $nombre, $modalidad, $obraSocial, $prof, $hc);

            // $historiasPaginado['results'] = array_slice($historias, $limit * ($currentPage - 1), $limit);
            // $historiasPaginado['total'] = count($historias);
            // $maxPages = ceil($historiasPaginado['total'] / $limit);

            foreach ($historias as $historia) {
                $cliente = $historia->getCliente();
                if (!$cliente) {
                    $cliente = $clienteRepository->find($historia->getIdPaciente());
                }
                $fechaDesde2 = (!empty ($historia->getFecha()) && $historia->getFecha() >= $fechaDesde) ? $historia->getFecha() : $fechaDesde;
                $fechaDesde2 = (($cliente->getFIngreso() != null) && $fechaDesde2 < $cliente->getFIngreso()) ? $cliente->getFIngreso()->format('d-m-Y') : $fechaDesde2->format('d-m-Y');
                $fechaHasta2 = (!empty ($historia->getFechaFin()) && $historia->getFechaFin() <= $fechaHasta) ? $historia->getFechaFin() : $fechaHasta;
                $fechaHasta2 = (($cliente->getFEgreso() != null) && $fechaHasta2 > $cliente->getFEgreso()) ? $cliente->getFEgreso()->format('d-m-Y') : $fechaHasta2->format('d-m-Y');
                $fechaDesde2 = new \DateTime($fechaDesde2. '0:0:0');
                $fechaHasta2 = new \DateTime($fechaHasta2. '23:59:59');
                //$fechaHasta2->modify('+1 day');

                $interval = new DateInterval("P1D");
                $range2 = new DatePeriod($fechaDesde2, $interval, $fechaHasta2);
                
                foreach ( $range2 as $date ) {
                    $date->setTime(23, 59, 59);
                    $texto = '';
                    if($historia->getFechaFin()) $historia->getFechaFin()->setTime(23, 59, 59);
                    if($historia->getFecha()) $historia->getFecha()->setTime(23, 59, 59);
                    
                    if ($historia->getFecha() <= $date && ($historia->getFechaFin() >= $date) or ($historia->getFechaFin() == null) ) {
                        if($cliente->getFEgreso()) $cliente->getFEgreso()->setTime(23, 59, 59);
                        
                        if ($cliente->getFEgreso() == $date ) {
                            $texto = 'Egreso';
                            $egresos[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                        } else if ( $historia->getFechaDerivacion() != null && $date >= $historia->getFechaDerivacion()->setTime(23, 59, 59) && ( $historia->getFechaReingresoDerivacion() == null or $historia->getFechaReingresoDerivacion()->setTime(23, 59, 59) <= $historia->getFechaDerivacion() or $date <= $historia->getFechaReingresoDerivacion()) ) {
                            $texto = 'Derivado';
                            $derivados[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                        } else if ( $historia->getModalidad() != 2 ) {
                            
                                                  
                            switch ($historia->getModalidad()) {
                                case 1:
                                    $texto = 'Ambulatorio';
                                    $ambulatorios[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                                    break;
                                case 3:
                                    $texto = 'Hospital de día';
                                    $ambulatorios[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                                    break;
                                case 4:
                                    $texto = 'ART';
                                    $ambulatorios[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                                    break;
                                default:
                                    $texto = 'Sin modalidad registrada';
                                    $sinModalidad[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                                    break;
                            }

                            $presente = $presenteRepository->findBy(['fecha' => $date, 'paciente' => $historia->getCliente()]);
                            if(!empty( $presente )) {
                                $texto .= ' ' . $presente[0]->getValor() == 1 ? '<br>presente' : '<br>ausente';
                            } else {
                                $habitacion = $historiaHabitacionesRepository->findBy(['fecha' => $date, 'cliente' => $historia->getCliente()]);
                                if(!empty( $habitacion )) {
                                    $texto .= '<br>H:' . $habitacion[0]->getHabitacion()->getNombre() . ' C: ' . $habitacion[0]->getNCama();
                                } else {
                                    $texto .= '<br>sin datos';
                                }
                            }
                        } else {
                            $texto .= 'Internado';
                            $habitacion = $historiaHabitacionesRepository->findBy(['fecha' => $date, 'cliente' => $historia->getCliente()]);
                            if(!empty( $habitacion )) {
                                $texto .= '<br>H:' . $habitacion[0]->getHabitacion()->getNombre() . ' C: ' . $habitacion[0]->getNCama();
                            } else {
                                $texto .= '<br>sin datos';
                            }
                            $internados[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                        }
                        
                        
                        $ref = json_decode($historia->getDocReferente()) ?? [];
                        foreach( $ref as $docReferente ) {
                            $doc = $doctorRepository->find($docReferente);
                            if ($doc && $texto != 'Derivado') {
                                $texto .= '<br>' . $doc->getNombreApellido();
                                $referentes[$date->format('d/m/Y')][$doc->getNombreApellido()][$historia->getCliente()->getId()] = "1";
                            }
                        }
                        
                        if (isset($obArray[$historia->getObraSocial()])) {
                            $texto .= '<br><small><b>' . $obArray[$historia->getObraSocial()] . '</b></small>';
                            $obrasSocialesTotales[$date->format('d/m/Y')][$obArray[$historia->getObraSocial()]][$historia->getCliente()->getId()] = "1";
                        }
                        
                        
                        $arrayParaLaVista[$historia->getCliente()->getId()][$date->format('d/m/Y')] = $texto;
                        $totalDia[$date->format('d/m/Y')][$historia->getCliente()->getId()] = '1';
                        
                        
                    } else {
                        $arrayParaLaVista[$historia->getCliente()->getId()][$date->format('d/m/Y')] = $texto;
                    }
                }
            }
            
        }

        $totales = [
            'totalDia' => $totalDia,
            'internados' => $internados,
            'derivados' => $derivados,
            'sinModalidad' => $sinModalidad,
            'ambulatorios' => $ambulatorios,
            'egresos' => $egresos,            
        ];

        foreach ($referentes as $data) {
            foreach ($data as $profName => $data2) {
                if (isset($totalReferentes[$profName])) {
                    $totalReferentes[$profName] = $totalReferentes[$profName] + count($data2);
                } else {
                    $totalReferentes[$profName] = count($data2);
                }
            }
        }

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
        
        $internadosCount = 0;
        $derivadosCount = 0;
        $ambulatoriosCount = 0;
        $sinModalidadCount = 0;
        $egresosCount = 0;

        foreach ($totales['internados'] as $data) {
            $internadosCount += count($data);
        }

        foreach ($totales['ambulatorios'] as $data) {
            $ambulatoriosCount += count($data);
        }
        foreach ($totales['sinModalidad'] as $data) {
            $sinModalidadCount += count($data);
        }
        foreach ($totales['egresos'] as $data) {
            $egresosCount += count($data);
        }
        foreach ($totales['derivados'] as $data) {
            $derivadosCount += count($data);
        }

        
        $docReferentes = $doctorRepository->findByContratos(['Fisiatra', 'Director medico', 'Sub director medico'], false);
        return $this->render('cliente/historico_2.html.twig',
            [
                'obraSociales'                  => $obArray,
                'from'                          => $from,
                'to'                            => $to,
                'vto'                           => $vto,
                'nombre'                        => $nombre,
                'obraSocial'                    => $obraSocial,
                'prof'                          => $prof,
                'profesionales'                 => $docReferentes,
                'modalidad'                     => $modalidad,
                'hab'                           => $hab,
                'paginaImprimible'              => true,
                'hc'                            => $hc,
                'historiaPacienteRepository'    => $historiaPacienteRepository,
                'habitacionRepository'          => $habitacionRepository,
                'doctorRepository'              => $doctorRepository,
                'clienteRepository'             => $clienteRepository,
                'range'                         => $range,
                'historias'                     => $historias,
                'limit'                         => $limit,
                'currentPage'                   => $currentPage,
                'total'                         => count($arrayParaLaVista),
                'pacientes'                     => $arrayParaLaVista,
                'totales'                       => $totales,
                'referentes'                    => $referentes,
                'obrasSocialesTotales'          => $obrasSocialesTotales,
                'totalReferentes'               => $totalReferentes,
                'internadosCount'               => $internadosCount,
                'derivadosCount'                => $derivadosCount,
                'ambulatoriosCount'             => $ambulatoriosCount,
                'sinModalidadCount'             => $sinModalidadCount,
                'egresosCount'                  => $egresosCount,
                'osTotal'                       => $osTotal,
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
                dd($form->getErrors());
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
                $camasDispArray['sin cama'] = 0;
                for ($i = 1; $i <= $cantCamas; $i++) {
                    if(!in_array($i, $camasOcupadas)) {
                        $camasDispArray[$i] = $i;
                    }
                }
                $camasDispArray[$camaActualId] = $camaActualId;
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
                $modalidad = $form->get('modalidad')->getData();
                $cliente->setAmbulatorio($modalidad == 1);
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

                if ($cliente->getNCama()) {
                    $nuevaCamaId = $cliente->getNCama();
                } else {
                    $nuevaCamaId = $form->getExtraData()['nCama'] ?? 0;
                    $cliente->setNCama($nuevaCamaId);
                }

                $habitacionNueva = $habitacionRepository->find($nuevaHabId);
                $habVieja = $habitacionRepository->find($habitacionActualId);

                $this->acomodarHabitacion($habitacionNueva, $nuevaCamaId, $habVieja, $camaActualId, $habPrivada, $habPrivadaNueva, $entityManager);

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
                    'docReferente' => $cliente->getDocReferente(),
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
    public function guardarDerivacion(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository, HistoriaPacienteRepository $historiaPacienteRepository): Response
    {
        $user = $this->security->getUser();
        $derivadoEn = ($request->get('derivadoEn')) ?? '';
        //guardo la fecha que ponene en el formulario, antes guardaba la fecha del día, pero después no coinciden en el histórico
        $fechaDerivacion = ($request->get('fechaDerivacion')) ? new \DateTime($request->get('fechaDerivacion')) : new \DateTime();
        $motivo = ($request->get('motivo')) ?? '';
        $empDeTraslado = ($request->get('empDeTraslado')) ?? '';

        $cliente->setDerivado(true);
        $cliente->setDerivadoEn($derivadoEn);
        $cliente->setFechaDerivacion($fechaDerivacion);
        $cliente->setMotivoDerivacion($motivo);
        $cliente->setEmpTrasladoDerivacion($empDeTraslado);
        $cliente->setDisponibleParaTerapia(false);

        $this->liberarCamaCliente($cliente);

        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        $parametros = [
                        'derivadoEn' => $derivadoEn,
                        'fechaDerivacion' => $fechaDerivacion,
                        'motivoDerivacion' => $motivo,
                        'empresaTransporteDerivacion' => $empDeTraslado,
                        'habitacion' => '',
                        'cama' => '',
                      ];

        $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($historial);
        $entityManager->persist($cliente);
        foreach ($turnos as $turno) {
            $entityManager->remove($turno);
        }


        $entityManager->flush();

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
    public function darPermiso(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();

        $fechaPermisoDesde = ($request->get('fechaPermisoDesde')) ? new \DateTime($request->get('fechaPermisoDesde')) : new \DateTime();
        $fechaPermisoHasta = ($request->get('fechaPermisoHasta')) ? new \DateTime($request->get('fechaPermisoHasta')) : new \DateTime();

        $cliente->setDePermiso(true);
        $cliente->setFechaBajaPorPermiso($fechaPermisoDesde);
        $cliente->setFechaAltaPorPermiso($fechaPermisoHasta);
        $cliente->setDisponibleParaTerapia(false);

        //$this->liberarCamaCliente($cliente);

        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        $parametros = [
            'dePermiso' => true,
            'fechaBajaPorPermiso' => $fechaPermisoDesde,
            'fechaAltaPorPermiso' => $fechaPermisoHasta,
        ];

        $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($historial);
        $entityManager->persist($cliente);
        foreach ($turnos as $turno) {
            $entityManager->remove($turno);
        }

        $entityManager->flush();

        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/ambulatorio/{id}", name="cliente_ambulatorio", methods={"GET"})
     */
    public function ambulatorio(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();

        $cliente->setAmbulatorio(true);
        $cliente->setModalidad(1);
        $cliente->setFechaAmbulatorio(new \DateTime());

        $this->liberarCamaCliente($cliente);

        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        $parametros = [
            'ambulatorio' => true,
            'modalidad' => 1,
            'habitacion' => '',
            'cama' => '',
        ];

        $historial = $this->getHistorialActualizado($cliente, $parametros, $user);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($historial);
        $entityManager->persist($cliente);
        foreach ($turnos as $turno) {
            $entityManager->remove($turno);
        }

        $entityManager->flush();

        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/permiso/reingresar/{id}", name="cliente_reingreso_permiso", methods={"GET", "POST"})
     */
    public function reingresarPermiso(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();
        $cliente->setDisponibleParaTerapia(true);

        $form = $this->createForm(ReingresoType::class, $cliente, ['allow_extra_fields' =>true, 'tipo' => 'permiso']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $cliente->setDePermiso(false);

            $parametros = [
                'dePermiso' => false,
                'fechaBajaPorPermiso' => null,
                'fechaAltaPorPermiso' => $form->get('fechaAltaPorPermiso')->getData() ?? null,
            ];

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
                    $entityManager->persist($habitacion);
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
     * @Route("/patologia-select", name="paciente_patologia_select")
     */
    public function getPatologiasSelect(Request $request): Response
    {
        $cliente = new Cliente();
        $cliente->setMotivoIng($request->query->get('motivoIng'));
        $form = $this->createForm(ClienteType::class, $cliente);

        if (!$form->has('motivoIngEspecifico')) {
            return new Response(null, 204);
        }

        return $this->render('cliente/_motivoIngEspecifico.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/{id}", name="cliente_show", methods={"GET"})
     */
    public function show(Cliente $cliente, AdjuntosPacientesRepository $adjuntosPacientesRepository, FamiliarExtraRepository $familiarExtraRepository): Response
    {
        $familiaresExtra = $familiarExtraRepository->findBy(['cliente_id' => $cliente->getId()]);
        $adjuntosActuales = $adjuntosPacientesRepository->findBy(array('id_paciente' => $cliente->getId()), array('tipo' => 'ASC'));
        $adjuntosArray = [];
        foreach ($adjuntosActuales as $adjunto) {
            $adjuntosArray[$adjunto->getTipo()][] = $adjunto;
        }

        return $this->render('cliente/show.html.twig', [
            'cliente' => $cliente,
            'adjuntosActuales' => $adjuntosArray,
            'familiaresExtra' => $familiaresExtra
        ]);
    }

    /**
     * @Route("/{id}/historia", name="cliente_historial", methods={"GET"})
     */
    public function historia(Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository, NotasTurnoRepository $notasTurnoRepository, BookingRepository $bookingRepository, NotasHistoriaClinicaRepository $notasHistoriaClinicaRepository, EvolucionRepository $evolucionRepository, HistoriaEgresoRepository $historiaEgresoRepository, Request $request, DoctorRepository $doctorRepository, UserRepository $userRepository, HabitacionRepository $habitacionRepository): Response
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
        

        return $this->render('cliente/historia.html.twig', [
                'cliente'               => $cliente,
                'historiaPaciente'      => $historiaPaciente,
                'obraSociales'          => $obraSocialesArray,
                'paginaImprimible'      => false,//local
                'notasTurnos'           => $notasTurnos,
                'notasHistoria'         => $notasHistoria,
                'titulo_solo'           => true,
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

    public function buildErrorArray(FormInterface $form)
    {
        $errors = [];

        foreach ($form->all() as $child) {
            $errors = array_merge(
                $errors,
                $this->buildErrorArray($child)
            );
        }

        foreach ($form->getErrors() as $error) {
            $errors[$error->getCause()->getPropertyPath()] = $error->getMessage();
        }

        return $errors;
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

        return $this->redirectToRoute('cliente_index', ['pestana' => $pestana]);
    }

    /**
    * @Route("/ausente/ambulatorio/{id}", name="dar_ausente", methods={"GET"})
    */
    public function ausente(Request $request, Cliente $cliente, PresentesRepository $presenteRepository): Response
    {
        $user = $this->security->getUser();
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

        return $this->redirectToRoute('cliente_index', ['pestana' => $pestana]);
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

        return $this->redirectToRoute('cliente_historial', ['id' => $cliente->getId()]);

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

    public function acomodarHabitacion($habitacionNueva, int $nuevaCamaId, $habVieja, int $camaActualId, int $habPrivada, int $habPrivadaNueva, EntityManager $entityManager)
    {
        if (!empty($habVieja)) {
            $camasOcupadasViejaHab = $habVieja->getCamasOcupadas();
            for ($i=1; $i <= $habVieja->getCamasDisponibles(); $i++) {
                if ($habPrivada || $camaActualId == $i) {
                    unset($camasOcupadasViejaHab[$i]);
                }
            }
            $habVieja->setCamasOcupadas($camasOcupadasViejaHab);
            $entityManager->persist($habVieja);
            $entityManager->flush();
        }

        if (!empty($habitacionNueva)) {
            $camasOcupadasNuevaHab = $habitacionNueva->getCamasOcupadas();
            for ($i=1; $i <= $habitacionNueva->getCamasDisponibles(); $i++) {
                if ($habPrivadaNueva || $nuevaCamaId == $i) {
                    $camasOcupadasNuevaHab[$i] = $i;
                }
            }
            $habitacionNueva->setCamasOcupadas($camasOcupadasNuevaHab);
            $entityManager->persist($habitacionNueva);
            $entityManager->flush();
        }
    }

    private function liberarCamaCliente($cliente) {
        $habitacionRepository = $this->getDoctrine()->getRepository(Habitacion::class);

        if($cliente->getHabitacion()) {
            $habitacionActual = $habitacionRepository->find($cliente->getHabitacion());

            $habPrivada = $cliente->getHabPrivada();
            $camasOcupadasPorCliente = $habitacionActual->getCamasOcupadas();

            if($habPrivada != null && $habPrivada) {
                $camasOcupadasPorCliente = [];
            } else {
                unset($camasOcupadasPorCliente[$cliente->getNCama()]);
            }

            $habitacionActual->setCamasOcupadas($camasOcupadasPorCliente);

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
