<?php

namespace App\Controller;

use App\Entity\Prescripcion;
use App\Form\PrescripcionType;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\PrescripcionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/prescripcion")
 */
class PrescripcionController extends AbstractController
{
    /**
     * @Route("/", name="prescripcion_index", methods={"GET"})
     */
    public function index(PrescripcionRepository $prescripcionRepository, ClienteRepository $clienteRepository): Response
    {
        $isEnfermero = $this->isEnfermero();
        $ahora = new \DateTime();
        
        // Obtener todas las prescripciones activas
        $prescripciones = $prescripcionRepository->findBy(['activa' => 1]);
        
        // Si es enfermero, filtrar prescripciones según ventana de 8 horas
        // Mostrar: próximas 8 horas hacia adelante + pendientes de últimas 8 horas
        if ($isEnfermero) {
            $prescripcionesFiltradas = [];
            
            // Calcular ventanas de tiempo
            $hace8Horas = clone $ahora;
            $hace8Horas->modify('-8 hours');
            $en8Horas = clone $ahora;
            $en8Horas->modify('+8 hours');
            
            // Fechas sin hora para comparación
            $fechaAhora = clone $ahora;
            $fechaAhora->setTime(0, 0, 0);
            $fechaHace8Horas = clone $hace8Horas;
            $fechaHace8Horas->setTime(0, 0, 0);
            $fechaEn8Horas = clone $en8Horas;
            $fechaEn8Horas->setTime(0, 0, 0);
            
            // Si las próximas 8 horas caen en el día siguiente, incluir ese día también
            $fechaMaxima = max($fechaAhora, $fechaEn8Horas);
            
            foreach ($prescripciones as $prescripcion) {
                $fechaPrescripcion = clone $prescripcion->getFecha();
                $fechaPrescripcion->setTime(0, 0, 0);
                
                $hecho = $prescripcion->getHecho() ?: [];
                $estaEnVentana = false;
                
                // 1. PRÓXIMAS 8 HORAS: Prescripciones que están vigentes y dentro de las próximas 8 horas
                // Incluir prescripciones del día actual (si no están completadas) y del día siguiente (si aplica)
                if ($fechaPrescripcion >= $fechaAhora && $fechaPrescripcion <= $fechaMaxima) {
                    // Si es hoy, verificar que no esté completada
                    if ($fechaPrescripcion == $fechaAhora) {
                        $hoyFormateado = $ahora->format('d/m/Y');
                        if (!in_array($hoyFormateado, $hecho)) {
                            $estaEnVentana = true;
                        }
                    } else {
                        // Es una fecha futura dentro de la ventana (próximo día si aplica)
                        $estaEnVentana = true;
                    }
                }
                
                // 2. PENDIENTES DE ÚLTIMAS 8 HORAS: Prescripciones que deberían haberse completado
                // pero no están marcadas como "hecho" en las últimas 8 horas
                if (!$estaEnVentana && $fechaPrescripcion <= $fechaAhora) {
                    // Verificar fechas desde hace 8 horas hasta ahora
                    $fechaVerificar = clone $fechaHace8Horas;
                    
                    while ($fechaVerificar <= $fechaAhora) {
                        $fechaVerificarFormateada = $fechaVerificar->format('d/m/Y');
                        
                        // Si la prescripción es de esta fecha o anterior y no está marcada como hecha
                        if ($fechaPrescripcion <= $fechaVerificar && !in_array($fechaVerificarFormateada, $hecho)) {
                            $estaEnVentana = true;
                            break;
                        }
                        
                        $fechaVerificar->modify('+1 day');
                    }
                }
                
                if ($estaEnVentana) {
                    $prescripcionesFiltradas[] = $prescripcion;
                }
            }
            $prescripciones = $prescripcionesFiltradas;
        }
        
        // Obtener IDs únicos de clientes que tienen prescripciones
        $clienteIds = [];
        foreach ($prescripciones as $prescripcion) {
            $clienteId = $prescripcion->getCliente()->getId();
            if (!in_array($clienteId, $clienteIds)) {
                $clienteIds[] = $clienteId;
            }
        }
        
        // Obtener clientes activos
        // Si es enfermero, solo obtener los clientes que tienen prescripciones en su turno
        if ($isEnfermero) {
            if (!empty($clienteIds)) {
                // Obtener clientes por IDs y filtrar solo los activos
                $clientesObtenidos = $clienteRepository->findBy(['id' => $clienteIds]);
                $hoyParaFiltro = new \DateTime();
                $clientes = [];
                
                foreach ($clientesObtenidos as $cliente) {
                    // Verificar que el cliente cumpla los criterios de activo
                    if (($cliente->getFEgreso() === null || $cliente->getFEgreso() > $hoyParaFiltro) &&
                        ($cliente->getDerivado() == 0 || $cliente->getDerivado() === null) &&
                        ($cliente->getAmbulatorio() == 0 || $cliente->getAmbulatorio() === null) &&
                        $cliente->getHabitacion() !== null) {
                        $clientes[] = $cliente;
                    }
                }
            } else {
                // Si no hay prescripciones para el turno, no mostrar clientes
                $clientes = [];
            }
        } else {
            // Para no enfermeros, obtener todos los clientes activos sin paginación
            $clientes = $clienteRepository->findActivosSinPag(new \DateTime(), '', null, null, null);
        }
        
        // Organizar prescripciones por cliente (solo para clientes que están en la lista)
        $presArray = [];
        $clienteIdsEnLista = array_map(function($c) { return $c->getId(); }, $clientes);
        
        foreach ($prescripciones as $prescripcion) {
            $clienteId = $prescripcion->getCliente()->getId();
            if (in_array($clienteId, $clienteIdsEnLista)) {
                $presArray[$clienteId][] = $prescripcion;
            }
        }

        return $this->render('prescripcion/index.html.twig', [
            'prescripcions' => $prescripcionRepository->findAll(),
            'isEnfermero' => $isEnfermero,
            'clientes' => $clientes,
            'prescripciones' => $presArray,
        ]);
    }

