<?php

namespace App\Controller;

use App\Entity\TipoConsumible;
use App\Form\TipoConsumibleType;
use App\Repository\TipoConsumibleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/consumible")
 */
class TipoConsumibleController extends AbstractController
{
    /**
     * @Route("/", name="tipo_consumible_index", methods={"GET"})
     */
    public function index(TipoConsumibleRepository $tipoConsumibleRepository): Response
    {
        return $this->render('tipo_consumible/index.html.twig', [
            'tipo_consumibles' => $tipoConsumibleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="tipo_consumible_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $tipoConsumible = new TipoConsumible();
        $form = $this->createForm(TipoConsumibleType::class, $tipoConsumible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($tipoConsumible);
            $entityManager->flush();

            return $this->redirectToRoute('consumible_index');
        }

        return $this->render('tipo_consumible/new.html.twig', [
            'tipo_consumible' => $tipoConsumible,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tipo_consumible_show", methods={"GET"})
     */
    public function show(TipoConsumible $tipoConsumible): Response
    {
        return $this->render('tipo_consumible/show.html.twig', [
            'tipo_consumible' => $tipoConsumible,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="tipo_consumible_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TipoConsumible $tipoConsumible): Response
    {
        $form = $this->createForm(TipoConsumibleType::class, $tipoConsumible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('tipo_consumible_index');
        }

        return $this->render('tipo_consumible/edit.html.twig', [
            'tipo_consumible' => $tipoConsumible,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tipo_consumible_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TipoConsumible $tipoConsumible): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoConsumible->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tipoConsumible);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipo_consumible_index');
    }
}
