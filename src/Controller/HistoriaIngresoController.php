<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\HistoriaIngreso;
use App\Form\HistoriaIngresoType;
use App\Repository\HistoriaIngresoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/historia/ingreso")
 */
class HistoriaIngresoController extends AbstractController
{
    /**
     * @Route("/", name="historia_ingreso_index", methods={"GET"})
     */
    public function index(HistoriaIngresoRepository $historiaIngresoRepository): Response
    {
        return $this->render('historia_ingreso/index.html.twig', [
            'historia_ingresos' => $historiaIngresoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{cliente}", name="historia_ingreso_new", methods={"GET","POST"})
     */
    public function new(Request $request, Cliente $cliente, SluggerInterface $slugger): Response
    {
        if($cliente->getHistoriaIngreso()) {
            $historiaIngreso = $cliente->getHistoriaIngreso();
        } else {
            $historiaIngreso = new HistoriaIngreso();
            $historiaIngreso->setCliente($cliente);
        }

        $form = $this->createForm(HistoriaIngresoType::class, $historiaIngreso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adjuntos = $form->get('adjunto')->getData();
            foreach($adjuntos as $adjunto) {
                $originalFilename = pathinfo($adjunto->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$adjunto->guessExtension();

                try {
                    $adjunto->move(
                        $this->getParameter('adjuntos_pacientes_directory') . '/' . $cliente->getId() . '/complementerios/',
                        $newFilename
                    );
                } catch (FileException $e) {
                    dd($e->getMessage());
                    // ... handle exception if something happens during file upload
                }


                $historiaIngreso->addExamenesComplementeriosFiles($newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($historiaIngreso);
            $entityManager->flush();


            return $this->redirectToRoute('doctor_historia', [], Response::HTTP_SEE_OTHER);
            /*return $this->redirectToRoute('historia_ingreso_new', ['cliente' => $cliente->getId()], Response::HTTP_SEE_OTHER);*/
        }

        return $this->render('historia_ingreso/new.html.twig', [
            'historia_ingreso' => $historiaIngreso,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="historia_ingreso_show", methods={"GET"})
     */
    public function show(HistoriaIngreso $historiaIngreso): Response
    {
        return $this->render('historia_ingreso/show.html.twig', [
            'historia_ingreso' => $historiaIngreso,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="historia_ingreso_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, HistoriaIngreso $historiaIngreso): Response
    {
        $form = $this->createForm(HistoriaIngresoType::class, $historiaIngreso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('historia_ingreso_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('historia_ingreso/edit.html.twig', [
            'historia_ingreso' => $historiaIngreso,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="historia_ingreso_delete", methods={"POST"})
     */
    public function delete(Request $request, HistoriaIngreso $historiaIngreso): Response
    {
        if ($this->isCsrfTokenValid('delete'.$historiaIngreso->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($historiaIngreso);
            $entityManager->flush();
        }

        return $this->redirectToRoute('historia_ingreso_index', [], Response::HTTP_SEE_OTHER);
    }
    /**
     * @Route("/borrar/examen/file", name="borrar_examen_complementerio_file", methods={"POST"})
     */
    public function borrarExamenComplementarioFile(Request $request, HistoriaIngresoRepository $historiaIngresoRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $nombre = $request->request->get('nombre');
        $clienteId = $request->request->get('clienteId');

        $historiaIngreso = $historiaIngresoRepository->findBy(['cliente' => $clienteId]);
        $historiaIngreso[0]->removeExamenesComplementeriosFiles($nombre, $this->getParameter('adjuntos_pacientes_directory') . '/' . $clienteId . '/complementerios/');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($historiaIngreso[0]);
        $entityManager->flush();

        return new JsonResponse(['error' => false, 'message' => $nombre]);

    }
}