    /**
     * @Route("/new/{id}", name="prescripcion_new", methods={"GET","POST"})
     */
    public function new($id, Request $request, ClienteRepository $clienteRepository, DoctorRepository $doctorRepository, PrescripcionRepository $prescripcionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $doctor = $doctorRepository->findOneBy(['email' => $user->getEmail()]);

        if( !$doctor ) {
            return $this->redirectToRoute('app_logout');
        }

        $cliente = $clienteRepository->find($id);
        $prescripcionesAnteriores = $prescripcionRepository->findBy(['cliente' => $cliente]);

        $prescripcion = new Prescripcion();
        $prescripcion->setCliente($cliente);
        $prescripcion->setActiva(1);
        $prescripcion->setUser($doctor);
        $prescripcion->setFecha(new \DateTime());
        $form = $this->createForm(PrescripcionType::class, $prescripcion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($prescripcion);
            $entityManager->flush();

            return $this->redirectToRoute('prescripcion_new', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prescripcion/new.html.twig', [
            'prescripcion' => $prescripcion,
            'nombreCliente' => $cliente->getNombreApellido(),
            'prescripcionesAnteriores' => $prescripcionesAnteriores,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="prescripcion_show", methods={"GET"})
     */
    public function show(Prescripcion $prescripcion): Response
    {
        return $this->render('prescripcion/show.html.twig', [
            'prescripcion' => $prescripcion,
        ]);
    }

    /**
     * @Route("/{id}/edit/{clienteId}", name="prescripcion_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Prescripcion $prescripcion, $clienteId): Response
    {
        $prescripcion->setActiva(!$prescripcion->getActiva());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($prescripcion);
        $entityManager->flush();
        return $this->redirectToRoute('prescripcion_new', ['id' => $clienteId], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}", name="prescripcion_delete", methods={"POST"})
     */
    public function delete(Request $request, Prescripcion $prescripcion): Response
    {
        if ($this->isCsrfTokenValid('delete'.$prescripcion->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($prescripcion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('prescripcion_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/hecho/{id}", name="prescripcion_hecho", methods={"POST", "GET"})
     */
    public function echo(Request $request, Prescripcion $prescripcion): Response
    {
        $fecha = $request->get('fecha');
        $hecho = $prescripcion->getHecho();

        $key = array_search($fecha, $hecho);

        if ($key !== false) {
            unset($hecho[$key]);
        } else {
            $hecho[] = $fecha;
        }
        sort($hecho);

        $prescripcion->setHecho($hecho);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($prescripcion);
        $entityManager->flush();

        return new JsonResponse('ok');
    }

    private function isEnfermero()
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return false;
        }

        // Verificar si tiene algún rol de enfermería usando el nuevo sistema
        return $user->hasRole('enfermero') || 
               $user->hasRole('auxiliar_enfermeria') || 
               $user->hasRole('asistente_enfermeria') ||
               $user->hasRole('coordinador_enfermeria');
    }
}
