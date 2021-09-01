<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Consumible;
use App\Entity\ConsumiblesClientes;
use App\Entity\Recibo;
use App\Form\ConsumibleType;
use App\Controller\ExportToExcel;
use App\Repository\ClienteRepository;
use App\Repository\ConsumibleRepository;
use App\Repository\ConsumiblesClientesRepository;
use App\Repository\ReciboRepository;
use App\Repository\TipoConsumibleRepository;
use App\Repository\UserRepository;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

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
    public function addView(Request $request, Consumible $consumible, ClienteRepository $clienteRepository, TipoConsumibleRepository $tipoConsumibleRepository): Response
    {
        $clientes = $clienteRepository->findAllActivos(new \DateTime());
        return $this->render('consumible/add.html.twig', [
            'consumible' => $consumible,
            'clientes' => $clientes,
            'tipoConsumibles' => $tipoConsumibleRepository->findAll(),
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
    public function imputarView(Request $request, Cliente $cliente, ConsumibleRepository $consumibleRepository, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $consumibles = $consumibleRepository->findBy([], ['nombre' => 'ASC']);
        $indicacionesCargadas = $consumiblesClientesRepository->findIndicacionesParaElCliente($cliente->getId());

        $now = new \DateTime();
        $mes = $now->modify("+1 month")->format('m');

        return $this->render('consumible/imputar.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibles,
            'indicacionesCargadas' => $indicacionesCargadas,
            'mes' => $mes,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ]
        ]);
    }

    /**
     * @Route("/imputar-view/acciones/get-imputaciones/", name="consumible_imputar_view_get_imputaciones", methods={"GET"})
     */
    public function imputarViewGetImputaciones(Request $request, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $mes = $request->query->get('mes');
        $consumibleId = $request->query->get('consumibleId');
        $cid = $request->query->get('cid');

        $indicacionesCargadas = $consumiblesClientesRepository->findImputacionesMesConsumibleCliente($mes, $consumibleId, $cid);

        $cant = 0;
        foreach ($indicacionesCargadas as $indicacion) {
            $cant += $indicacion->getCantidad();
        }
        return new JsonResponse($cant);
    }

    /**
     * @Route("/imputar-view/acciones/get-imputaciones/imprimir", name="consumible_imputar_view_get_imputaciones_imprimir", methods={"GET"})
     */
    public function imputarViewGetImputacionesParaImprimir(Request $request, ConsumiblesClientesRepository $consumiblesClientesRepository, ConsumibleRepository $consumibleRepository): Response
    {
        $fecha = $request->query->get('fecha');
        $cid = $request->query->get('cid');

        $indicacionesCargadas = $consumiblesClientesRepository->findImputacionesFechaConsumible($fecha, $cid);
        $indicacionesArray = [];

            foreach ($indicacionesCargadas as $indicacion) {
                if (isset($indicacionesArray[$indicacion->getConsumibleId()])) {
                    $indicacionesArray[$indicacion->getConsumibleId()]['cant'] += $indicacion->getCantidad();
                } else {
                    $indicacionesArray[$indicacion->getConsumibleId()] = ['id' => $indicacion->getId(), 'nombre' => $consumibleRepository->find($indicacion->getConsumibleId())->getNombre(), 'cant' => $indicacion->getCantidad()];
                }
            }
            return new JsonResponse($indicacionesArray);

    }

    /**
     * @Route("/indicar_view/{id}", name="consumible_indicar_view", methods={"GET"})
     */
    public function indicarView(Cliente $cliente, ConsumibleRepository $consumibleRepository, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $consumibles = $consumibleRepository->findBy([], ['nombre' => 'ASC']);
        $indicacionesCargadas = $consumiblesClientesRepository->findConsumibleMesAnteriorParaElCliente($cliente->getId(), 0);
        $now = new \DateTime();
        $mes = $now->format('m');

        return $this->render('consumible/indicar.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibles,
            'mes' => $mes,
            'indicacionesCargadas' => $indicacionesCargadas,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ]
        ]);
    }

    /**
     * @Route("/imputar_view/guardar_recibo", name="guardar_recibo", methods={"GET"})
     */
    public function guardarRecibo(Request $request, ReciboRepository $reciboRepository): Response
    {
        try {
            $clienteId = $request->get('cid');
            $fecha = new \DateTime($request->get('fecha'));
            $tipo = $request->get('tipo');
            $html = $request->get('html');
            $html = (preg_replace('/\v(?:[\v\h]+)/', '', $html));


            $count = $reciboRepository->getCountByType($tipo);
            $count ++;

            $html = str_replace("###numero###", $count, $html);

            $recibo = new Recibo();
            $recibo->setTipo($tipo);
            $recibo->setCid($clienteId);
            $recibo->setFecha($fecha);
            $recibo->setHtml($html);
            $recibo->setCount($count);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($recibo);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }

        return new JsonResponse($html);
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
     * @Route("/acciones/imputar", name="consumible_imputar", methods={"GET"})
     */
    public function imputar(Request $request, ConsumibleRepository $consumibleRepository): Response
    {
        $clienteId = $request->get('cliente');
        $consumibleId = $request->get('consumibleId');
        $cantidad = $request->get('cantidad');
        $accion = $request->get('accion');
        $mes = $request->get('mes', '');
        $isAjax = $request->get('isAjax', false);
        $error = false;
        $message = 'ok';

        if ($mes == '') {
            $now = new \DateTime();
        }

        try {
            $consumible = $consumibleRepository->find($consumibleId);

            //$existenciaActual = $consumible->getExistencia();
            /*if ($cantidad <= $existenciaActual) {
                $consumible->setExistencia($existenciaActual - $cantidad);
            }*/

            $consumiblesClientesHistorico = new ConsumiblesClientes();
            $consumiblesClientesHistorico->setFecha(new \DateTime);
            $consumiblesClientesHistorico->setMes($mes);
            $consumiblesClientesHistorico->setAccion($accion);
            $consumiblesClientesHistorico->setCantidad($cantidad);
            $consumiblesClientesHistorico->setClienteId($clienteId);
            $consumiblesClientesHistorico->setConsumibleId($consumibleId);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($consumiblesClientesHistorico);
            $entityManager->persist($consumible);
            $entityManager->flush();

        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }
        if($isAjax) {
            return new JsonResponse(['error' => $error, 'message' => $message]);
        } else {
            return $this->redirectToRoute('consumible_historico', ['id' => $clienteId]);
        }
    }

    /**
     * @Route("/acciones/update/consumible-cliente", name="update_consumible_cliente", methods={"GET"})
     */
    public function updateConsumibleCliente(Request $request, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $fecha = new \DateTime($request->get('fecha'));
        $consumibleId = $request->get('cid');
        $consumible = $request->get('consumible');
        $cantidad = $request->get('cantidad');
        $accion = $request->get('accion');
        $mes = $request->get('mes', '');

        $error = false;
        $message = 'ok';


        try {
            $consumiblesClientesHistorico = $consumiblesClientesRepository->find($consumibleId);

            //$existenciaActual = $consumible->getExistencia();
            /*if ($cantidad <= $existenciaActual) {
                $consumible->setExistencia($existenciaActual - $cantidad);
            }*/

            $consumiblesClientesHistorico->setFecha($fecha);
            $consumiblesClientesHistorico->setMes($mes);
            $consumiblesClientesHistorico->setAccion($accion);
            $consumiblesClientesHistorico->setCantidad($cantidad);
            $consumiblesClientesHistorico->setConsumibleId($consumible);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($consumiblesClientesHistorico);
            $entityManager->flush();

        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }

        return new JsonResponse(['error' => $error, 'message' => $message]);

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
        $accion = ($pestana === 'todos') ? null : (($pestana === 'imputaciones') ? 1 : 0);

        $mes = $request->get('mes', '');
        if ($mes == '') {
            $now = new \DateTime();
            $mes = $now->modify("+1 month")->format('m');
        }

        $fecha = $request->query->get('imputacion', '');

        $consumiblesClientes = $consumiblesClientesRepository->findByAccionAndClientId($id, $mes, $fecha, $accion);

        return $this->render('consumible/historico.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibleArray,
            'consumiblesClientes' => $consumiblesClientes,
            'pestana' => $pestana,
            'tipos' => $tipoConsumibleRepository->findAll(),
            'tipoSeleccionado' => $tipoSeleccionado,
            'mes' => $mes,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ],
            'paginaImprimible' => true,
            'imputacion' => $fecha,
        ]);

    }

    /**
     * @Route("/borrar/{id}", name="borrar_consumible", methods={"GET"})
     */
    public function borrarConsumible(ConsumiblesClientes $consumibleCliente, Request $request): Response
    {
        $clientId = $consumibleCliente->getClienteId();

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($consumibleCliente);
        $entityManager->flush();

        return $this->redirectToRoute('consumible_historico', ['id' => $clientId, 'mes' => $request->get('mes')]);

    }
    /**
     * @Route("/recibos/{id}", name="consumibles_recibos", methods={"GET"})
     */
    public function verRecibos($id, ReciboRepository $reciboRepository, ClienteRepository $clienteRepository): Response
    {
        $cliente = $clienteRepository->find($id);
        $recibos = $reciboRepository->findReciboImputacionCliente($id);

        return $this->render('consumible/recibos.html.twig', [
            'recibos' => $recibos,
            'cliente' => $cliente
        ]);

    }

    /**
     * @Route("/{path}/excel", name="consumible_to_excel", methods={"POST"})
     */

    public function toExcel(Request $request, RouterInterface $router) {
        return ExportToExcel::toExcel($request->get('html'), $router, 'consumible_historico.xlsx');
    }
}
