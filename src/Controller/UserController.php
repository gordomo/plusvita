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

        if ($form->isSubmitted() && $form->isValid()) {
            
            try {
                $password = $form->get('password')->getData() ?? '';
                $encodePass = $passwordEncoder->encodePassword($user, $password);
                $user->setPassword($encodePass);
                
                // Handle the unmapped roleEntities field
                $selectedRoles = $form->get('roleEntities')->getData();
                
                
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Limpiar relaciones existentes y guardar las nuevas
                $connection = $entityManager->getConnection();
                
                // Eliminar todas las relaciones existentes para este usuario
                $connection->executeStatement(
                    'DELETE FROM user_roles WHERE user_id = ?',
                    [$user->getId()]
                );
                
                // Agregar las nuevas relaciones
                foreach ($selectedRoles as $role) {
                    $connection->executeStatement(
                        'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)',
                        [$user->getId(), $role->getId()]
                    );
                }
                
                $this->addFlash('success', 'Usuario creado correctamente.');
                return $this->redirectToRoute('user_management_index');
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al crear el usuario: ' . $e->getMessage());
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
        $oldPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user);
        
        // Populate the unmapped roleEntities field with current user roles
        $form->get('roleEntities')->setData($user->getRoleEntities());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('password')->getData() == 'noPass') {
                $user->setPassword($oldPassword);
            } else {
                $password = $form->get('password')->getData() ?? '';
                $encodePass = $passwordEncoder->encodePassword($user, $password);
                $user->setPassword($encodePass);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            
            // Handle the unmapped roleEntities field - Limpiar y guardar relaciones manualmente
            $selectedRoles = $form->get('roleEntities')->getData();
            $connection = $entityManager->getConnection();
            
            // Eliminar todas las relaciones existentes para este usuario
            $connection->executeStatement(
                'DELETE FROM user_roles WHERE user_id = ?',
                [$user->getId()]
            );
            
            // Agregar las nuevas relaciones
            foreach ($selectedRoles as $role) {
                $connection->executeStatement(
                    'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)',
                    [$user->getId(), $role->getId()]
                );
            }

            return $this->redirectToRoute('user_management_index');
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
