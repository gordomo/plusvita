<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Doctor;
use App\Form\ClienteType;
use App\Form\DoctorType;
use App\Repository\DoctorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;

/**
 * @Route("/staff")
 */
class DoctorController extends AbstractController
{
    /**
     * @Route("/", name="doctor_index", methods={"GET"})
     */
    public function index(DoctorRepository $doctorRepository): Response
    {
        return $this->render('doctor/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
            
        ]);
    }

    /**
     * @Route("/tipo-select", name="staff_tipo_select")
     */
    public function getModalidadesSelect(Request $request)
    {
        $doctor = new Doctor();
        $doctor->setTipo($request->query->get('tipo'));
        $form = $this->createForm(DoctorType::class, $doctor);
        if (!$form->has('modalidad')) {
            return new Response(null, 204);
        }

        return $this->render('doctor/_modalidad.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/new", name="doctor_new", methods={"GET","POST"})
     */
    public function new(Request $request,  SluggerInterface $slugger): Response
    {
        $doctor = new Doctor();
        $doctor->setInicioContrato(new \DateTime());

        $claseParaEspecialidad = empty($doctor->getModalidad()) ? 'd-none' : '';

        $form = $this->createForm(DoctorType::class, $doctor);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('username');
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$firmaPdfFile->guessExtension();

                try {
                    $firmaPdfFile->move(
                        $this->getParameter('firmas_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $doctor->setFirma($newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doctor);
            $entityManager->flush();

            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/new.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'claseParaEspecialidad' => $claseParaEspecialidad,
            
        ]);
    }

    /**
     * @Route("/{id}", name="doctor_show", methods={"GET"})
     */
    public function show(Doctor $doctor): Response
    {
        return $this->render('doctor/show.html.twig', [
            'doctor' => $doctor,
            
        ]);
    }

    /**
     * @Route("/{id}/edit", name="doctor_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Doctor $doctor, SluggerInterface $slugger): Response
    {
        $claseParaEspecialidad = empty($doctor->getEspecialidad()) ? 'd-none' : '';

        $form = $this->createForm(DoctorType::class, $doctor, array('is_new' => false));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('username');
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$firmaPdfFile->guessExtension();

                try {
                    $firmaPdfFile->move(
                        $this->getParameter('firmas_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $filesystem = new Filesystem();
                $filesystem->remove($this->getParameter('firmas_directory').'/'.$doctor->getFirma());
                $doctor->setFirma($newFilename);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'claseParaEspecialidad' => $claseParaEspecialidad,
            
        ]);
    }

    /**
     * @Route("/{id}", name="doctor_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Doctor $doctor): Response
    {
        if ($this->isCsrfTokenValid('delete'.$doctor->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doctor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doctor_index');
    }
}
