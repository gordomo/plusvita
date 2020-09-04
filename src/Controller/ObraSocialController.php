<?php

namespace App\Controller;

use App\Entity\ObraSocial;
use App\Form\ObraSocialType;
use App\Repository\ObraSocialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/obras-sociales")
 */
class ObraSocialController extends AbstractController
{
    /**
     * @Route("/", name="obra_social_index", methods={"GET"})
     */
    public function index(ObraSocialRepository $obraSocialRepository): Response
    {
        return $this->render('obra_social/index.html.twig', [
            'obra_socials' => $obraSocialRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="obra_social_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $obraSocial = new ObraSocial();
        $form = $this->createForm(ObraSocialType::class, $obraSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($obraSocial);
            $entityManager->flush();

            return $this->redirectToRoute('obra_social_index');
        }

        return $this->render('obra_social/new.html.twig', [
            'obra_social' => $obraSocial,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="obra_social_show", methods={"GET"})
     */
    public function show(ObraSocial $obraSocial): Response
    {
        return $this->render('obra_social/show.html.twig', [
            'obra_social' => $obraSocial,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="obra_social_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ObraSocial $obraSocial): Response
    {
        $form = $this->createForm(ObraSocialType::class, $obraSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('obra_social_index');
        }

        return $this->render('obra_social/edit.html.twig', [
            'obra_social' => $obraSocial,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="obra_social_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ObraSocial $obraSocial): Response
    {
        if ($this->isCsrfTokenValid('delete'.$obraSocial->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($obraSocial);
            $entityManager->flush();
        }

        return $this->redirectToRoute('obra_social_index');
    }
}
