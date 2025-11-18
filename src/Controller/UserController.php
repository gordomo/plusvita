<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Doctor;
use App\Form\UserType;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar permisos - solo administradores pueden ver la lista de usuarios
        if (!$this->isGranted('user.read')) {
            throw $this->createAccessDeniedException('No tienes permiso para acceder a este recurso');
        }
        
        // Make sure we get all users, sorted by username for better organization
        $allUsers = $userRepository->findBy([], ['username' => 'ASC']);
        
        return $this->render('user/index.html.twig', [
            'users' => $allUsers,
            'paginaImprimible' => true,
            'isDoctor' => $this->isGranted('doctor.read'),
            'currentUser' => $user
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $user->setHabilitado(true);
        
        $form = $this->createForm(UserType::class, $user);
        
        // Populate the unmapped roleEntities field with current user roles
        $form->get('roleEntities')->setData($user->getRoleEntities());

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Verificar si el email ya existe ANTES de validar el formulario
            if ($user->getEmail()) {
                $existingUser = $this->getDoctrine()->getRepository(User::class)
                    ->findOneBy(['email' => $user->getEmail()]);
                
                if ($existingUser) {
                    $form->get('email')->addError(new FormError('Ya existe un usuario registrado con este email.'));
                }
            }
            
            // Debug: mostrar errores de validación
            if (!$form->isValid()) {
                $errors = [];
                // getErrors(true) devuelve un array plano de FormError
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                if (!empty($errors)) {
                    $this->addFlash('error', 'Errores de validación: ' . implode(', ', $errors));
                }
                
                // Verificar errores específicos por campo
                if (!$form->get('roleEntities')->isValid()) {
                    $roleErrors = [];
                    foreach ($form->get('roleEntities')->getErrors(true) as $error) {
                        $roleErrors[] = $error->getMessage();
                    }
                    if (!empty($roleErrors)) {
                        $this->addFlash('error', 'Roles: ' . implode(', ', $roleErrors));
                    } else {
                        $this->addFlash('error', 'Debe seleccionar al menos un rol.');
                    }
                }
            }
            
            if ($form->isValid()) {
            
                try {
                    // Setear username = email para compatibilidad con UserInterface
                    $user->setUsername($user->getEmail());

                    $password = $form->get('password')->getData() ?? '';
                    if (empty($password)) {
                        $this->addFlash('error', 'La contraseña es obligatoria.');
                        $businessHoursData = $this->extractBusinessHoursData($request);
                        return $this->render('user/new.html.twig', [
                            'user' => $user,
                            'form' => $form->createView(),
                            'businessHoursData' => $businessHoursData
                        ]);
                    }
                    $encodePass = $passwordEncoder->encodePassword($user, $password);
                    $user->setPassword($encodePass);
                    
                    // Mantener modalidad vacía por compatibilidad
                    $user->setModalidad([]);
                    
                    // Asignar roles seleccionados
                    $selectedRoles = $form->get('roleEntities')->getData();
                    
                    // Agregar roles seleccionados
                    foreach ($selectedRoles as $role) {
                        $user->addRole($role);
                    }
                    
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    // Crear o actualizar contrato si se proporcionaron datos
                    try {
                        $this->handleUserContract($user, $form, $entityManager);
                    } catch (\Exception $e) {
                        // Si falla la creación del contrato, continuar pero registrar el error
                        $this->addFlash('warning', 'El usuario se creó correctamente, pero hubo un problema al guardar los datos del contrato. Puede editarlos más tarde.');
                    }
                    
                    // Si se marcó "completar info para doctor", crear registro en tabla doctor
                    $completarInfoDoctor = $form->get('completarInfoDoctor')->getData();
                    if ($completarInfoDoctor) {
                        try {
                            $doctor = $this->createDoctorFromForm($user, $form, $request);
                            if ($doctor) {
                                $entityManager->persist($doctor);
                                $entityManager->flush();
                            }
                        } catch (\Exception $e) {
                            // Si falla la creación del doctor, continuar pero registrar el error
                            $errorMsg = 'El usuario se creó correctamente, pero hubo un problema al guardar los datos del doctor. Puede editarlos más tarde.';
                            if ($this->getParameter('kernel.environment') === 'dev') {
                                $errorMsg .= ' Error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
                            }
                            $this->addFlash('warning', $errorMsg);
                        }
                    }
                    
                    $this->addFlash('success', 'Usuario creado correctamente.');
                    return $this->redirectToRoute('user_management_index');
                } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                    // Determinar qué campo causa la violación
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $this->addFlash('error', 'Ya existe un usuario registrado con este email.');
                    } else {
                        $this->addFlash('error', 'Error: Datos duplicados. Por favor, verifica los datos ingresados.');
                    }
                    $businessHoursData = $this->extractBusinessHoursData($request);
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                        'businessHoursData' => $businessHoursData
                    ]);
                } catch (\Doctrine\DBAL\Exception\DriverException $e) {
                    // Errores de base de datos (columnas no encontradas, etc.)
                    $errorMessage = 'Error en la base de datos. Por favor, contacte al administrador del sistema.';
                    if ($this->getParameter('kernel.environment') === 'dev') {
                        $errorMessage = 'Error de base de datos: ' . $e->getMessage();
                    }
                    $this->addFlash('error', $errorMessage);
                    
                    $businessHoursData = $this->extractBusinessHoursData($request);
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                        'businessHoursData' => $businessHoursData
                    ]);
                } catch (\Exception $e) {
                    // Mensaje de error más amigable para el usuario
                    $errorMessage = 'Error al crear el usuario. Por favor, verifique los datos ingresados.';
                    if ($this->getParameter('kernel.environment') === 'dev') {
                        $errorMessage = 'Error al crear el usuario: ' . $e->getMessage() . ' (' . get_class($e) . ')';
                    }
                    
                    $this->addFlash('error', $errorMessage);
                    
                    // Extraer datos de horarios del request para restaurarlos
                    $businessHoursData = $this->extractBusinessHoursData($request);
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                        'businessHoursData' => $businessHoursData
                    ]);
                }
                }
        }

        // Extraer datos de horarios del request si hay errores de validación
        $businessHoursData = [];
        if ($form->isSubmitted() && !$form->isValid()) {
            $businessHoursData = $this->extractBusinessHoursData($request);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'businessHoursData' => $businessHoursData
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
            
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if (!$this->isGranted('user.update')) {
            throw $this->createAccessDeniedException('No tienes permisos para editar usuarios');
        }

        $oldPassword = $user->getPassword();
        $oldEmail = $user->getEmail();

        $form = $this->createForm(UserType::class, $user);
        
        // Populate the unmapped roleEntities field with current user roles
        $form->get('roleEntities')->setData($user->getRoleEntities());
        
        // Cargar datos del doctor si existe (por email)
        // Usar DQL directo para evitar problemas con campos eliminados
        $doctor = null;
        try {
            $doctor = $this->getDoctrine()->getRepository(Doctor::class)
                ->createQueryBuilder('d')
                ->where('d.email = :email')
                ->setParameter('email', $user->getEmail())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $e) {
            // Si hay error al buscar doctor, continuar sin datos de doctor
            // Esto puede pasar si hay problemas con campos eliminados
        }
        
        if ($doctor) {
            // Marcar el checkbox como marcado
            $form->get('completarInfoDoctor')->setData(true);
            
            // Prellenar campos de doctor
            $form->get('doctor_matricula')->setData($doctor->getMatricula());
            $form->get('doctor_vtoMatricula')->setData($doctor->getVtoMatricula());
            // Los datos de contrato se obtienen desde UserContract, no desde Doctor
            $form->get('doctor_max_cli_turno')->setData($doctor->getMaxCliTurno());
            $form->get('doctor_color')->setData($doctor->getColor());
            
            // Los businessHours se cargarán dinámicamente con JavaScript desde la nueva estructura
            // No necesitamos prellenar campos antiguos ya que usamos la nueva interfaz dinámica
        }
        
        // Prellenar campos de contrato si existe un contrato activo
        $activeContract = $user->getActiveContract();
        if ($activeContract) {
            $form->get('contract_tipo')->setData($activeContract->getTipo());
            $form->get('contract_inicioContrato')->setData($activeContract->getInicioContrato());
            $form->get('contract_vtoContrato')->setData($activeContract->getVtoContrato());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verificar si el email ya existe (solo si realmente cambió el email)
            $newEmail = $form->get('email')->getData();
            if ($oldEmail !== $newEmail) {
                $existingUser = $this->getDoctrine()->getRepository(User::class)
                    ->findOneBy(['email' => $newEmail]);
                
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Ya existe un usuario registrado con este email.');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                    ]);
                }
            }
            
            // El formulario es válido, proceder con la actualización
            try {
                // Actualizar username = email para mantener sincronía
                $user->setUsername($user->getEmail());

                // Manejar la contraseña - solo si se ingresó una nueva
                $passwordData = $form->get('password')->getData();
                if ($passwordData && $passwordData !== 'noPass' && !empty($passwordData)) {
                    $encodePass = $passwordEncoder->encodePassword($user, $passwordData);
                    $user->setPassword($encodePass);
                } else {
                    // Mantener la contraseña anterior
                    $user->setPassword($oldPassword);
                }
                
                // Mantener modalidad vacía por compatibilidad
                $user->setModalidad([]);

                // Asignar roles seleccionados
                $selectedRoles = $form->get('roleEntities')->getData();
                
                // Obtener los roles actuales
                $currentRoles = $user->getRoleEntities()->toArray();
                
                // Remover roles que ya no están seleccionados
                foreach ($currentRoles as $role) {
                    if (!$selectedRoles->contains($role)) {
                        $user->removeRole($role);
                    }
                }
                
                // Agregar nuevos roles seleccionados
                foreach ($selectedRoles as $role) {
                    if (!$user->getRoleEntities()->contains($role)) {
                        $user->addRole($role);
                    }
                }

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Crear o actualizar contrato si se proporcionaron datos
                $this->handleUserContract($user, $form, $entityManager);
                
                // Si se marcó "completar info para doctor", crear/actualizar registro en tabla doctor
                $completarInfoDoctor = $form->get('completarInfoDoctor')->getData();
                if ($completarInfoDoctor) {
                    $doctor = $this->createDoctorFromForm($user, $form, $request);
                    if ($doctor) {
                        $entityManager->persist($doctor);
                        $entityManager->flush();
                    }
                }
                
                $this->addFlash('success', 'Usuario actualizado correctamente.');
                return $this->redirectToRoute('user_management_index');

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Ya existe un usuario registrado con este email.');
                return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al actualizar el usuario: ' . $e->getMessage());
                return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user_to_delete, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if($user && $this->isGranted('user.delete')) {
            if ($this->isCsrfTokenValid('delete'.$user_to_delete->getId(), $request->request->get('_token'))) {
                $entityManager = $this->getDoctrine()->getManager();
                
                $bookingsDelUsuario = $bookingRepository->findBy(['user' => $user_to_delete]);
                foreach ( $bookingsDelUsuario as $book ) {
                    $book->setUser($user);
                }
                $entityManager->remove($user_to_delete);
                $entityManager->flush();
            }    
        } else {
            die('el usuario no tiene los permisos suficientes para borrar otro usuario');
        }

        

        return $this->redirectToRoute('user_index');
    }

    /**
     * Crea un registro Doctor desde los datos del formulario de usuario
     */
    private function createDoctorFromForm(User $user, $form, $request = null): ?Doctor
    {
        // Verificar si ya existe un doctor con este email
        // Buscar doctor existente usando DQL para evitar problemas con campos eliminados
        $existingDoctor = null;
        try {
            $existingDoctor = $this->getDoctrine()->getRepository(Doctor::class)
                ->createQueryBuilder('d')
                ->where('d.email = :email')
                ->setParameter('email', $user->getEmail())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $e) {
            // Si hay error, crear nuevo doctor
        }
        
        if ($existingDoctor) {
            // Actualizar doctor existente
            $doctor = $existingDoctor;
        } else {
            // Crear nuevo doctor
            $doctor = new Doctor();
            $doctor->setEmail($user->getEmail());
        }
        
        // Sincronizar datos básicos desde User
        $doctor->setNombre($user->getNombre() ?? '');
        $doctor->setApellido($user->getApellido() ?? '');
        $doctor->setTelefono($user->getTelefono());
        $doctor->setLegajo($user->getLegajo());
        $doctor->setHabilitado($user->getHabilitado() ?? true);
        
        // Establecer valores por defecto requeridos
        $doctor->setEspecialidad([]);
        // legacyRoles ya se inicializa en el constructor con ['ROLE_STAFF'], no necesita establecerse
        $doctor->setUsername($user->getEmail());
        // Password: establecer un password temporal (el método setPassword lo codifica automáticamente)
        // Nota: El Doctor entity es legacy y no se usa para autenticación, solo para datos adicionales
        if (!$doctor->getPassword()) {
            // Solo establecer password si no existe (para actualizaciones)
            $doctor->setPassword('temp_password_not_used');
        }
        $doctor->setPresente(false);
        
        // Campos específicos de doctor del formulario
        if ($form->has('doctor_matricula')) {
            $matricula = $form->get('doctor_matricula')->getData();
            $doctor->setMatricula($matricula);
        }
        
        if ($form->has('doctor_vtoMatricula')) {
            $doctor->setVtoMatricula($form->get('doctor_vtoMatricula')->getData());
        }
        
        // Los datos de contrato se gestionan a través de UserContract, no aquí
        // El campo tipo ya no existe en Doctor, se maneja en UserContract
        
        if ($form->has('doctor_max_cli_turno')) {
            $doctor->setMaxCliTurno($form->get('doctor_max_cli_turno')->getData());
        }
        
        if ($form->has('doctor_color')) {
            $doctor->setColor($form->get('doctor_color')->getData());
        }
        
        // Construir businessHours desde los campos de horarios
        // Nueva estructura: soporta múltiples rangos por día y horarios que cruzan medianoche
        $dias = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];
        
        $businessHours = [];
        
        foreach ($dias as $key => $dia) {
            // Intentar obtener la nueva estructura (rangos múltiples)
            $rangesData = null;
            if ($request) {
                $formData = $request->request->get('user', []);
                $rangesData = $formData['doctor_' . $dia . '_ranges'] ?? null;
            }
            
            if ($rangesData && is_array($rangesData)) {
                // Nueva estructura: múltiples rangos
                $ranges = [];
                foreach ($rangesData as $rangeData) {
                    if (isset($rangeData['start']) && !empty($rangeData['start'])) {
                        $start = $rangeData['start'];
                        $end = null;
                        $nextDay = false;
                        
                        // Si cruza medianoche, usar endNextDay, sino usar end normal
                        if (isset($rangeData['nextDay']) && $rangeData['nextDay'] && 
                            isset($rangeData['endNextDay']) && !empty($rangeData['endNextDay'])) {
                            $end = $rangeData['endNextDay'];
                            $nextDay = true;
                        } elseif (isset($rangeData['end']) && !empty($rangeData['end'])) {
                            $end = $rangeData['end'];
                        }
                        
                        // Solo agregar si tenemos start y end válidos
                        if ($start && $end) {
                            $range = [
                                'start' => $start,
                                'end' => $end,
                            ];
                            
                            // Si el horario cruza medianoche
                            if ($nextDay) {
                                $range['nextDay'] = true;
                            }
                            
                            $ranges[] = $range;
                        }
                    }
                }
                
                if (!empty($ranges)) {
                    $businessHours[$key] = $ranges;
                }
            }
        }
        
        if (!empty($businessHours)) {
            $doctor->setBusinessHours($businessHours);
        }
        
        // Valores por defecto
        $doctor->setPresente(false);
        
        return $doctor;
    }
    
    /**
     * Extrae los datos de businessHours del request para restaurarlos en el formulario
     */
    private function extractBusinessHoursData(Request $request): array
    {
        $businessHoursData = [];
        $dias = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];
        
        // Los campos pueden estar directamente en el request o dentro de 'user'
        $allRequestData = $request->request->all();
        
        foreach ($dias as $key => $dia) {
            $rangesData = null;
            
            // Intentar obtener desde el nivel raíz del request
            $fieldName = 'doctor_' . $dia . '_ranges';
            if (isset($allRequestData[$fieldName]) && is_array($allRequestData[$fieldName])) {
                $rangesData = $allRequestData[$fieldName];
            } else {
                // Intentar obtener desde dentro de 'user'
                $formData = $request->request->get('user', []);
                if (isset($formData[$fieldName]) && is_array($formData[$fieldName])) {
                    $rangesData = $formData[$fieldName];
                }
            }
            
            if ($rangesData && is_array($rangesData)) {
                // Filtrar solo los rangos que tienen datos válidos
                $validRanges = [];
                foreach ($rangesData as $rangeData) {
                    if (is_array($rangeData) && isset($rangeData['start']) && !empty($rangeData['start'])) {
                        $validRanges[] = $rangeData;
                    }
                }
                if (!empty($validRanges)) {
                    $businessHoursData[$dia] = $validRanges;
                }
            }
        }
        
        return $businessHoursData;
    }
    
    /**
     * Maneja la creación o actualización del contrato del usuario
     */
    private function handleUserContract(User $user, $form, $entityManager): void
    {
        $tipo = $form->get('contract_tipo')->getData();
        $inicioContrato = $form->get('contract_inicioContrato')->getData();
        $vtoContrato = $form->get('contract_vtoContrato')->getData();
        
        // Solo crear/actualizar contrato si se proporcionó al menos tipo e inicio
        if ($tipo && $tipo !== '0' && $inicioContrato) {
            // Buscar contrato activo existente
            $activeContract = $user->getActiveContract();
            
            if ($activeContract) {
                // Actualizar contrato existente
                $activeContract->setTipo($tipo);
                $activeContract->setInicioContrato($inicioContrato);
                $activeContract->setVtoContrato($vtoContrato);
                $activeContract->setUpdatedAt(new \DateTime());
            } else {
                // Crear nuevo contrato
                $contract = new \App\Entity\UserContract();
                $contract->setUser($user);
                $contract->setTipo($tipo);
                $contract->setInicioContrato($inicioContrato);
                $contract->setVtoContrato($vtoContrato);
                $contract->setIsActive(true);
                $contract->setCreatedAt(new \DateTime());
                
                $entityManager->persist($contract);
            }
            
            $entityManager->flush();
        }
    }
}
