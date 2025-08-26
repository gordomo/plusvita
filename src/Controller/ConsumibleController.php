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
use App\Service\HorarioTomaCalculatorService;

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

        // Siempre será agregar ya que eliminamos la opción de quitar
        $agregar = 1;

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
        
        // Siempre agregamos ya que eliminamos la opción de quitar
        $nuevoTotal += $cantidad;

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

        $year = $request->get('year', '');
        $mes = $request->get('mes', '');
        $indicacionesCargadas = $consumiblesClientesRepository->findIndicacionesParaElCliente($cliente->getId(), $year, $mes, null, false);

        // Verificar si el usuario es un doctor
        $user = $this->getUser();
        $isDoctor = false;
        if ($user && $this->isGranted('ROLE_DOCTOR')) {
            $isDoctor = true;
        } elseif ($user && $this->isGranted('ROLE_STAFF') && $user->getDoctor() !== null) {
            $isDoctor = true;
        }
        
        return $this->render('consumible/imputar.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibles,
            'indicacionesCargadas' => $indicacionesCargadas,
            'mes' => $mes,
            'year' => $year,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ],
            'isDoctor' => $isDoctor
        ]);
    }

    /**
     * @Route("/imputar-view/acciones/get-imputaciones/", name="consumible_imputar_view_get_imputaciones", methods={"GET"})
     */
    public function imputarViewGetImputaciones(Request $request, ConsumiblesClientesRepository $consumiblesClientesRepository): Response
    {
        $mes = $request->query->get('mes');
        $year = $request->query->get('year', '');
        $consumibleId = $request->query->get('consumibleId');
        $cid = $request->query->get('cid');

        $indicacionesCargadas = $consumiblesClientesRepository->findImputacionesMesConsumibleCliente($mes, $consumibleId, $cid, $year);

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
        
        // Crear array asociativo para acceso más eficiente en el template
        $consumiblesArray = [];
        foreach ($consumibles as $consumible) {
            $consumiblesArray[$consumible->getId()] = $consumible->getNombre();
        }
        
        // Obtener indicaciones por estado
        $indicacionesActivas = $consumiblesClientesRepository->findActiveIndicationsForClient($cliente->getId());
        $indicacionesProximasVencer = $consumiblesClientesRepository->findExpiringIndicationsForClient($cliente->getId());
        $indicacionesHistoricas = $consumiblesClientesRepository->findHistoricalIndicationsForClient($cliente->getId());
        
        $now = new \DateTime();
        $mes = $now->format('m');

        // Verificar si el usuario es un doctor
        $user = $this->getUser();
        $isDoctor = false;
        if ($user && $this->isGranted('ROLE_DOCTOR')) {
            $isDoctor = true;
        } elseif ($user && $this->isGranted('ROLE_STAFF') && $user->getDoctor() !== null) {
            $isDoctor = true;
        }
       
        return $this->render('consumible/indicar.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibles,
            'consumiblesArray' => $consumiblesArray,
            'mes' => $mes,
            'indicacionesActivas' => $indicacionesActivas,
            'indicacionesProximasVencer' => $indicacionesProximasVencer, 
            'indicacionesHistoricas' => $indicacionesHistoricas,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ],
            'isDoctor' => $isDoctor
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
     * @Route("/extender-indicacion", name="consumible_extender_indicacion", methods={"POST"})
     */
    public function extenderIndicacion(Request $request, EntityManagerInterface $entityManager)
    {
        $indicacionId = $request->request->get('indicacionId');
        $nuevaFechaFin = $request->request->get('nuevaFechaFin');
        
        if (!$indicacionId || !$nuevaFechaFin) {
            return $this->json(['success' => false, 'message' => 'Parámetros incompletos']);
        }
        
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($indicacionId);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            $fechaFinObj = new \DateTime($nuevaFechaFin);
            $indicacion->setFechaFin($fechaFinObj);
            
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/reactivar-indicacion", name="consumible_reactivar_indicacion", methods={"POST"})
     */
    public function reactivarIndicacion(Request $request, EntityManagerInterface $entityManager)
    {
        $indicacionId = $request->request->get('indicacionId');
        $fechaInicio = $request->request->get('fechaInicio');
        $fechaFin = $request->request->get('fechaFin');
        
        if (!$indicacionId || !$fechaInicio || !$fechaFin) {
            return $this->json(['success' => false, 'message' => 'Parámetros incompletos']);
        }
        
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($indicacionId);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            $fechaInicioObj = new \DateTime($fechaInicio);
            $fechaFinObj = new \DateTime($fechaFin);
            
            $indicacion->setFechaInicio($fechaInicioObj);
            $indicacion->setFechaFin($fechaFinObj);
            $indicacion->setActivo(true);
            
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/acciones/imputar", name="consumible_imputar", methods={"GET"})
     */
    public function imputar(Request $request, ConsumibleRepository $consumibleRepository): Response
    {
        $clienteId = $request->get('cliente');
        $consumibleId = $request->get('consumibleId');
        $cantidad = $request->get('cantidad', 1); // Default to 1 if not provided
        $unidadMedida = $request->get('unidadMedida', 'unidades'); // Unidad de medida
        $accion = $request->get('accion');
        $mes = $request->get('mes', '');
        $year = $request->get('year', '');
        $isAjax = $request->get('isAjax', false);
        $activo = $request->get('activo', true);
        
        // Nuevos campos
        $tipoIndicacion = $request->get('tipoIndicacion');
        $frecuencia = $request->get('frecuencia');
        $duracion = $request->get('duracion');
        $viaAdministracion = $request->get('viaAdministracion');
        
        // Fechas de vigencia
        $fechaInicio = $request->get('fechaInicio');
        $fechaFin = $request->get('fechaFin');
        
        $error = false;
        $message = 'ok';

        if ($mes == '') {
            $now = new \DateTime();
        }

        try {
            // Crear un nuevo objeto ConsumiblesClientes con datos completos
            $consumiblesClientesHistorico = new ConsumiblesClientes();
            
            // Configurar todos los campos correctamente
            $ahora = new \DateTime();
            $consumiblesClientesHistorico->setFecha($ahora);
            
            // Si no se proporciona mes o año, usar el mes y año actuales
            if (empty($mes)) {
                $mes = $ahora->format('m');
            }
            if (empty($year)) {
                $year = $ahora->format('Y');
            }
            
            $consumiblesClientesHistorico->setMes($mes);
            $consumiblesClientesHistorico->setYear($year);
            $consumiblesClientesHistorico->setActivo(filter_var($activo, FILTER_VALIDATE_BOOLEAN));
            // No podemos guardar el usuario porque no existe el campo en la entidad
            // Será necesario modificar la entidad para añadir este campo
            
            // Guardar las notas o indicaciones de texto libre
            $notas = $request->get('notas', '');
            $consumiblesClientesHistorico->setNotas($notas);
            $consumiblesClientesHistorico->setAccion($accion);
            $consumiblesClientesHistorico->setCantidad($cantidad);
            $consumiblesClientesHistorico->setClienteId($clienteId);
            
            // Guardar los nuevos campos de indicación
            if (!empty($tipoIndicacion)) {
                $consumiblesClientesHistorico->setTipoIndicacion($tipoIndicacion);
            }
            if (!empty($frecuencia)) {
                $consumiblesClientesHistorico->setFrecuencia($frecuencia);
            }
            if (!empty($duracion)) {
                $consumiblesClientesHistorico->setDuracion($duracion);
            }
            if (!empty($viaAdministracion)) {
                $consumiblesClientesHistorico->setViaAdministracion($viaAdministracion);
            }
            if (!empty($unidadMedida)) {
                $consumiblesClientesHistorico->setUnidadMedida($unidadMedida);
            }
            
            // Configurar fechas de vigencia
            if (!empty($fechaInicio)) {
                $fechaInicioObj = new \DateTime($fechaInicio);
                $consumiblesClientesHistorico->setFechaInicio($fechaInicioObj);
            } else {
                // Si no se especifica, usar la fecha actual
                $consumiblesClientesHistorico->setFechaInicio(new \DateTime());
            }
            
            if (!empty($fechaFin)) {
                $fechaFinObj = new \DateTime($fechaFin);
                $consumiblesClientesHistorico->setFechaFin($fechaFinObj);
            }
            
            // ConsumibleId puede ser null para indicaciones sin medicamento específico
            if (!empty($consumibleId)) {
                $consumiblesClientesHistorico->setConsumibleId($consumibleId);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($consumiblesClientesHistorico);
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
        $year = $request->query->get('year', '');

        $consumiblesClientes = $consumiblesClientesRepository->findByAccionAndClientId($id, $mes, $fecha, $accion, $year);

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
     * @Route("/historico/enfermeria/{id}", name="consumible_enfermeria", methods={"GET"})
     */
    /*public function enfermeria($id, ClienteRepository $clienteRepository, ConsumibleRepository $consumibleRepository, ConsumiblesClientesRepository $consumiblesClientesRepository, TipoConsumibleRepository $tipoConsumibleRepository, Request $request): Response
    {

        $consumibles = $consumibleRepository->findBy([], ['tipo'=>'DESC']);

        $consumibleArray = [];
        foreach ($consumibles as $consumible) {
            $consumibleArray[$consumible->getId()] = $consumible;
        }

        $mes = $request->get('mes', '');

        if ($mes == '') {
            $now = new \DateTime();
            $mes = $now->modify("+1 month")->format('m');
        }

        $year = $request->query->get('year', '2022');

        $consumiblesClientes = $consumiblesClientesRepository->findBy(['clienteId' => $id, 'mes' => $mes, 'year' => $year, 'accion' => 0]);
        $cliente = $clienteRepository->find($id);

        return $this->render('consumible/enfermeria_planilla.html.twig', [
            'cliente' => $cliente,
            'consumibles' => $consumibleArray,
            'consumiblesClientes' => $consumiblesClientes,
            'mes' => $mes,
            'meses' => ['Enero' => '01', 'Febrero' => '02', 'Marzo' => '03', 'Abril' => 04, 'Mayo' => '05', 'Junio' => '06', 'Julio' => '07', 'Agosto' => '08', 'Septiembre' => '09', 'Octubre' => '10', 'Noviembre' => '11', 'Diciembre' => '12', ],
            'paginaImprimible' => true,
        ]);

    }*/

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
     * @Route("/guardar-indicaciones/{id}", name="consumible_guardar_indicaciones", methods={"POST"})
     */
    public function guardarIndicaciones($id, Request $request, HorarioTomaCalculatorService $horarioCalculator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $cliente = $entityManager->getRepository(Cliente::class)->find($id);
        
        if (!$cliente) {
            return $this->json(['success' => false, 'message' => 'Cliente no encontrado']);
        }
        
        try {
            // Obtener datos directamente del request
            $tipoIndicacion = $request->request->get('tipoIndicacion');
            $consumibleId = $request->request->get('consumibleId');
            $procedimientoPersonalizado = $request->request->get('procedimientoPersonalizado');
            $cantidad = $request->request->get('cantidad');
            $unidadMedida = $request->request->get('unidadMedida');
            $frecuencia = $request->request->get('frecuencia');
            $fechaInicio = $request->request->get('fechaInicio');
            $duracion = $request->request->get('duracion');
            $duracionValor = $request->request->get('duracionValor');
            $fechaFin = $request->request->get('fechaFin');
            $notas = $request->request->get('notas');
            $activo = $request->request->get('activo') ? true : false;
            $historiaPacienteId = $request->request->get('historiaPacienteId');
            $horario = $request->request->get('horario') ?: null;
            
            // Validar datos básicos
            if ($tipoIndicacion === 'medicamento' && !$consumibleId) {
                return $this->json(['success' => false, 'message' => 'Debe seleccionar un medicamento']);
            }
            
            // Si es medicamento, buscamos el consumible
            $consumible = null;
            if ($consumibleId) {
                $consumible = $entityManager->getRepository(Consumible::class)->find($consumibleId);
                
                if (!$consumible) {
                    return $this->json(['success' => false, 'message' => 'Consumible no encontrado']);
                }
            }
            
            // Buscar si ya existe una indicación similar para actualizar
            $existente = null;
            if ($consumible) {
                $existente = $entityManager->getRepository(ConsumiblesClientes::class)->findOneBy([
                    'clienteId' => $cliente->getId(),
                    'consumibleId' => $consumibleId
                ]);
            }
            
            if ($existente) {
                // Actualizar registro existente
                $existente->setCantidad($cantidad ?? 1);
                $existente->setActivo($activo);
                $existente->setNotas($notas ?? '');
                $existente->setTipoIndicacion($tipoIndicacion);
                $existente->setUnidadMedida($unidadMedida);
                $existente->setFrecuencia($frecuencia);
                
                // Asegurarse de que el año y mes estén establecidos
                if (!$existente->getYear()) {
                    $existente->setYear((new \DateTime())->format('Y'));
                }
                if (!$existente->getMes()) {
                    $existente->setMes((new \DateTime())->format('m'));
                }
                // Asegurarse de que accion y via_administracion estén establecidos
                if (!$existente->getAccion()) {
                    $existente->setAccion('indicacion');
                }
                if (!$existente->getViaAdministracion()) {
                    $existente->setViaAdministracion($unidadMedida); // Usar unidad de medida como vía por defecto
                }
                
                if ($fechaInicio) {
                    $fechaInicioObj = new \DateTime($fechaInicio);
                    $existente->setFechaInicio($fechaInicioObj);
                    // También actualizamos fecha_inicio para mantener coherencia
                    $existente->setFechaInicio($fechaInicioObj);
                }
                
                if ($duracion === 'especifica' && $duracionValor) {
                    $existente->setDuracionValor($duracionValor);
                    $existente->setFechaFin(null);
                } else if ($duracion === 'fechaFin' && $fechaFin) {
                    $existente->setDuracionValor(null);
                    $existente->setFechaFin(new \DateTime($fechaFin));
                } else {
                    $existente->setDuracionValor(null);
                    $existente->setFechaFin(null);
                }
                
                if ($historiaPacienteId) {
                    $existente->setHistoriaPacienteId($historiaPacienteId);
                }
                
                // Asegurarse de que todos los campos requeridos en la base de datos estén establecidos
                $entityManager->persist($existente);
            } else {
                // Crear nueva indicación
                $nuevaIndicacion = new ConsumiblesClientes();
                $nuevaIndicacion->setClienteId($cliente->getId());
                $nuevaIndicacion->setConsumibleId($consumibleId);
                $nuevaIndicacion->setCantidad($cantidad ?? 1);
                $nuevaIndicacion->setActivo($activo);
                $nuevaIndicacion->setNotas($notas ?? '');
                
                // Establecer fecha actual
                $fechaActual = new \DateTime();
                $nuevaIndicacion->setFecha($fechaActual);
                
                // Establecer el año y mes (necesario para la base de datos)
                $nuevaIndicacion->setYear($fechaActual->format('Y'));
                $nuevaIndicacion->setMes($fechaActual->format('m'));
                
                // Establecer otros campos requeridos por la base de datos
                $nuevaIndicacion->setAccion('indicacion');
                $nuevaIndicacion->setViaAdministracion($unidadMedida); // Usar unidad de medida como vía de administración si no hay otra info
                
                $nuevaIndicacion->setTipoIndicacion($tipoIndicacion);
                $nuevaIndicacion->setUnidadMedida($unidadMedida);
                $nuevaIndicacion->setFrecuencia($frecuencia);
                
                // Procedimiento personalizado si aplica
                if ($procedimientoPersonalizado && ($tipoIndicacion === 'procedimiento' || $tipoIndicacion === 'control')) {
                    $nuevaIndicacion->setProcedimientoPersonalizado($procedimientoPersonalizado);
                }
                
                // Establecer fecha de inicio
                if ($fechaInicio) {
                    $fechaInicioObj = new \DateTime($fechaInicio);
                    $nuevaIndicacion->setFechaInicio($fechaInicioObj);
                    // Establecer fecha de inicio también como fecha_inicio
                    $nuevaIndicacion->setFechaInicio($fechaInicioObj);
                }
                
                // Manejar duración
                if ($duracion === 'especifica' && $duracionValor) {
                    $nuevaIndicacion->setDuracionValor($duracionValor);
                    $nuevaIndicacion->setDuracion('especifica');
                    $nuevaIndicacion->setFechaFin(null);
                } else if ($duracion === 'fechaFin' && $fechaFin) {
                    $nuevaIndicacion->setFechaFin(new \DateTime($fechaFin));
                    $nuevaIndicacion->setDuracion('fechaFin');
                    $nuevaIndicacion->setDuracionValor(null);
                } else {
                    $nuevaIndicacion->setDuracion('indefinida');
                }
                
                // Asociar con historia del paciente si está disponible
                if ($historiaPacienteId) {
                    $nuevaIndicacion->setHistoriaPacienteId($historiaPacienteId);
                }
                
                // Establecer horario si está disponible
                if ($horario && !empty($horario)) {
                    try {
                        $nuevaIndicacion->setHorario(new \DateTime($horario));
                    } catch (\Exception $e) {
                        // Ignorar error de formato de horario
                        error_log('Error en formato de horario: ' . $e->getMessage());
                    }
                }
                
                // Asegurarse de que todos los campos requeridos en la base de datos estén establecidos
                if ($nuevaIndicacion->getConsumibleId() === null && $tipoIndicacion === 'medicamento') {
                    throw new \Exception('Falta seleccionar un consumible para la indicación de tipo medicamento');
                }
                
                $entityManager->persist($nuevaIndicacion);
                
                // Generar horarios de toma automáticamente
                try {
                    // Hacer flush primero para que la indicación tenga ID
                    $entityManager->flush();
                    $horarioCalculator->generarHorariosToma($nuevaIndicacion);
                } catch (\Exception $e) {
                    // Log del error detallado pero no interrumpir el proceso
                    error_log('Error generando horarios para indicación ID ' . $nuevaIndicacion->getId() . ': ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    // La indicación se guarda aunque fallen los horarios
                }
            }
            
            return $this->json(['success' => true, 'message' => 'Indicación guardada correctamente']);
            
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al guardar la indicación: ' . $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/editar-indicacion/{id}", name="consumible_editar_indicacion", methods={"POST"})
     */
    public function editarIndicacion($id, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($id);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            // Obtener datos del request
            $tipoIndicacion = $request->request->get('tipoIndicacion');
            $consumibleId = $request->request->get('consumibleId');
            $procedimientoPersonalizado = $request->request->get('procedimientoPersonalizado');
            $cantidad = $request->request->get('cantidad');
            $unidadMedida = $request->request->get('unidadMedida');
            $frecuencia = $request->request->get('frecuencia');
            $fechaInicio = $request->request->get('fechaInicio');
            $duracion = $request->request->get('duracion');
            $duracionValor = $request->request->get('duracionValor');
            $fechaFin = $request->request->get('fechaFin');
            $notas = $request->request->get('notas');
            $hora = $request->request->get('hora') ?: null;
            $horarioPrimeraToma = $request->request->get('horarioPrimeraToma') ?: null;
            
            // Actualizar campos
            if ($tipoIndicacion) $indicacion->setTipoIndicacion($tipoIndicacion);
            if ($consumibleId) $indicacion->setConsumibleId($consumibleId);
            if ($procedimientoPersonalizado) $indicacion->setProcedimientoPersonalizado($procedimientoPersonalizado);
            if ($cantidad) $indicacion->setCantidad($cantidad);
            if ($unidadMedida) $indicacion->setUnidadMedida($unidadMedida);
            if ($frecuencia) $indicacion->setFrecuencia($frecuencia);
            if ($notas !== null) $indicacion->setNotas($notas);
            
            // Fechas y horarios
            if ($fechaInicio) {
                $indicacion->setFechaInicio(new \DateTime($fechaInicio));
            }
            if ($hora && !empty($hora)) {
                try {
                    $indicacion->setHora(new \DateTime($hora));
                } catch (\Exception $e) {
                    error_log('Error en formato de hora al editar: ' . $e->getMessage());
                }
            }
            if ($horarioPrimeraToma && !empty($horarioPrimeraToma)) {
                try {
                    $indicacion->setHorarioPrimeraToma(new \DateTime($horarioPrimeraToma));
                } catch (\Exception $e) {
                    error_log('Error en formato de horario primera toma al editar: ' . $e->getMessage());
                }
            }
            
            // Duración
            if ($duracion === 'especifica' && $duracionValor) {
                $indicacion->setDuracionValor($duracionValor);
                $indicacion->setFechaFin(null);
            } elseif ($duracion === 'fechaFin' && $fechaFin) {
                $indicacion->setFechaFin(new \DateTime($fechaFin));
                $indicacion->setDuracionValor(null);
            }
            
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Indicación actualizada correctamente']);
            
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al editar la indicación: ' . $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/suspender-indicacion/{id}", name="consumible_suspender_indicacion", methods={"POST"})
     */
    public function suspenderIndicacion($id, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($id);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            $indicacion->setEstadoSuspendido(true);
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Indicación suspendida correctamente']);
            
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al suspender la indicación: ' . $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/reanudar-indicacion/{id}", name="consumible_reanudar_indicacion", methods={"POST"})
     */
    public function reanudarIndicacion($id, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($id);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            $indicacion->setEstadoSuspendido(false);
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Indicación reanudada correctamente']);
            
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al reanudar la indicación: ' . $e->getMessage()]);
        }
    }
    
    /**
     * @Route("/cancelar-indicacion/{id}", name="consumible_cancelar_indicacion", methods={"POST"})
     */
    public function cancelarIndicacion($id, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $indicacion = $entityManager->getRepository(ConsumiblesClientes::class)->find($id);
        
        if (!$indicacion) {
            return $this->json(['success' => false, 'message' => 'Indicación no encontrada']);
        }
        
        try {
            $indicacion->setActivo(false);
            $entityManager->persist($indicacion);
            $entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Indicación cancelada correctamente']);
            
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al cancelar la indicación: ' . $e->getMessage()]);
        }
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
}
