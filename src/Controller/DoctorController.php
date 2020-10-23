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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    public function index(Request $request, DoctorRepository $doctorRepository): Response
    {
        $empleado = [
            'Mucamo/a',
            'Enfermero/a',
            'Auxiliar de enfermeria',
            'Asistente de enfermeria',
            'Mantenimiento',
            'Cocinero',
            'Ayudante de cocina',
            'Administrativo',
            'Recepcionista',
            'Coordinador de pisos',
            'Coordinador general',
            'Coordinador de enfermeria'
        ];
        $directo = [
            'Nutricionista',
            'Director medico',
            'Sub director medico',
            'Trabajadora social',
            'Psiquiatra',
            'Infectologo',
            'Contador',
            'Abogado',
            'Estudio contable',
            'Directivo',
            'Programador',
        ];
        $prestacion = [
            'Profesional por prestacion',
            'Medico de guardia',
            'Kinesiologo',
            'Kinesiologo respiratorio',
            'Terapista ocupacional',
            'Fonoaudiologo',
            'Psicologo',
            'Fisiatra',
            'Neurologo',
            'Cardiologo',
            'Urologo',
            'Hematologo',
            'Neumonologo',
        ];
        $sinContrato = [
            'Cirujano',
            'Traumatologo',
            'Neumonologo',
        ];
        $contratos = ['empleado'=> $empleado, 'directo' => $directo, 'prestacion' => $prestacion, 'sinContrato' => $sinContrato];

        $ctrs = $request->query->get('ctr');
        $ctrsArray = explode(',', $ctrs);


        if(!empty($ctrs)) {
            $doctores = $doctorRepository->findByContratos($ctrsArray);
        } else {
            $doctores = $doctorRepository->findAll();
        }

        return $this->render('doctor/index.html.twig', [
            'doctors' => $doctores,
            'contratos' => $contratos,
            'ctrsArray' => $ctrsArray,
            
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
        $doctor->setRoles([]);
        $doctor->setInicioContrato(new \DateTime());

        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => true, 'allow_extra_fields' =>true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('dni');
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

            $dias =  [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];

            $horarios = [];

            foreach ($dias as $key => $dia) {
                $desde = $form->get($dia.'desde')->getData() ?? '08:00';
                $ydesde = $form->get('y'.$dia.'desde')->getData() ?? $desde;

                $hasta = $form->get($dia.'hasta')->getData() ?? '18:00';
                $yhasta = $form->get('y'.$dia.'hasta')->getData() ?? $hasta;

                $horarios[$key] = ['desde' => $desde, 'hasta' => $hasta, 'ydesde' => $ydesde, 'yhasta' => $yhasta];
            }

            $doctor->setBusinessHours($horarios);


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doctor);
            $entityManager->flush();

            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/new.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),

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
        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => false, 'allow_extra_fields' =>true]);
        $dias =  [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firmaPdfFile = $form->get('firmaPdf')->getData();
            if ($firmaPdfFile) {
                $originalFilename = pathinfo($firmaPdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $form->get('dni');
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
                if(!empty($doctor->getFirma())) {
                    if ($filesystem->exists($this->getParameter('firmas_directory').'/'.$doctor->getFirma())) {
                        $filesystem->remove($this->getParameter('firmas_directory').'/'.$doctor->getFirma());
                    }
                }
                $doctor->setFirma($newFilename);
            }



            $horarios = [];


            foreach ($dias as $key => $dia) {
                $desde = $form->get($dia.'desde')->getData() ?? 0;
                $ydesde = $form->get('y'.$dia.'desde')->getData() ?? $desde;

                $hasta = $form->get($dia.'hasta')->getData() ?? 0;
                $yhasta = $form->get('y'.$dia.'hasta')->getData() ?? $hasta;

                if($desde != 0 && $hasta != 0) {
                    $horarios[$key] = ['desde' => $desde, 'hasta' => $hasta, 'ydesde' => $ydesde, 'yhasta' => $yhasta];
                }

            }

            $doctor->setBusinessHours($horarios);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doctor_index');
        }
        $businessHours = [];
        $doctorActualBusinessHours = $doctor->getBusinessHours();
        foreach ($dias as $key => $dia) {
            if (isset($doctorActualBusinessHours[$key])) {
                $businessHours[$dia] = $doctorActualBusinessHours[$key];
            }
        }
//dd($businessHours);
        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'title' => 'Editar:' . $doctor->getNombre() . ' ' . $doctor->getApellido(),
            'businessHours' => $businessHours,
        ]);
    }

    /**
     * @Route("/{id}/egreso", name="staff_egreso", methods={"GET","POST"})
     */
    public function egreso(Request $request, Doctor $doctor): Response
    {
        $form = $this->createForm(DoctorType::class, $doctor, ['is_new' => false, 'egreso' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doctor);
            $entityManager->flush();
            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form->createView(),
            'title' => 'Egreso para:' . $doctor->getNombre() . ' ' . $doctor->getApellido(),
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
