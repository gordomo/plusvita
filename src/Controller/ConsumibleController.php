<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Consumible;
use App\Entity\ConsumiblesClientes;
use App\Form\ConsumibleType;
use App\Repository\ClienteRepository;
use App\Repository\ConsumibleRepository;
use App\Repository\ConsumiblesClientesRepository;
use App\Repository\TipoConsumibleRepository;
use App\Repository\UserRepository;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/consumible")
 */
class ConsumibleController extends AbstractController
{
    /**
     * @Route("/", name="consumible_index", methods={"GET"})
     */
    public function index(Request $request, ConsumibleRepository $consumibleRepository, TipoConsumibleRepository $tipoConsumibleRepository): Response
    {
        $tipoSeleccionado = $request->query->get('tipoSeleccionado', 0);
        if ($tipoSeleccionado) {
            $consumibles = $consumibleRepository->findByTipo($tipoSeleccionado);
        } else {
            $consumibles = $consumibleRepository->findBy([], ['tipo'=>'DESC']);
        }


        return $this->render('consumible/index.html.twig', [
            'consumibles' => $consumibles,
            'tipoSeleccionado' => $tipoSeleccionado,
            'tipos' => $tipoConsumibleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="consumible_new", methods={"GET","POST"})
     */
    public function new(Request $request, TipoConsumibleRepository $tipoConsumibleRepository): Response
    {
        $consumible = new Consumible();
        $form = $this->createForm(ConsumibleType::class, $consumible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($consumible);
            $entityManager->flush();

            return $this->redirectToRoute('consumible_index');
        }

        return $this->render('consumible/new.html.twig', [
            'consumible' => $consumible,
            'tipos' => $tipoConsumibleRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="consumible_show", methods={"GET"})
     */
    public function show(Consumible $consumible, ConsumiblesClientesRepository $consumiblesClientesRepository, ClienteRepository $clienteRepository, UserRepository $userRepository): Response
    {
        $historicoConsumible = $consumiblesClientesRepository->findBy(['consumibleId' => $consumible->getId()]);
        $clientes = $clienteRepository->findAllActivos(new \DateTime());
        $clientesArray = [];
        foreach ( $clientes as $cliente) {
            $clientesArray[$cliente->getId()] = $cliente->getNombre() . ' ' . $cliente->getApellido();
        };

        $usuarios = $userRepository->findAll();

        foreach ($usuarios as $user) {
            $clientesArray[$user->getId()] = $user->getEmail();
        }

        return $this->render('consumible/show.html.twig', [
            'consumible' => $consumible,
            'historicoConsumible' => $historicoConsumible,
            'pacientes' => $clientesArray
        ]);
    }

    /**
     * @Route("/{id}/edit", name="consumible_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Consumible $consumible): Response
    {
        $form = $this->createForm(ConsumibleType::class, $consumible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('consumible_index');
        }

        return $this->render('consumible/edit.html.twig', [
            'consumible' => $consumible,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/addView", name="consumible_add_view", methods={"GET"})
     */
    public function addView(Request $request, Consumible $consumible, ClienteRepository $clienteRepository): Response
    {
        $clientes = $clienteRepository->findAllActivos(new \DateTime());
        return $this->render('consumible/add.html.twig', [
            'consumible' => $consumible,
            'clientes' => $clientes,
        ]);
    }

    /**
     * @Route("/{id}/add", name="consumible_add", methods={"GET"})
     */
    public function add(Request $request, Consumible $consumible): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $agregar = $request->query->get('agregarQuitar') == 'agregar' ? 1 : 0;

        $cantidad = $request->query->get('cantidad') ?? 0;
        $aNombreDe = $request->query->get('aNombreDe') ?? 0;
        $cliente = $request->query->get('cliente') ?? $user->getId();

        $entityManager = $this->getDoctrine()->getManager();

        $historialConsumible = new ConsumiblesClientes();
        $historialConsumible->setClienteId($cliente);
        $historialConsumible->setAccion($agregar);
        $historialConsumible->setCantidad($cantidad);
        $historialConsumible->setConsumibleId($consumible->getId());
        $historialConsumible->setFecha(new \DateTime);


        $nuevoTotal = $consumible->getExistencia();

        if ($agregar) {
            $nuevoTotal += $cantidad;
        } else {
            $nuevoTotal -= $cantidad;
        }

        $consumible->setExistencia($nuevoTotal);

        $entityManager->persist($historialConsumible);
        $entityManager->persist($consumible);
        $entityManager->flush();

        return $this->redirectToRoute('consumible_index');
    }

    /**
     * @Route("/{id}", name="consumible_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Consumible $consumible): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consumible->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($consumible);
            $entityManager->flush();
        }

        return $this->redirectToRoute('consumible_index');
    }

    /**
     * @Route("/imputar-view/{id}", name="consumible_imputar_view", methods={"GET"})
     */
    public function imputarView(Cliente $cliente, ConsumibleRepository $consumibleRepository): Response
    {
        $consumibles = $consumibleRepository->findAll();

        return $this->render('consumible/imputar.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibles
        ]);
    }
    /**
     * @Route("/check/existencias/", name="consumible_check_existencias", methods={"GET"})
     */
    public function checkExistencias(Request $request, ConsumibleRepository $consumibleRepository): Response
    {
        $id = $request->query->get('id');
        $existencia = $consumibleRepository->find($id)->getExistencia();
        return new JsonResponse(['existencia' => $existencia]);
    }


    /**
     * @Route("/imputar", name="consumible_imputar", methods={"POST"})
     */
    public function imputar(Request $request, ConsumibleRepository $consumibleRepository): Response
    {
        $clienteId = $request->get('cliente');
        $consumibleId = $request->get('consumible');
        $cantidad = $request->get('cantidad');
        $accion = ($request->get('accion', 0) !== "0") ? 1 : 0;
        $desde = new \DateTime($request->get('desde', ''), new DateTimeZone('America/Argentina/Buenos_Aires'));
        $hasta = new \DateTime($request->get('hasta', ''), new DateTimeZone('America/Argentina/Buenos_Aires'));

        $consumible = $consumibleRepository->find($consumibleId);

        $existenciaActual = $consumible->getExistencia();

        /*if ($cantidad <= $existenciaActual) {
            $consumible->setExistencia($existenciaActual - $cantidad);
        }*/

        $consumiblesClientesHistorico = new ConsumiblesClientes();
        $consumiblesClientesHistorico->setFecha(new \DateTime);
        $consumiblesClientesHistorico->setDesde($desde);
        $consumiblesClientesHistorico->setHasta($hasta);
        $consumiblesClientesHistorico->setAccion($accion);
        $consumiblesClientesHistorico->setCantidad($cantidad);
        $consumiblesClientesHistorico->setClienteId($clienteId);
        $consumiblesClientesHistorico->setConsumibleId($consumibleId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($consumiblesClientesHistorico);
        $entityManager->persist($consumible);
        $entityManager->flush();

        return $this->redirectToRoute('consumible_historico', ['id' => $clienteId]);

    }

    /**
     * @Route("/historico/{id}", name="consumible_historico", methods={"GET"})
     */
    public function historico($id,
                              ClienteRepository $clienteRepository,
                              ConsumibleRepository $consumibleRepository,
                              ConsumiblesClientesRepository $consumiblesClientesRepository,
                              TipoConsumibleRepository $tipoConsumibleRepository,
                              Request $request): Response
    {
        $pestana = $request->query->get('pestana') ?? 'todos';
        $cliente = $clienteRepository->find($id);

        $tipoSeleccionado = $request->query->get('tipoSeleccionado', 0);
        if ($tipoSeleccionado) {
            $consumibles = $consumibleRepository->findByTipo($tipoSeleccionado);
        } else {
            $consumibles = $consumibleRepository->findBy([], ['tipo'=>'DESC']);
        }

        $consumibleArray = [];
        foreach ($consumibles as $consumible) {
            $consumibleArray[$consumible->getId()] = $consumible;
        }
        $accion = ($pestana === 'todos') ? null : (($pestana === 'ingresos') ? 1 : 0);
        $desde = $request->query->get('desde', 0);
        $hasta = $request->query->get('hasta', 0);

        $consumiblesClientes = $consumiblesClientesRepository->findByAccionAndClientId($id, $desde, $hasta, $accion);


        return $this->render('consumible/historico.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibleArray,
            'consumiblesClientes' => $consumiblesClientes,
            'pestana' => $pestana,
            'tipos' => $tipoConsumibleRepository->findAll(),
            'tipoSeleccionado' => $tipoSeleccionado,
            'hasta' => $hasta,
            'desde' => $desde,

        ]);

    }
}
