<?php

namespace App\Controller;

use http\Client;
use App\Entity\Doctor;
use App\Entity\Evolucion;
use App\Form\EvolucionType;
use App\Service\UserEvolutionService;
use App\Repository\DoctorRepository;
use App\Repository\ClienteRepository;
use App\Repository\EvolucionRepository;
use App\Repository\PresentesRepository;
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
    public function index(Request $request, EvolucionRepository $evolucionRepository, ClienteRepository $clienteRepository, UserEvolutionService $userEvolutionService): Response
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

        // Modalidades deprecado - ya no se auto-selecciona tipo de evolución
        // Los usuarios ahora seleccionan manualmente el tipo
        // $modalidades = [];
        // if($user instanceOf Doctor) {
        //     $modalidades = $user->getModalidad();
        // }
        // if( count($modalidades) === 1 && $tipoSeleccionado === 0) {
        //     $tipoSeleccionado = $modalidades[0];
        // }

        $clientId = $request->get('cliente');
        $cliente = $clienteRepository->find($clientId);

        if(!$userEvolutionService->canEvolveToday() && !$cliente->getAmbulatorioPresente()) {
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
    public function new(SluggerInterface $slugger, ValidatorInterface $validator, Request $request, ClienteRepository $clienteRepository, EvolucionRepository $evolucionRepository, DoctorRepository $doctorRepository, \App\Repository\UserRepository $userRepository, PresentesRepository $presentesRepository): Response
    {
        $user = $this->getUser();
        
        $error = '';
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Verificar permisos
        $puedeEvolucionar = $this->isGranted('patient.evolve');
        $puedenEditarEvoluciones = $this->isGranted('patient.edit_evolve');
        
        if (!$puedeEvolucionar) {
            throw $this->createAccessDeniedException('No tienes permiso para crear evoluciones');
        }
        
        // Solo cargar otros usuarios si tiene permiso para editar evoluciones
        $docArr = [];
        if ($puedenEditarEvoluciones) {
            // Obtener todos los usuarios que tienen permiso para evolucionar
            $todosUsuarios = $userRepository->findAll();
            
            // Agrupar usuarios por roles (un usuario puede aparecer en múltiples grupos)
            $usuariosPorRol = [];
            
            foreach ($todosUsuarios as $usr) {
                // Filtrar solo usuarios que tengan permiso patient.evolve
                if ($usr->hasPermission('patient.evolve')) {
                    // Obtener todos los roles activos del usuario
                    $rolesDelUsuario = $usr->getRoleEntities(); // Obtiene Collection de Role
                    
                    if ($rolesDelUsuario && count($rolesDelUsuario) > 0) {
                        foreach ($rolesDelUsuario as $role) {
                            if ($role->getIsActive()) {
                                $nombreRol = $role->getDisplayName(); // Nombre amigable del rol
                                if (!isset($usuariosPorRol[$nombreRol])) {
                                    $usuariosPorRol[$nombreRol] = [];
                                }
                                // Agregar el usuario bajo este rol
                                $usuariosPorRol[$nombreRol][] = $usr;
                            }
                        }
                    } else {
                        // Usuario sin roles definidos
                        if (!isset($usuariosPorRol['Sin rol'])) {
                            $usuariosPorRol['Sin rol'] = [];
                        }
                        $usuariosPorRol['Sin rol'][] = $usr;
                    }
                }
            }
            
            // Ordenar alfabéticamente por nombre de rol
            ksort($usuariosPorRol);
            
            // La estructura ya está lista para ChoiceType con grupos
            // Formato: ['Nombre Rol' => [usuario1, usuario2, ...], ...]
            $docArr = $usuariosPorRol;
        }
        $evolucion = new Evolucion();

        $cliente = $clienteRepository->find($request->get('cliente'));
        if($cliente->getDerivado() and !$puedenEditarEvoluciones) die('paciente derivado, no se puede evolucionar');
        //solo activos
        $evolucion->setPaciente($cliente);
        $evolucion->setUser($user->getEmail());
        $evolucion->setFecha(new \DateTime());
        
        // Asignar tipo automáticamente basado en el usuario actual (antes de crear el form)
        // Esto es necesario para que pase la validación NotBlank
        $evolucion->setTipo($user->getTipoProfesional());

        // Modalidades deprecado - siempre será vacío con el nuevo sistema
        // Los usuarios deben seleccionar el tipo de evolución manualmente
        $modalidad = '';

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
                
                // Asignar tipo automáticamente basado en el rol del usuario que evoluciona
                if ($doctorQueFirma) {
                    $evolucion->setTipo($doctorQueFirma->getTipoProfesional());
                    
                    // Guardar datos del doctor que firma
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
                
                // Validar presente para pacientes ambulatorios
                if ($cliente->getAmbulatorio()) {
                    $fechaEvolucion = clone $evolucion->getFecha();
                    $fechaEvolucion->setTime(0, 0, 0); // Normalizar a inicio del día
                    
                    // Verificar si el paciente tiene presente en la fecha de la evolución
                    $presentes = $presentesRepository->findByFechaCliente($fechaEvolucion, $cliente);
                    $tienePresente = false;
                    
                    foreach ($presentes as $presente) {
                        if ($presente->getValor() === true) {
                            $tienePresente = true;
                            break;
                        }
                    }
                    
                    // Si no tiene presente y el doctor no tiene permiso especial, rechazar
                    if (!$tienePresente && !$this->isGranted('patient.evolve_without_presence')) {
                        $error = 'No se puede evolucionar a un paciente ambulatorio sin presente. El paciente debe tener presente registrado en la fecha de la evolución (' . $fechaEvolucion->format('d/m/Y') . '). Si necesitas evolucionar sin presente, contacta al administrador para obtener el permiso "Evolucionar sin Presente".';
                        return $this->render('evolucion/new.html.twig', [
                            'evolucion' => $evolucion,
                            'nombreCliente' => $cliente->getNombre() . ' ' . $cliente->getApellido(),
                            'form' => $form->createView(),
                            'clienteId' => $cliente->getId(),
                            'error' => $error,
                        ]);
                    }
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
    public function show($id, EvolucionRepository $evolucionRepository, DoctorRepository $doctorRepository, \App\Repository\UserRepository $userRepository): Response
    {
        $evolucion = $evolucionRepository->find($id);
        $firma = '';
        $doctorData = ['nombre' => '', 'apellido' => '', 'matricula' => ''];
        
        if ($evolucion && $evolucion->getUser()) {
            // 1. PRIMERO: Verificar si la evolución ya tiene datos de firma guardados (nuevo sistema)
            if ($evolucion->getFirmaDoctorPath()) {
                $firma = $evolucion->getFirmaDoctorPath();
                $doctorData['nombre'] = $evolucion->getFirmaDoctorNombre() ?: '';
                $doctorData['apellido'] = $evolucion->getFirmaDoctorApellido() ?: '';
                $doctorData['matricula'] = $evolucion->getFirmaDoctorMatricula() ?: '';
            } else {
                // 2. SEGUNDO: Buscar firma activa del usuario en tabla User
                $userDoctor = $userRepository->findOneBy(['email' => $evolucion->getUser()]);
                if ($userDoctor) {
                    // Obtener datos del usuario
                    $doctorData['nombre'] = $userDoctor->getNombre() ?: '';
                    $doctorData['apellido'] = $userDoctor->getApellido() ?: '';
                    $doctorData['matricula'] = $userDoctor->getLegajo() ?: '';
                    
                    // Buscar firma activa en las firmas del usuario
                    $firmas = $userDoctor->getFirmas();
                    if ($firmas && count($firmas) > 0) {
                        foreach ($firmas as $firmaItem) {
                            if ($firmaItem->getIsActive() && $firmaItem->getFilePath()) {
                                $firma = $firmaItem->getFilePath();
                                break; // Solo necesitamos la primera firma activa
                            }
                        }
                    }
                }
                
                // 3. TERCERO: Si no encontró firma en User, buscar en Doctor (sistema viejo)
                if (empty($firma)) {
                    $doctorObj = $doctorRepository->findOneBy(['email' => $evolucion->getUser()]);
                    if ($doctorObj) {
                        $firmaDoctor = $doctorObj->getFirma();
                        if ($firmaDoctor) {
                            $firma = $firmaDoctor;
                        }
                        
                        // Si no obtuvimos datos de User, usar los de Doctor
                        if (empty($doctorData['nombre'])) {
                            $doctorData['nombre'] = $doctorObj->getNombre() ?: '';
                        }
                        if (empty($doctorData['apellido'])) {
                            $doctorData['apellido'] = $doctorObj->getApellido() ?: '';
                        }
                        if (empty($doctorData['matricula'])) {
                            $doctorData['matricula'] = $doctorObj->getLegajo() ?: '';
                        }
                    }
                }
            }
        }
        
        return $this->render('evolucion/show.html.twig', [
            'evolucion' => $evolucion,
            'firma' => $firma,
            'doctorData' => $doctorData,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="evolucion_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evolucion $evolucion, DoctorRepository $doctorRepository, \App\Repository\UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $usuarioActual = $evolucion->getUser();

        // Modalidades deprecado - siempre será vacío
        $modalidad = '';

        // Obtener usuarios agrupados por roles (igual que en new)
        $docArr = [];
        $puedenEditarEvoluciones = $this->isGranted('patient.edit_evolve');
        
        if ($puedenEditarEvoluciones) {
            // Obtener todos los usuarios que tienen permiso para evolucionar
            $todosUsuarios = $userRepository->findAll();
            
            // Agrupar usuarios por roles (un usuario puede aparecer en múltiples grupos)
            $usuariosPorRol = [];
            
            foreach ($todosUsuarios as $usr) {
                // Filtrar solo usuarios que tengan permiso patient.evolve
                if ($usr->hasPermission('patient.evolve')) {
                    // Obtener todos los roles activos del usuario
                    $rolesDelUsuario = $usr->getRoleEntities();
                    
                    if ($rolesDelUsuario && count($rolesDelUsuario) > 0) {
                        foreach ($rolesDelUsuario as $role) {
                            if ($role->getIsActive()) {
                                $nombreRol = $role->getDisplayName();
                                if (!isset($usuariosPorRol[$nombreRol])) {
                                    $usuariosPorRol[$nombreRol] = [];
                                }
                                $usuariosPorRol[$nombreRol][] = $usr;
                            }
                        }
                    } else {
                        if (!isset($usuariosPorRol['Sin rol'])) {
                            $usuariosPorRol['Sin rol'] = [];
                        }
                        $usuariosPorRol['Sin rol'][] = $usr;
                    }
                }
            }
            
            ksort($usuariosPorRol);
            $docArr = $usuariosPorRol;
        }

        $redirect = $request->get('redirect', '');

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
}
