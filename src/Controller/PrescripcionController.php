<?php

namespace App\Controller;

use App\Entity\Prescripcion;
use App\Form\PrescripcionType;
use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\PrescripcionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(PrescripcionRepository $prescripcionRepository): Response
    {
        return $this->render('prescripcion/index.html.twig', [
            'prescripcions' => $prescripcionRepository->findAll(),
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
}
