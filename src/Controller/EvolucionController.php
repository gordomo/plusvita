<?php

namespace App\Controller;

use http\Client;
use App\Entity\Doctor;
use App\Entity\Evolucion;
use App\Form\EvolucionType;
use App\Service\DoctorService;
use App\Repository\DoctorRepository;
use App\Repository\ClienteRepository;
use App\Repository\EvolucionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @Route("/evolucion")
 */
class EvolucionController extends AbstractController
{
    /**
     * @Route("/", name="evolucion_index", methods={"GET"})
     */
    public function index(Request $request, EvolucionRepository $evolucionRepository, ClienteRepository $clienteRepository, DoctorService $DoctorService): Response
    {
        $user               = $this->getUser();
        $tipoSeleccionado   = $request->query->get('tipoSeleccionado', 0);
        $limit              = $request->query->get('limit', 100);
        $currentPage        = $request->query->get('currentPage', 1);

        $f          = new \DateTime('first day of this month');
        $l          = new \DateTime('last day of this month');
        $from       = $request->get('from' , $f->format('Y-m-d'));
        $to         = $request->get('to', $l->format('Y-m-d'));  
        $fechaDesde = $from ? new \DateTime($from. '0:0:0') : $from;
        $fechaHasta = $to   ? new \DateTime($to. '23:59:59'): $to;

        $modalidades = [];
        if($user instanceOf Doctor) {
            $modalidades = $user->getModalidad();
        }

        if( count($modalidades) === 1 && $tipoSeleccionado === 0) {
            $tipoSeleccionado = $modalidades[0];
        }

        $clientId = $request->get('cliente');
        $cliente = $clienteRepository->find($clientId);

        if(!$DoctorService->puedeEvolucionar() && !$cliente->getAmbulatorioPresente()) {
            die('No puede evolucionar');
        }

        $evoluciones = $evolucionRepository->findByClienteYTipo($cliente, $tipoSeleccionado, $currentPage, $limit, $fechaDesde, $fechaHasta);
        

        $maxPages = ceil($evoluciones['paginator']->count() / $limit);
        

        return $this->render('evolucion/index.html.twig', [
            'nombreCliente'     => $cliente->getNombre() . ' ' . $cliente->getApellido(),
            'evolucions'        => $evoluciones['paginator'],
            'all_items'         => $evoluciones['query'],
            'clienteId'         => $cliente->getId(),
            'tipoSeleccionado'  => $tipoSeleccionado,
            'maxPages'          => $maxPages,
            'thisPage'          => $currentPage,
            'clientId'          => $clientId,
            'fechaDesde'        => $from,
            'fechaHasta'        => $to,
        ]);
    }

