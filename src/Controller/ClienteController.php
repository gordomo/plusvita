<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\FamiliarExtra;
use App\Entity\Habitacion;
use App\Entity\HistoriaPaciente;
use App\Form\ClienteType;
use App\Repository\AdjuntosPacientesRepository;
use App\Repository\CamaRepository;
use App\Repository\ClienteRepository;
use App\Repository\FamiliarExtraRepository;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\ObraSocialRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $inactivos = $request->query->get('inactivos');
        $nombreInput = $request->query->get('nombreInput');


        if ( $inactivos ) {
            $clientes = $clienteRepository->findInActivos(new \DateTime(), $nombreInput);
        } else {
            $clientes = $clienteRepository->findActivos(new \DateTime(), $nombreInput);
        }

        $habitaciones = $habitacionRepository->findAll();

        $habitacionesArray = [];
        foreach ($habitaciones as $habitacion) {
            $habitacionesArray[$habitacion->getId()] = $habitacion->getNombre();
        }

        return $this->render('cliente/index.html.twig', [
            'clientes' => $clientes,
            'inactivos' => $inactivos,
            'nombreInput' => $nombreInput,
            'habitacionesArray'=>$habitacionesArray,
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
    public function new(Request $request, ObraSocialRepository $obraSocialRepository, HabitacionRepository $habitacionRepository): Response
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

        $form = $this->createForm(ClienteType::class, $cliente, ['allow_extra_fields' =>true, 'is_new' => true, 'obrasSociales' => $obArray, 'habitaciones' => $haArray]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $familiarResponsableExtraNombres = $request->request->get('familiarResponsableExtraNombre');
            $familiarResponsableExtraTel = $request->request->get('familiarResponsableExtraTel');
            $familiarResponsableExtraMail = $request->request->get('familiarResponsableExtraMail');
            $familiarResponsableExtraVinculo = $request->request->get('familiarResponsableExtraVinculo');

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

                $familarRespExtra = new FamiliarExtra();
                $familarRespExtra->setNombre($item);
                $familarRespExtra->setTel($tel);
                $familarRespExtra->setMail($mail);
                $familarRespExtra->setVinculo($vinculo);
                $familarRespExtra->setClienteId($cliente->getId());

                $entityManager->persist($familarRespExtra);
            };


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
            $entityManager->persist($habitacion);
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
    public function historia(Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository): Response
    {
        $historiaPaciente = $historiaPacienteRepository->findBy(['id_paciente' => $cliente->getId()]);
        $obrasSociales = $obraSocialRepository->findAll();
        $obraSocialesArray = [];
        foreach ($obrasSociales as $obraSocial) {
            $obraSocialesArray[$obraSocial->getId()] = $obraSocial->getNombre();
        }
        return $this->render('cliente/historia.html.twig', [
                'cliente' => $cliente,
                'historiaPaciente' => $historiaPaciente,
                'obraSociales' => $obraSocialesArray
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
        $habitacionActualId = $cliente->getHabitacion();
        $camaActualId = $cliente->getNCama();

        if(!empty($habitacionActualId)) {
            $habitacionActual = $habitacionRepository->find($habitacionActualId);
            if(!empty($habitacionActual)) {
                if(empty($haArray[$habitacionActualId])) {
                    $haArray[$habitacionActualId] = !empty($habitacionActual) ? $habitacionActual->getNombre() : 'Habitación sin nombre';
                }

                $camasOcupadas = $habitacionActual->getCamasOcupadas();
                $cantCamas = $habitacionActual->getCamasDisponibles();
                $camasDispArray = [];
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

                foreach ($familiarExtraActuales as $familiarExtraActual) {
                    $entityManager->remove($familiarExtraActual);
                }

                $familiarResponsableExtraNombres = $familiarResponsableExtraNombres ?? [];
                foreach ($familiarResponsableExtraNombres as $key => $item) {
                    $tel = $familiarResponsableExtraTel[$key] ?? '';
                    $mail = $familiarResponsableExtraMail[$key] ?? '';
                    $vinculo = $familiarResponsableExtraVinculo[$key] ?? '';

                    $familarRespExtra = new FamiliarExtra();
                    $familarRespExtra->setNombre($item);
                    $familarRespExtra->setTel($tel);
                    $familarRespExtra->setMail($mail);
                    $familarRespExtra->setVinculo($vinculo);
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

                $nuevaHabId = $cliente->getHabitacion();
                $nuevaCamaId = $cliente->getNCama();

                $habitacionNueva = $habitacionRepository->find($nuevaHabId);
                $habVieja = $habitacionRepository->find($habitacionActualId);


                $this->acomodarHabitacion($habitacionNueva, $nuevaCamaId, $habVieja, $camaActualId, $habPrivada, $habPrivadaNueva, $entityManager);


                $historial = new HistoriaPaciente();
                $historial->setIdPaciente($cliente->getId());
                $historial->setCama($cliente->getNCama());
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
    public function egreso(Request $request, Cliente $cliente, HistoriaPacienteRepository $historiaPacienteRepository, HabitacionRepository $habitacionRepository): Response
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

            $entityManager->persist($cliente);
            $historial = $historiaPacienteRepository->findOneBy(['id_paciente' => $cliente->getId()]);
            $historial->setFechaEngreso($cliente->getFEgreso());

            if($cliente->getFEgreso() <= new \DateTime()) {
                $habitacionActual = $habitacionRepository->find($cliente->getHabitacion());

                $habViejaCamasOcupadas = $habitacionActual->getCamasOcupadas();
                unset($habViejaCamasOcupadas[$cliente->getNCama()]);
                $habitacionActual->setCamasOcupadas($habViejaCamasOcupadas);

                $cliente->setHabitacion(null);
                $cliente->setNCama(null);

                $entityManager->persist($habitacionActual);
                $entityManager->persist($cliente);
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
     * @Route("/{id}", name="cliente_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cliente $cliente): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cliente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($cliente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('cliente_index');
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
}
