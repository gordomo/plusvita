<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\FamiliarExtra;
use App\Entity\Habitacion;
use App\Entity\HistoriaPaciente;
use App\Form\ClienteType;
use App\Form\ReingresoType;
use App\Repository\AdjuntosPacientesRepository;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use App\Repository\FamiliarExtraRepository;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\NotasTurnoRepository;
use App\Repository\ObraSocialRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

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
    public function index(Request $request, ClienteRepository $clienteRepository, HabitacionRepository $habitacionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $pestana = $request->query->get('pestana') ?? 'activos';
        $nombreInput = $request->query->get('nombreInput');
        $hab = $request->query->get('hab') ?? null;

        if ($pestana == 'inactivos') {
            $clientes = $clienteRepository->findInActivos(new \DateTime(), $nombreInput);
        } else if ( $pestana == 'derivados') {
            $clientes = $clienteRepository->findDerivados(new \DateTime(), $nombreInput);
        } else if ( $pestana == 'permiso') {
            $clientes = $clienteRepository->findDePermiso(new \DateTime(), $nombreInput);
        } else if ( $pestana == 'ambulatorios') {
            $clientes = $clienteRepository->findAmbulatorios(new \DateTime(), $nombreInput);
        } else {
            $clientes = $clienteRepository->findActivos(new \DateTime(), $nombreInput, $hab);
        }

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
            'paginaImprimible' => true,
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
    public function guardarDerivacion(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();
        $derivadoEn = ($request->get('derivadoEn')) ?? '';
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



        $historial = new HistoriaPaciente();
        $historial->setCama(null);
        $historial->setCliente($cliente);
        $historial->setIdPaciente($cliente->getId());
        $historial->setFecha(new \DateTime());
        $historial->setHabitacion(null);
        $historial->setFechaDerivacion($fechaDerivacion);
        $historial->setDerivadoEn($derivadoEn);
        $historial->setMotivoDerivacion($motivo);
        $historial->setEmpresaTransporteDerivacion($empDeTraslado);
        $historial->setUsuario($user->getUsername());

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

        $historial = new HistoriaPaciente();
        //$historial->setCama(null);
        $historial->setCliente($cliente);
        $historial->setIdPaciente($cliente->getId());
        $historial->setDePermiso(true);
        $historial->setFecha(new \DateTime());
        //$historial->setHabitacion(null);
        $historial->setFechaBajaPorPermiso($fechaPermisoDesde);
        $historial->setUsuario($user->getUsername());

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
        $cliente->setFechaAmbulatorio(new \DateTime());

        $this->liberarCamaCliente($cliente);

        $turnos = $bookingRepository->findBy(['cliente' => $cliente]);

        $historial = new HistoriaPaciente();
        $historial->setCama(null);
        $historial->setCliente($cliente);
        $historial->setIdPaciente($cliente->getId());
        $historial->setFecha(new \DateTime());
        $historial->setHabitacion(null);
        $historial->setUsuario($user->getUsername());
        $historial->setAmbulatorio(true);

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
            $historial = new HistoriaPaciente();
            $entityManager = $this->getDoctrine()->getManager();

            $cliente->setDePermiso(false);


            $historial->setCliente($cliente);
            $historial->setIdPaciente($cliente->getId());
            $historial->setFecha(new \DateTime());

            $historial->setFechaAltaPorPermiso($form->get('fechaAltaPorPermiso')->getData() ?? null);
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
     * @Route("/reingresar/{id}", name="cliente_reingresar", methods={"GET", "POST"})
     */
    public function reingresar(Cliente $cliente, Request $request, HabitacionRepository $habitacionRepository): Response
    {
        $user = $this->security->getUser();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles();

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
            $historial = new HistoriaPaciente();
            $entityManager = $this->getDoctrine()->getManager();

            if ($tipo == 'permiso') {
                $historial->setFechaAltaPorPermiso($form->get('fechaAltaPorPermiso')->getData() ?? null);
                $historial->setFechaBajaPorPermiso($form->get('fechaBajaPorPermiso')->getData() ?? null);
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
                    $historial->setHabitacion($habitacion->getId());
                    $entityManager->persist($habitacion);
                }

                $cliente->setNCama($ncama);
                $historial->setCama($ncama);
            }

            $historial->setCliente($cliente);
            $historial->setIdPaciente($cliente->getId());
            $historial->setFecha(new \DateTime());

            if($cliente->getDerivado()) {
                $historial->setFechaReingresoDerivacion($form->get('fechaReingresoDerivacion')->getData() ?? null);
                $historial->setDerivadoEn(null);
                $historial->setMotivoDerivacion($form->get('motivoReingresoDerivacion')->getData() ?? null);
                $historial->setEmpresaTransporteDerivacion(null);
                $cliente->setDerivado(false);
            }

            if($cliente->getAmbulatorio()) {
                $historial->setDerivadoEn(null);
                $cliente->setAmbulatorio(false);
            }

            if($cliente->getDePermiso()) {
                $cliente->setDePermiso(false);
            }

            if ($tipo == 'inactivos') {
                $historial->setFechaIngreso(new \DateTime());
                $cliente->setFEgreso(null);
            }


            $historial->setUsuario($user->getUsername());

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
     * @Route("/new", name="cliente_new", methods={"GET","POST"})
     */
    public function new(Request $request, ObraSocialRepository $obraSocialRepository, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository): Response
    {
        $user = $this->security->getUser();

        $cliente = new Cliente();
        $historial = new HistoriaPaciente();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles();

        $cliente->setActivo(true);
        $cliente->setFIngreso(new \DateTime());

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

        $cliente ->setHClinica($clienteRepository->findLastHClinica() + 1);

        $form = $this->createForm(ClienteType::class, $cliente, ['allow_extra_fields' =>true, 'is_new' => true, 'obrasSociales' => $obArray, 'habitaciones' => $haArray]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $familiarResponsableExtraNombres = $request->request->get('familiarResponsableExtraNombre');
            $familiarResponsableExtraTel = $request->request->get('familiarResponsableExtraTel');
            $familiarResponsableExtraMail = $request->request->get('familiarResponsableExtraMail');
            $familiarResponsableExtraVinculo = $request->request->get('familiarResponsableExtraVinculo');
            $familiarResponsableExtraAcompanante = $request->request->get('familiarResponsableExtraAcompanante');

            $entityManager = $this->getDoctrine()->getManager();
            $doctoresReferentes = $cliente->getDocReferente();

            foreach ($doctoresReferentes as $doctor) {
                $doctor->addCliente($cliente);
                $entityManager->persist($doctor);
            }

            $entityManager->persist($cliente);
            $entityManager->flush();

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



            $historial = new HistoriaPaciente();
            $historial->setIdPaciente($cliente->getId());
            $historial->setCama($cliente->getNCama());
            $historial->setHabitacion($cliente->getHabitacion());
            $historial->setNAfiliadoObraSocial($cliente->getObraSocialAfiliado());
            $historial->setObraSocial($cliente->getObraSocial());
            $historial->setModalidad($cliente->getModalidad());
            $historial->setPatologia($cliente->getMotivoIng());
            $historial->setPatologiaEspecifica($cliente->getMotivoIngEspecifico());
            $historial->setFecha(new \DateTime());
            $usuario = $user->getEmail() ?? $user->getUsername() ?? 'no user';
            $historial->setUsuario($usuario);
            $historial->setFechaIngreso($cliente->getFIngreso());
            $historial->setFechaEngreso($cliente->getFEgreso());


            $entityManager->persist($historial);
            $entityManager->persist($cliente);

            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/new.html.twig', [
            'cliente' => $cliente,
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
    public function historia(Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository, NotasTurnoRepository $notasTurnoRepository, BookingRepository $bookingRepository): Response
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

        return $this->render('cliente/historia.html.twig', [
                'cliente' => $cliente,
                'historiaPaciente' => $historiaPaciente,
                'obraSociales' => $obraSocialesArray,
                'paginaImprimible' => true,
                'notasTurnos' => $notasTurnos,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="cliente_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Cliente $cliente, ObraSocialRepository $obraSocialRepository, FamiliarExtraRepository $familiarExtraRepository, HabitacionRepository $habitacionRepository): Response
    {
        $habitacionesDisp = $habitacionRepository->findHabitacionConCamasDisponibles();
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

        $haArray = array_flip($haArray);
        ksort($camasDispArray);

        $habPrivada = $cliente->getHabPrivada() ?? false;
        $puedePasarHabPrivada = $habPrivada;
        if(!empty($habitacionActual)) {
            if(count($habitacionActual->getCamasOcupadas()) == 1) {
                $puedePasarHabPrivada = true;
            }
        }

        $form = $this->createForm(ClienteType::class, $cliente, ['allow_extra_fields'=>true, 'is_new' => false, 'obrasSociales' => $obArray, 'habitaciones' => $haArray, 'camasDisp' => $camasDispArray, 'bloquearHab' => $puedePasarHabPrivada]);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager = $this->getDoctrine()->getManager();
                $doctoresReferentes = $cliente->getDocReferente();

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

                foreach ($doctoresReferentes as $doctor) {
                    $doctor->addCliente($cliente);
                    $entityManager->persist($doctor);
                }

                $habPrivadaNueva = $form->getExtraData()['habPrivada'] ?? $cliente->getHabPrivada() ?? 0;

                $cliente->setHabPrivada($habPrivadaNueva);

                $entityManager->persist($cliente);

                $nuevaHabId = $cliente->getHabitacion() ?? 0;

                if($cliente->getNCama()) {
                    $nuevaCamaId = $cliente->getNCama();
                } else {
                    $nuevaCamaId = $form->getExtraData()['nCama'] ?? 0;
                    $cliente->setNCama($nuevaCamaId);
                    $entityManager->persist($cliente);
                }


                $habitacionNueva = $habitacionRepository->find($nuevaHabId);
                $habVieja = $habitacionRepository->find($habitacionActualId);


                $this->acomodarHabitacion($habitacionNueva, $nuevaCamaId, $habVieja, $camaActualId, $habPrivada, $habPrivadaNueva, $entityManager);


                $historial = new HistoriaPaciente();
                $historial->setIdPaciente($cliente->getId());
                $historial->setCama($cliente->getNCama());
                $historial->setHabitacion($cliente->getHabitacion());
                $historial->setNAfiliadoObraSocial($cliente->getObraSocialAfiliado());
                $historial->setObraSocial($cliente->getObraSocial());
                $historial->setNAfiliadoSistemaDeEmergencia($cliente->getSistemaDeEmergenciaAfiliado());
                $historial->setSistemaDeEmergencia($cliente->getSistemaDeEmergenciaNombre());
                $historial->setModalidad($cliente->getModalidad());
                $historial->setPatologia($cliente->getMotivoIng());
                $historial->setPatologiaEspecifica($cliente->getMotivoIngEspecifico());
                $historial->setFecha(new \DateTime());
                $user = $this->security->getUser();
                $usuario = $user->getEmail() ?? $user->getUsername() ?? 'no user';
                $historial->setUsuario($usuario);

                $historial->setCliente($cliente);
                //$cliente->setHistoria($historial);

                $entityManager->persist($historial);

                $entityManager->flush();

                return $this->redirectToRoute('cliente_index');
            }catch (\Exception $e) {
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
     * @Route("/{id}/egreso", name="cliente_egreso", methods={"GET","POST"})
     */
    public function egreso(Request $request, Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $form = $this->createForm(ClienteType::class, $cliente, ['egreso' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $doctoresReferentes = $cliente->getDocReferente();

            foreach ($doctoresReferentes as $doctor) {
                $doctor->addCliente($cliente);
                $entityManager->persist($doctor);
            }
            $fechaDeEgresoString = $cliente->getFEgreso()->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            $turnos = $bookingRepository->turnosConFiltro('', $cliente, $fechaDeEgresoString);

            foreach ($turnos as $turno) {
                $entityManager->remove($turno);
            }

            $entityManager->persist($cliente);
            $historial = $historiaPacienteRepository->findOneBy(['id_paciente' => $cliente->getId()]);
            $historial->setFechaEngreso($cliente->getFEgreso());

            if($cliente->getFEgreso() <= new \DateTime()) {
                $this->liberarCamaCliente($cliente);
            }

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
    public function reingreso(Request $request, Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, HabitacionRepository $habitacionRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->security->getUser();
        $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles();

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

        return $this->redirectToRoute('cliente_index');
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
}