    /**
     * @Route("/new", name="evolucion_new", methods={"GET","POST"})
     */
    public function new(SluggerInterface $slugger, ValidatorInterface $validator, Request $request, ClienteRepository $clienteRepository, EvolucionRepository $evolucionRepository, DoctorRepository $doctorRepository, \App\Repository\UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $puedenEditarEvoluciones = $this->isGranted('patient.evolve');
        
        // Obtener doctores con rol doctor (User objects, no solo emails)
        $doctoresConRol = $userRepository->findByRole('doctor');
        $docArr = [];
        foreach ($doctoresConRol as $doc) {
            // Usar el email como key y el objeto User como value
            $docArr[$doc->getEmail()] = $doc;
        }
        
        $error = '';
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $evolucion = new Evolucion();

        $cliente = $clienteRepository->find($request->get('cliente'));
        if($cliente->getDerivado() and !$puedenEditarEvoluciones) die('paciente derivado, no se puede evolucionar');
        //solo activos
        $evolucion->setPaciente($cliente);
        $evolucion->setUser($user->getEmail());
        $evolucion->setFecha(new \DateTime());

        $modalidades = $user->getModalidad();
        $modalidad = '';
        if( count($modalidades) === 1 ) {
            $modalidad = $modalidades[0];
        }

        $form = $this->createForm(EvolucionType::class, $evolucion, ['modalidad' => $modalidad, 'doctores' => $docArr, 'usuarioActual' => $user->getEmail(), 'puedenEditarEvoluciones' => $puedenEditarEvoluciones]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

                $entityManager = $this->getDoctrine()->getManager();

                $adjuntos = $form->get('adjunto')->getData();
                
                $doctorQueFirma = $user; // Por defecto, el usuario actual
                if ($form->has('doctor') && $form->get('doctor')->getData()) {
                    $doctorId = $form->get('doctor')->getData();
                    $doctorQueFirma = $userRepository->find($doctorId);
                    if ($doctorQueFirma) {
                        $evolucion->setUser($doctorQueFirma->getEmail());
                    }
                }
                
                // Guardar datos del doctor que firma
                if ($doctorQueFirma) {
                    $evolucion->setFirmaDoctorNombre($doctorQueFirma->getNombre());
                    $evolucion->setFirmaDoctorApellido($doctorQueFirma->getApellido());
                    $evolucion->setFirmaDoctorMatricula($doctorQueFirma->getLegajo()); // Usando legajo como matrícula
                    
                    // Obtener la firma activa del doctor
                    $firmaActiva = $doctorQueFirma->getActiveFirma();
                    if ($firmaActiva) {
                        $evolucion->setFirmaDoctorPath($firmaActiva->getFilePath());
                    }
                }
                
                if(!empty($cliente->getFegreso()) && $cliente->getFegreso() < $evolucion->getFecha() && !$puedenEditarEvoluciones) {
                    die('paciente con fecha egreso anterior a la fecha de la evolución, no se puede evolucionar');
                }
                
                $hoy = new \DateTime();
                
                if ($evolucion->getFecha()->diff($hoy)->days > 0 && !$puedenEditarEvoluciones) {
                    die('la fecha de la evolución es anterior al día de la fecha, no se puede evolucionar');
                }
                
                foreach($adjuntos as $adjunto) {
                    $originalFilename = pathinfo($adjunto->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$adjunto->guessExtension();

                    try {
                        $adjunto->move(
                            $this->getParameter('adjuntos_pacientes_directory') . '/' . $cliente->getId() . '/evoluciones/',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        dd($e->getMessage());
                        // ... handle exception if something happens during file upload
                    }

                    $evolucion->addAdjuntoUrl($newFilename);
                }

                $entityManager->persist($evolucion);
                $entityManager->flush();

                return $this->redirectToRoute('evolucion_index', ['cliente' => $cliente->getId()], Response::HTTP_SEE_OTHER);

        } else {
            $errors = $validator->validate($form);
            if (!empty($errors[0])) {
                $error = $errors[0]->getMessage();
            }
        }

        return $this->render('evolucion/new.html.twig', [
            'evolucion' => $evolucion,
            'nombreCliente' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
            'form' => $form->createView(),
            'clienteId' => $cliente->getId(),
            'error' => $error,
        ]);



    }

    /**
     * @Route("/{id}", name="evolucion_show", methods={"GET"})
     */
    public function show($id, EvolucionRepository $evolucionRepository, DoctorRepository $doctorRepository): Response
    {
        $evolucion = $evolucionRepository->find($id);
        $firmaData = null;
        
        // Get the doctor associated with this evolution (by email)
        if ($evolucion && $evolucion->getUser()) {
            $doctor = $doctorRepository->findOneBy(['email' => $evolucion->getUser()]);
            if ($doctor) {
                $firmaData = $this->obtenerFirmaDoctor($doctor);
            }
        }
        
        return $this->render('evolucion/show.html.twig', [
            'evolucion' => $evolucion,
            'firmaData' => $firmaData,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="evolucion_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evolucion $evolucion, DoctorRepository $doctorRepository, \App\Repository\UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $modalidades = $user->getModalidad();
        $usuarioActual = $evolucion->getUser();

        $modalidad = '';
        if( count($modalidades) === 1 ) {
            $modalidad = $modalidades[0];
        }

        // Obtener doctores con rol doctor (User objects, no solo emails)
        $doctoresConRol = $userRepository->findByRole('doctor');
        $docArr = [];
        foreach ($doctoresConRol as $doc) {
            // Usar el email como key y el objeto User como value
            $docArr[$doc->getEmail()] = $doc;
        }

        $redirect = $request->get('redirect', '');

        $puedenEditarEvoluciones = $this->isGranted('patient.evolve');

        if ( $puedenEditarEvoluciones ) {
            $form = $this->createForm(EvolucionType::class, $evolucion, ['usuarioActual'=>$usuarioActual, 'modalidad' => $modalidad, 'doctores' => $docArr, 'puedenEditarEvoluciones' => $puedenEditarEvoluciones]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Si hay un campo doctor y fue modificado, actualizar los datos del doctor
                if ($form->has('doctor') && $form->get('doctor')->getData()) {
                    $doctorId = $form->get('doctor')->getData();
                    $doctorQueFirma = $userRepository->find($doctorId);
                    if ($doctorQueFirma) {
                        $evolucion->setUser($doctorQueFirma->getEmail());
                        $evolucion->setFirmaDoctorNombre($doctorQueFirma->getNombre());
                        $evolucion->setFirmaDoctorApellido($doctorQueFirma->getApellido());
                        $evolucion->setFirmaDoctorMatricula($doctorQueFirma->getLegajo());
                        
                        // Obtener la firma activa del doctor
                        $firmaActiva = $doctorQueFirma->getActiveFirma();
                        if ($firmaActiva) {
                            $evolucion->setFirmaDoctorPath($firmaActiva->getFilePath());
                        }
                    }
                }
                
                $this->getDoctrine()->getManager()->flush();

                if($redirect !== '') {
                    return $this->redirect($redirect);
                } else {
                    return $this->redirectToRoute('evolucion_index', ['cliente' => $evolucion->getPaciente()->getId()], Response::HTTP_SEE_OTHER);
                }
            }

            return $this->render('evolucion/edit.html.twig', [
                'evolucion' => $evolucion,
                'clienteId' => $evolucion->getPaciente()->getId(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->redirectToRoute('evolucion_index', ['cliente' => $evolucion->getPaciente()->getId()], Response::HTTP_SEE_OTHER);
        }

    }

    /**
     * @Route("/{id}", name="evolucion_delete", methods={"POST"})
     */
    public function delete(Request $request, Evolucion $evolucion): Response
    {
        $clienteId = $evolucion->getPaciente()->getId();
        $redirect = $request->get('redirect', '');

        $puedenEditarEvoluciones = $this->isGranted('patient.evolve');

        if( $puedenEditarEvoluciones ) {
            if ($this->isCsrfTokenValid('delete'.$evolucion->getId(), $request->request->get('_token'))) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($evolucion);
                $entityManager->flush();
            }
        }
        if($redirect !== '') {
            return $this->redirect($redirect);
        } else {
            return $this->redirectToRoute('evolucion_index', ['cliente' => $clienteId], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/get-adjunto/{pdf}/{clienteId}", name="evolucion_get_adjunto", methods={"GET"})
     */
    public function getFile(Request $request, $clienteId, $pdf): Response
    {
        $file = $this->getParameter('adjuntos_pacientes_directory') . '/' . $clienteId . '/evoluciones/' . $pdf;
        return new BinaryFileResponse($file);
    }

    /**
     * Get the signature data for a doctor
     * Priority: UserFirma -> Doctor.firma
     */
    private function obtenerFirmaDoctor(Doctor $doctor): ?array
    {
        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        
        // First, try to get UserFirma (new system)
        $user = $userRepository->findOneBy(['email' => $doctor->getEmail()]);
        if ($user) {
            $activeFirma = $user->getActiveFirma();
            if ($activeFirma && $activeFirma->getFilePath()) {
                return [
                    'nombre' => $doctor->getNombre(),
                    'apellido' => $doctor->getApellido(),
                    'matricula' => $doctor->getMatricula(),
                    'firma_path' => $activeFirma->getFilePath(),
                ];
            }
        }
        
        // Fallback to Doctor.firma (legacy system)
        if ($doctor->getFirma()) {
            return [
                'nombre' => $doctor->getNombre(),
                'apellido' => $doctor->getApellido(),
                'matricula' => $doctor->getMatricula(),
                'firma_path' => $doctor->getFirma(),
            ];
        }
        
        return null;
    }
}
