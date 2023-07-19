<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Form\ClienteType;
use App\Form\HabitacionType;
use App\Repository\ClienteRepository;
use App\Repository\HabitacionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/habitacion")
 */
class HabitacionController extends AbstractController
{
    /**
     * @Route("/", name="habitacion_index", methods={"GET"})
     */
    public function index(ClienteRepository $clienteRepository, HabitacionRepository $habitacionRepository, Request $request): Response
    {
        $pestana = $request->query->get('pestana') ?? 'todas';

        switch ($pestana) {
            case 'completas':
                $habitaciones = $habitacionRepository->findHabitacionSinCamasDisponibles();
                break;
            case 'camas-vacias':
                $habitaciones = $habitacionRepository->findHabitacionConCamasDisponibles();
                break;
            default:
                $habitaciones = $habitacionRepository->findAll();
                break;
        }

        return $this->render('habitacion/index.html.twig', [
            'habitacions' => $habitaciones,
            'clienteRepository' => $clienteRepository,
            'fecha' => new \DateTime(),
            'pestana' => $pestana,
        ]);
    }

    /**
     * @Route("/new", name="habitacion_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $habitacion = new Habitacion();
        $habitacion->setCamasDisponibles(2);
        $habitacion->setCamasOcupadas([]);
        $form = $this->createForm(HabitacionType::class, $habitacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($habitacion);
            $entityManager->flush();

            return $this->redirectToRoute('habitacion_index');
        }

        return $this->render('habitacion/new.html.twig', [
            'habitacion' => $habitacion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="habitacion_show", methods={"GET"})
     */
    public function show(Habitacion $habitacion): Response
    {
        return $this->render('habitacion/show.html.twig', [
            'habitacion' => $habitacion,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="habitacion_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Habitacion $habitacion): Response
    {
        $form = $this->createForm(HabitacionType::class, $habitacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('habitacion_index');
        }

        return $this->render('habitacion/edit.html.twig', [
            'habitacion' => $habitacion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="habitacion_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Habitacion $habitacion): Response
    {
        if ($this->isCsrfTokenValid('delete'.$habitacion->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($habitacion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('habitacion_index');
    }

    /**
     * @Route("/cama/disp/{id}/{cliente_id}", name="habitacion_camas_disp", methods={"GET"})
     */
    public function getCamasDisponibles(int $id, Request $request, HabitacionRepository $habitacionRepository, ClienteRepository $clienteRepository, int $cliente_id = 0): Response
    {

        if($cliente_id) {
            $cliente = $clienteRepository->find($cliente_id);
        } else {
            $cliente = new Cliente();
        }

        $habitacion = $habitacionRepository->find($id);
        $camasOcupadas = $habitacion->getCamasOcupadas();
        $cantCamas = $habitacion->getCamasDisponibles();
        $camasDispArray = [];
        for ($i = 1; $i <= $cantCamas; $i++) {
            if(!in_array($i, $camasOcupadas)) {
                $camasDispArray[$i] = $i;
            }
        }

        if ($cliente->getHabitacion() == $id) {
            $camasDispArray[$cliente->getNCama()] = $cliente->getNCama();
        }


        ksort($camasDispArray);

        $form = $this->createForm(ClienteType::class, $cliente, ['camasDisp' => $camasDispArray, 'bloquearHab' => empty($camasOcupadas)]);
        return $this->render('habitacion/_camas.html.twig', [
            'form' => $form->createView(),
        ]);

    }
}
