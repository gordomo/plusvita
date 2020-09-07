<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\FamiliarExtra;
use App\Entity\HistoriaPaciente;
use App\Form\ClienteType;
use App\Repository\AdjuntosPacientesRepository;
use App\Repository\ClienteRepository;
use App\Repository\FamiliarExtraRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\ObraSocialRepository;
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
    public function index(Request $request, ClienteRepository $clienteRepository): Response
    {
        $inactivos = $request->query->get('inactivos');
        $nombreInput = $request->query->get('nombreInput');


        if ( $inactivos ) {
            $clientes = $clienteRepository->findInActivos(new \DateTime(), $nombreInput);
        } else {
            $clientes = $clienteRepository->findActivos(new \DateTime(), $nombreInput);
        }

        //dd($clientes);
        return $this->render('cliente/index.html.twig', [
            'clientes' => $clientes,
            'inactivos' => $inactivos,
            'nombreInput' => $nombreInput,
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
    public function new(Request $request, ObraSocialRepository $obraSocialRepository): Response
    {
        $user = $this->security->getUser();

        $cliente = new Cliente();
        $historial = new HistoriaPaciente();

        $cliente->setActivo(true);
        $cliente->setFIngreso(new \DateTime());

        $obrasSociales = $obraSocialRepository->findAll();
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }
        $obArray = array_flip($obArray);

        $form = $this->createForm(ClienteType::class, $cliente, ['is_new' => true, 'obrasSociales' => $obArray]);
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

            $historial = new HistoriaPaciente();
            $historial->setIdPaciente($cliente->getId());
            $historial->setCama($cliente->getNCama());
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

            $entityManager->persist($historial);

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
    public function edit(Request $request, Cliente $cliente, ObraSocialRepository $obraSocialRepository, FamiliarExtraRepository $familiarExtraRepository): Response
    {
        $obrasSociales = $obraSocialRepository->findAll();
        $familiarExtraActuales = $familiarExtraRepository->findBy(['cliente_id' => $cliente->getId()]);

        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }
        $obArray = array_flip($obArray);

        $form = $this->createForm(ClienteType::class, $cliente, ['is_new' => false, 'obrasSociales' => $obArray]);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $entityManager->persist($cliente);

            $historial = new HistoriaPaciente();
            $historial->setIdPaciente($cliente->getId());
            $historial->setCama($cliente->getNCama());
            $historial->setCama($cliente->getNCama());
            $historial->setHabitacion($cliente->getHabitacion());
            $historial->setNAfiliadoObraSocial($cliente->getObraSocialAfiliado());
            $historial->setObraSocial($cliente->getObraSocial());
            $historial->setModalidad($cliente->getModalidad());
            $historial->setPatologia($cliente->getMotivoIng());
            $historial->setPatologiaEspecifica($cliente->getMotivoIngEspecifico());
            $historial->setFecha(new \DateTime());
            $user = $this->security->getUser();
            $usuario = $user->getEmail() ?? $user->getUsername() ?? 'no user';
            $historial->setUsuario($usuario);

            $entityManager->persist($historial);

            $entityManager->flush();

            return $this->redirectToRoute('cliente_index');
        }
        //dd($familiarExtraActuales);
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
    public function egreso(Request $request, Cliente $cliente): Response
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
}
