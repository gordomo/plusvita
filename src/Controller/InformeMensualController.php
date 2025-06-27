<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Entity\InformeMensual;
use App\Form\InformeMensualType;
use App\Repository\ClienteRepository;
use App\Repository\InformeMensualRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @Route("/informe/mensual")
 */
class InformeMensualController extends AbstractController
{
    /**
     * @Route("/", name="informe_mensual_index", methods={"GET"})
     */
    public function index(InformeMensualRepository $informeMensualRepository): Response
    {
        return $this->render('informe_mensual/index.html.twig', [
            'informes' => $informeMensualRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{cliente}", name="informe_mensual_new", methods={"GET", "POST"})
     */
    public function new(Request $request, Cliente $cliente, InformeMensualRepository $informeMensualRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $entityManager = $this->getDoctrine()->getManager();
        $doctor = $entityManager->getRepository(Doctor::class)->findOneBy(['user' => $user]);
        
        if (!$doctor) {
            $this->addFlash('error', 'No se encontró el doctor asociado al usuario actual.');
            return $this->redirectToRoute('doctor_historia');
        }
        
        $informeMensual = new InformeMensual();
        $informeMensual->setCliente($cliente);
        $informeMensual->setDoctor($doctor);
        
        $form = $this->createForm(InformeMensualType::class, $informeMensual);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $informeMensualRepository->save($informeMensual, true);

            $this->addFlash('success', 'Informe mensual creado correctamente.');
            return $this->redirectToRoute('informe_mensual_show', ['id' => $informeMensual->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('informe_mensual/new.html.twig', [
            'informe_mensual' => $informeMensual,
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="informe_mensual_show", methods={"GET"})
     */
    public function show(InformeMensual $informeMensual): Response
    {
        return $this->render('informe_mensual/show.html.twig', [
            'informeMensual' => $informeMensual,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="informe_mensual_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, InformeMensual $informeMensual, InformeMensualRepository $informeMensualRepository): Response
    {
        $form = $this->createForm(InformeMensualType::class, $informeMensual);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $informeMensualRepository->save($informeMensual, true);

            $this->addFlash('success', 'Informe mensual actualizado correctamente.');
            return $this->redirectToRoute('informe_mensual_show', ['id' => $informeMensual->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('informe_mensual/edit.html.twig', [
            'informeMensual' => $informeMensual,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="informe_mensual_delete", methods={"GET", "POST"})
     */
    public function delete(Request $request, InformeMensual $informeMensual, InformeMensualRepository $informeMensualRepository): Response
    {
        // Eliminamos el informe mensual directamente
        $informeMensualRepository->remove($informeMensual, true);
        $this->addFlash('success', 'Informe mensual eliminado correctamente.');

        return $this->redirectToRoute('doctor_historia', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/pdf", name="informe_mensual_pdf", methods={"GET"})
     */
    public function generarPdf(InformeMensual $informeMensual): Response
    {
        // Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        
        // Renderizar la vista que queremos convertir a PDF
        $html = $this->renderView('informe_mensual/pdf.html.twig', [
            'informeMensual' => $informeMensual
        ]);
        
        // Cargar HTML en Dompdf
        $dompdf->loadHtml($html);
        
        // Establecer el tamaño del papel y orientación
        $dompdf->setPaper('A4', 'portrait');
        
        // Renderizar el PDF
        $dompdf->render();
        
        // Generar nombre de archivo
        $cliente = $informeMensual->getCliente();
        $fecha = $informeMensual->getFechaCreacion()->format('Y-m-d');
        $filename = 'informe_mensual_' . $cliente->getApellido() . '_' . $fecha . '.pdf';
        
        // Descargar el PDF generado
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
}
