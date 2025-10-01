<?php

namespace App\Controller;

use App\Entity\NovedadUbicacion;
use App\Entity\Ubicacion;
use App\Form\NovedadUbicacionType;
use App\Repository\NovedadUbicacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ubicacion/{ubicacionId}/novedades")
 */
class NovedadUbicacionController extends AbstractController
{
    /**
     * @Route("/", name="app_novedad_ubicacion_index", methods={"GET"})
     */
    public function index(int $ubicacionId, NovedadUbicacionRepository $novedadUbicacionRepository, EntityManagerInterface $em): Response
    {
        $ubicacion = $em->getRepository(Ubicacion::class)->find($ubicacionId);
        if (!$ubicacion) {
            throw $this->createNotFoundException('Ubicación no encontrada');
        }

        $novedades = $novedadUbicacionRepository->findByUbicacion($ubicacionId);

        return $this->render('novedad_ubicacion/index.html.twig', [
            'novedades' => $novedades,
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/new", name="app_novedad_ubicacion_new", methods={"GET", "POST"})
     */
    public function new(int $ubicacionId, Request $request, EntityManagerInterface $em): Response
    {
        $ubicacion = $em->getRepository(Ubicacion::class)->find($ubicacionId);
        if (!$ubicacion) {
            throw $this->createNotFoundException('Ubicación no encontrada');
        }

        $novedad = new NovedadUbicacion();
        $novedad->setUbicacion($ubicacion);
        $novedad->setUsuarioCreador($this->getUser());

        $form = $this->createForm(NovedadUbicacionType::class, $novedad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($novedad);
            $em->flush();

            $this->addFlash('success', 'Novedad creada exitosamente.');
            return $this->redirectToRoute('app_novedad_ubicacion_index', ['ubicacionId' => $ubicacionId]);
        }

        return $this->render('novedad_ubicacion/new.html.twig', [
            'novedad' => $novedad,
            'form' => $form->createView(),
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/{id}", name="app_novedad_ubicacion_show", methods={"GET"})
     */
    public function show(int $ubicacionId, NovedadUbicacion $novedad): Response
    {
        $ubicacion = $novedad->getUbicacion();
        if ($ubicacion->getId() !== $ubicacionId) {
            throw $this->createNotFoundException('Novedad no pertenece a esta ubicación');
        }

        return $this->render('novedad_ubicacion/show.html.twig', [
            'novedad' => $novedad,
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_novedad_ubicacion_edit", methods={"GET", "POST"})
     */
    public function edit(int $ubicacionId, Request $request, NovedadUbicacion $novedad, EntityManagerInterface $em): Response
    {
        $ubicacion = $novedad->getUbicacion();
        if ($ubicacion->getId() !== $ubicacionId) {
            throw $this->createNotFoundException('Novedad no pertenece a esta ubicación');
        }

        $form = $this->createForm(NovedadUbicacionType::class, $novedad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si se marca como resuelto, establecer fecha de resolución
            if ($novedad->getEstado() === 'resuelto' && !$novedad->getFechaResolucion()) {
                $novedad->setFechaResolucion(new \DateTime());
            }
            
            $em->flush();

            $this->addFlash('success', 'Novedad actualizada exitosamente.');
            return $this->redirectToRoute('app_novedad_ubicacion_index', ['ubicacionId' => $ubicacionId]);
        }

        return $this->render('novedad_ubicacion/edit.html.twig', [
            'novedad' => $novedad,
            'form' => $form->createView(),
            'ubicacion' => $ubicacion,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="app_novedad_ubicacion_delete", methods={"POST"})
     */
    public function delete(int $ubicacionId, Request $request, NovedadUbicacion $novedad, EntityManagerInterface $em): Response
    {
        $ubicacion = $novedad->getUbicacion();
        if ($ubicacion->getId() !== $ubicacionId) {
            throw $this->createNotFoundException('Novedad no pertenece a esta ubicación');
        }

        if ($this->isCsrfTokenValid('delete'.$novedad->getId(), $request->request->get('_token'))) {
            $em->remove($novedad);
            $em->flush();
            $this->addFlash('success', 'Novedad eliminada exitosamente.');
        }

        return $this->redirectToRoute('app_novedad_ubicacion_index', ['ubicacionId' => $ubicacionId]);
    }
}
