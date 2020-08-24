<?php

namespace App\Controller;

use App\Entity\AdjuntosStaff;
use App\Entity\Doctor;
use App\Form\AdjuntosStaffType;
use App\Repository\AdjuntosStaffRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\ExtensionFileException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/adjuntos/staff")
 */
class AdjuntosStaffController extends AbstractController
{
    /**
     * @Route("/", name="adjuntos_staff_index", methods={"GET"})
     */
    public function index(AdjuntosStaffRepository $adjuntosStaffRepository): Response
    {
        return $this->render('adjuntos_staff/index.html.twig', [
            'adjuntos_staffs' => $adjuntosStaffRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{id}", name="adjuntos_staff_new", methods={"GET","POST"})
     */
    public function new(Request $request, Doctor $doctor,  SluggerInterface $slugger, AdjuntosStaffRepository $adjuntosStaffRepository): Response
    {
        $adjuntosStaff = new AdjuntosStaff();
        $adjuntosStaff->setIdStaff($doctor->getId());

        $adjuntosActuales = $adjuntosStaffRepository->findBy(array('id_staff' => $doctor->getId()), array('tipo' => 'ASC'));

        $adjuntosArray = [];
        foreach ($adjuntosActuales as $adjunto) {
            $adjuntosArray[$adjunto->getTipo()][] = $adjunto;
        }

        $form = $this->createForm(AdjuntosStaffType::class, $adjuntosStaff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $archivoAdjunto = $form->get('archivoAdjunto')->getData();
            if ($archivoAdjunto) {
                $originalFilename = pathinfo($form->get('nombre')->getData(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$archivoAdjunto->guessExtension();
                $newPath = $this->getParameter('adjuntos_staff_directory') . '/' . $doctor->getId() . '/' . $form->get('tipo')->getData();

                try {
                    $archivoAdjunto->move(
                        $newPath,
                        $newFilename
                    );
                    $adjuntosStaff->setUrl($newPath);
                } catch (FileException $e) {
                    throw new ExtensionFileException('Adjunto seleccionado no permitido. Intente con una Imagen o un PDF');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $adjuntosStaff->setUrl($newFilename);
            }

            $entityManager->persist($adjuntosStaff);
            $entityManager->flush();

            return $this->redirectToRoute('adjuntos_staff_new',
                [
                    'id' => $doctor->getId(),
                ]);
        }

        return $this->render('adjuntos_staff/new.html.twig', [
            'adjuntos_staff' => $adjuntosStaff,
            'nombreStaff' => $doctor->getNombre(),
            'adjuntosActuales' => $adjuntosArray,
            'form' => $form->createView(),
            'staff' => $doctor,
        ]);
    }

    /**
     * @Route("/{id}", name="adjuntos_staff_show", methods={"GET"})
     */
    public function show(AdjuntosStaff $adjuntosStaff): Response
    {
        return $this->render('adjuntos_staff/show.html.twig', [
            'adjuntos_staff' => $adjuntosStaff,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="adjuntos_staff_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, AdjuntosStaff $adjuntosStaff): Response
    {
        $form = $this->createForm(AdjuntosStaffType::class, $adjuntosStaff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('adjuntos_staff_index');
        }

        return $this->render('adjuntos_staff/edit.html.twig', [
            'adjuntos_staff' => $adjuntosStaff,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="adjuntos_staff_delete", methods={"DELETE"})
     */
    public function delete(Request $request, AdjuntosStaff $adjuntosStaff): Response
    {
        if ($this->isCsrfTokenValid('delete'.$adjuntosStaff->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($adjuntosStaff);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adjuntos_staff_index');
    }
}
