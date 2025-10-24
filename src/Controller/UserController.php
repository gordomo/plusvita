<?php

namespace App\Controller;

use App\Entity\User;
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
            
            if ($form->isValid()) {
            
                try {
                    // Setear username = email para compatibilidad con UserInterface
                    $user->setUsername($user->getEmail());

                    $password = $form->get('password')->getData() ?? '';
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
                    
                    $this->addFlash('success', 'Usuario creado correctamente.');
                    return $this->redirectToRoute('user_management_index');
                } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                    // Determinar qué campo causa la violación
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $this->addFlash('error', 'Ya existe un usuario registrado con este email.');
                    } else {
                        $this->addFlash('error', 'Error: Datos duplicados. Por favor, verifica los datos ingresados.');
                    }
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al crear el usuario: ' . $e->getMessage());
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                    ]);
                }
                }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            
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
}
