<?php

namespace App\Controller;

use App\Entity\AdjuntosPacientes;
use App\Entity\Cliente;
use App\Form\AdjuntosPacientesType;
use App\Repository\AdjuntosPacientesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\ExtensionFileException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/adjuntos/pacientes")
 */
class AdjuntosPacientesController extends AbstractController
{
    /**
     * @Route("/", name="adjuntos_pacientes_index", methods={"GET"})
     */
    public function index(AdjuntosPacientesRepository $adjuntosPacientesRepository): Response
    {
        return $this->render('adjuntos_pacientes/index.html.twig', [
            'adjuntos_pacientes' => $adjuntosPacientesRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{id}", name="adjuntos_pacientes_new", methods={"GET","POST"})
     */
    public function new(Request $request, Cliente $cliente, SluggerInterface $slugger, AdjuntosPacientesRepository $adjuntosPacientesRepository): Response
    {
        $adjuntosPaciente = new AdjuntosPacientes();
        $adjuntosPaciente->setIdPaciente($cliente->getId());

        $adjuntosActuales = $adjuntosPacientesRepository->findBy(array('id_paciente' => $cliente->getId()), array('tipo' => 'ASC'));

        $adjuntosArray = [];
        foreach ($adjuntosActuales as $adjunto) {
            $adjuntosArray[$adjunto->getTipo()][] = $adjunto;
        }

        //dd($adjuntosArray);

        $form = $this->createForm(AdjuntosPacientesType::class, $adjuntosPaciente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $archivoAdjunto = $form->get('archivoAdjunto')->getData();
            if ($archivoAdjunto) {
                $originalFilename = pathinfo($form->get('nombre')->getData(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$archivoAdjunto->guessExtension();
                $newPath = $this->getParameter('adjuntos_pacientes_directory') . '/' . $cliente->getId() . '/' . $form->get('tipo')->getData();

                try {
                    $archivoAdjunto->move(
                        $newPath,
                        $newFilename
                    );
                    $adjuntosPaciente->setUrl($newPath);
                } catch (FileException $e) {
                    throw new ExtensionFileException('Adjunto seleccionado no permitido. Intente con una Imagen o un PDF');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $adjuntosPaciente->setUrl($newFilename);
            }

            $entityManager->persist($adjuntosPaciente);
            $entityManager->flush();

            return $this->redirectToRoute('adjuntos_pacientes_new',
                [
                    'id' => $cliente->getId(),
                ]);
        }

        return $this->render('adjuntos_pacientes/new.html.twig', [
            'adjuntos_paciente' => $adjuntosPaciente,
            'nombreCliente' => $cliente->getNombre(),
            'adjuntosActuales' => $adjuntosArray,
            'form' => $form->createView(),
            'cliente' => $cliente,
        ]);
    }

    /**
     * @Route("/{id}", name="adjuntos_pacientes_show", methods={"GET"})
     */
    public function show(AdjuntosPacientes $adjuntosPaciente): Response
    {
        return $this->render('adjuntos_pacientes/show.html.twig', [
            'adjuntos_paciente' => $adjuntosPaciente,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="adjuntos_pacientes_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, AdjuntosPacientes $adjuntosPaciente): Response
    {
        $form = $this->createForm(AdjuntosPacientesType::class, $adjuntosPaciente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('adjuntos_pacientes_index');
        }

        return $this->render('adjuntos_pacientes/edit.html.twig', [
            'adjuntos_paciente' => $adjuntosPaciente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="adjuntos_pacientes_delete", methods={"DELETE"})
     */
    public function delete(Request $request, AdjuntosPacientes $adjuntosPaciente): Response
    {
        if ($this->isCsrfTokenValid('delete'.$adjuntosPaciente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($adjuntosPaciente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adjuntos_pacientes_index');
    }
}
