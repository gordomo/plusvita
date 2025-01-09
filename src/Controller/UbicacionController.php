<?php

// src/Controller/UbicacionController.php

namespace App\Controller;

use App\Entity\Ubicacion;
use App\Form\UbicacionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UbicacionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class UbicacionController extends AbstractController
{
    /**
     * @Route("/ubicacion", name="app_ubicacion_index")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $ubicaciones = $em->getRepository(Ubicacion::class)->findAll();

        return $this->render('ubicacion/index.html.twig', [
            'ubicaciones' => $ubicaciones,
        ]);
    }

    /**
     * @Route("/ubicacion/new", name="app_ubicacion_new")
     */
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ubicacion = new Ubicacion();
        $form = $this->createForm(UbicacionType::class, $ubicacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ubicacion);
            $em->flush();

            return $this->redirectToRoute('app_ubicacion_index');
        }

        return $this->render('ubicacion/_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/ubicacion/search_name", name="ubicacion_search_name", methods={"GET"})
     */
    public function searchUbicacion(Request $request, UbicacionRepository $ubicacionRepository): JsonResponse
    {
        $term = $request->query->get('term', '');
        $ubicaciones = $ubicacionRepository->findByTerm($term);

        $results = [];
        foreach ($ubicaciones as $ubicacion) {
            $results[] = ['id' => $ubicacion->getId(), 'text' => $ubicacion->getNombre()];
        }

        return new JsonResponse($results);
    }

    /**
     * @Route("/ubicacion/validate", name="app_ubicacion_validate")
     */
    public function validate(Request $request, UbicacionRepository $ubicacionRepository): JsonResponse
    {
        $nombre = $request->query->get('nombre');
        $ubicacionExistente = $ubicacionRepository->findOneBy(['nombre' => $nombre]);

        return new JsonResponse(['exists' => $ubicacionExistente !== null]);
    }


    /**
     * @Route("/ubicacion/{id}/edit", name="app_ubicacion_edit")
     */
    public function edit(Request $request, Ubicacion $ubicacion, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UbicacionType::class, $ubicacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_ubicacion_index');
        }

        return $this->render('ubicacion/_new.html.twig', [
            'form' => $form->createView(),
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/ubicacion/{id}", name="app_ubicacion_show")
     */
    public function show(Ubicacion $ubicacion): Response
    {
        return $this->render('ubicacion/show.html.twig', [
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/ubicacion/{id}/delete", name="app_ubicacion_delete", methods={"POST"})
     */
    public function delete(Request $request, Ubicacion $ubicacion, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ubicacion->getId(), $request->request->get('_token'))) {
            $em->remove($ubicacion);
            $em->flush();
        }

        return $this->redirectToRoute('app_ubicacion_index');
    }

}

