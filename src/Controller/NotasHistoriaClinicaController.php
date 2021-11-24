<?php

namespace App\Controller;

use App\Entity\NotasHistoriaClinica;
use App\Form\NotasHistoriaClinicaType;
use App\Repository\NotasHistoriaClinicaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/notas/historia/clinica")
 */
class NotasHistoriaClinicaController extends AbstractController
{
    /**
     * @Route("/", name="notas_historia_clinica_index", methods={"GET"})
     */
    public function index(NotasHistoriaClinicaRepository $notasHistoriaClinicaRepository): Response
    {
        return $this->render('notas_historia_clinica/index.html.twig', [
            'notas_historia_clinicas' => $notasHistoriaClinicaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="notas_historia_clinica_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $notasHistoriaClinica = new NotasHistoriaClinica();
        $form = $this->createForm(NotasHistoriaClinicaType::class, $notasHistoriaClinica);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($notasHistoriaClinica);
            $entityManager->flush();

            return $this->redirectToRoute('notas_historia_clinica_index');
        }

        return $this->render('notas_historia_clinica/new.html.twig', [
            'notas_historia_clinica' => $notasHistoriaClinica,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="notas_historia_clinica_show", methods={"GET"})
     */
    public function show(NotasHistoriaClinica $notasHistoriaClinica): Response
    {
        return $this->render('notas_historia_clinica/show.html.twig', [
            'notas_historia_clinica' => $notasHistoriaClinica,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="notas_historia_clinica_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, NotasHistoriaClinica $notasHistoriaClinica): Response
    {
        $form = $this->createForm(NotasHistoriaClinicaType::class, $notasHistoriaClinica);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('notas_historia_clinica_index');
        }

        return $this->render('notas_historia_clinica/edit.html.twig', [
            'notas_historia_clinica' => $notasHistoriaClinica,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="notas_historia_clinica_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NotasHistoriaClinica $notasHistoriaClinica): Response
    {
        if ($this->isCsrfTokenValid('delete'.$notasHistoriaClinica->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($notasHistoriaClinica);
            $entityManager->flush();
        }

        return $this->redirectToRoute('notas_historia_clinica_index');
    }
}
