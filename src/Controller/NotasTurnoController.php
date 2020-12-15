<?php

namespace App\Controller;

use App\Entity\NotasTurno;
use App\Form\NotasTurnoType;
use App\Repository\BookingRepository;
use App\Repository\NotasTurnoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/notas/turno")
 */
class NotasTurnoController extends AbstractController
{
    /**
     * @Route("/", name="notas_turno_index", methods={"GET"})
     */
    public function index(NotasTurnoRepository $notasTurnoRepository): Response
    {
        return $this->render('notas_turno/index.html.twig', [
            'notas_turnos' => $notasTurnoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="notas_turno_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $notasTurno = new NotasTurno();
        $form = $this->createForm(NotasTurnoType::class, $notasTurno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($notasTurno);
            $entityManager->flush();

            return $this->redirectToRoute('notas_turno_index');
        }

        return $this->render('notas_turno/new.html.twig', [
            'notas_turno' => $notasTurno,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new/{turnoId}", name="nota_para_turno", methods={"GET","POST"})
     */
    public function newNotaDelTurno(Request $request, BookingRepository $bookingRepository, NotasTurnoRepository $notasTurnoRepository, $turnoId): Response
    {
        $notasTurno = new NotasTurno();
        $turno = $bookingRepository->find($turnoId);

        $notasTurno->setTurno($turno);
        $form = $this->createForm(NotasTurnoType::class, $notasTurno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($notasTurno);
            $entityManager->flush();

            return $this->redirectToRoute('doctor_agenda', ['periodo' => 'dia']);
        }

        $notas_turnos = $notasTurnoRepository->findBy(['turno' => $turno]);

        return $this->render('notas_turno/new.html.twig', [
            'notas_turno' => $notasTurno,
            'notas_turnos' => $notas_turnos,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="notas_turno_show", methods={"GET"})
     */
    public function show(NotasTurno $notasTurno): Response
    {
        return $this->render('notas_turno/show.html.twig', [
            'notas_turno' => $notasTurno,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="notas_turno_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, NotasTurno $notasTurno): Response
    {
        $form = $this->createForm(NotasTurnoType::class, $notasTurno);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('notas_turno_index');
        }

        return $this->render('notas_turno/edit.html.twig', [
            'notas_turno' => $notasTurno,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="notas_turno_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NotasTurno $notasTurno): Response
    {
        if ($this->isCsrfTokenValid('delete'.$notasTurno->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($notasTurno);
            $entityManager->flush();
        }

        return $this->redirectToRoute('notas_turno_index');
    }
}
