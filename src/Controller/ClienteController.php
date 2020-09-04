<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\FamiliarExtra;
use App\Form\ClienteType;
use App\Repository\AdjuntosPacientesRepository;
use App\Repository\ClienteRepository;
use App\Repository\FamiliarExtraRepository;
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

/**
 * @Route("/pacientes")
 */
class ClienteController extends AbstractController
{
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
        $cliente = new Cliente();
        $cliente->setActivo(true);
        $cliente->setFIngreso(new \DateTime());

        //TODO
        /*$obrasSociales = $obraSocialRepository->findAll();
        $obArray = [];
        foreach ( $obrasSociales as $ob ) {
            $obArray[$ob->getId()] = $ob->getNombre();
        }
        $obArray = array_flip($obArray);*/

        $obArray = [
            'Ospac' => 1,
            'Esencial' => 2,
            'Prevencion art' => 3,
            'Prevencion salud' => 4,
            'Pami' => 5,
            'Sancor seguros' => 6,
            'Sancor salud' => 7,
            'Sancor art' => 8,
            'Osde' => 9,
            'Iapos' => 10,
            'Ipam salud' => 11,
            'Amur' => 12,
            'Amr' => 13,
            'Ospat' => 14,
            'Osap' => 15,
            'Italmedic' => 16,
            'Plenit' => 17,
            'Ospif' => 18,
            'economicas' => 19,
            'Osecac' => 20,
            'Osseg' => 21,
            'Osprera' => 22,
            'Osprera/mutual abril' => 23,
            'Salud del nuevo rosario' => 24,
            'Salud rosario' => 25,
            'Medife' => 26,
            'Smai' => 27,
            'Dasuten' => 28,
            'Osdop' => 29,
            'Osfgpicyd (obra social de la carne)' => 30,
            'Delta salud' => 31,
            'Provincia art' => 32,
            'Osfatlyf (Sindicato Luz y Fuerza)' => 33,
            'Osammuc' => 34,
            'Britanica salud' => 35,
            'Andar' => 36,
            'Union personal art' => 37,
            'Union personal'  => 38,
            'Aca salud'  => 39,
            'Iosfa' => 40,
            'Simara' => 41,
            'Amparas' => 42,
            'Unr' => 43,
            'Ima' => 44,
            'Osmata' => 45,
            'Elevar' => 46,
            'Federación Médica' => 47,
            'Pasteleros' => 48,
            'Camioneros primera' => 49,
            'Mutual luz y fuerza' => 50,
            'Medicus' => 51,
            'Particular' => 52
        ];

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
     * @Route("/{id}/edit", name="cliente_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Cliente $cliente): Response
    {
        $form = $this->createForm(ClienteType::class, $cliente, ['is_new' => false]);

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
            'title' => 'Editar Paciente: ' . $cliente->getNombre() . ' ' . $cliente->getApellido(),
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
