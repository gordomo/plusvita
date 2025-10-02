<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Doctor;
use App\Entity\Nurse;
use App\Entity\Role;
use App\Service\AuthorizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/users")
 */
class UserManagementController extends AbstractController
{
    private $entityManager;
    private $authorizationService;
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationService $authorizationService,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationService = $authorizationService;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/", name="user_management_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        // Verificar permisos (temporalmente comentado para debug)
        // if (!$this->authorizationService->hasPermission('user.read')) {
        //     throw $this->createAccessDeniedException('No tienes permisos para ver usuarios');
        // }

        // Obtener parámetros de filtro
        $userType = $request->query->get('type', 'all');
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20; // Usuarios por página

        // Obtener usuarios
        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.roles', 'r')
            ->addSelect('r');

        // Filtrar por tipo de usuario si no es 'all'
        if ($userType !== 'all') {
            $queryBuilder
                ->andWhere('r.id = :roleId')
                ->setParameter('roleId', $userType);
        }

        $allUsers = $queryBuilder->getQuery()->getResult();

        // Aplicar filtros
        $filteredUsers = $this->applyFilters($allUsers, $search, $status);

        // Paginación
        $totalUsers = count($filteredUsers);
        $totalPages = ceil($totalUsers / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedUsers = array_slice($filteredUsers, $offset, $limit);

        // Obtener roles disponibles
        $roles = $this->entityManager->getRepository(Role::class)->findBy(['isActive' => true]);

        return $this->render('admin/users/index.html.twig', [
            'users' => $paginatedUsers,
            'roles' => $roles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'availableRoles' => $this->authorizationService->getRoles(),
            'filters' => [
                'type' => $userType,
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    private function applyFilters(array $users, string $search, string $status): array
    {
        return array_filter($users, function ($user) use ($search, $status) {
            // Filtro de búsqueda
            if ($search) {
                $searchLower = strtolower($search);
                $email = $user->getEmail() ?? '';
                
                if ($user->userType === 'admin') {
                    // Para usuarios admin, buscar en username, nombre, apellido y email
                    $username = $user->getUsername() ?? '';
                    $nombre = $user->getNombre() ?? '';
                    $apellido = $user->getApellido() ?? '';
                    $fullName = trim($nombre . ' ' . $apellido);
                    
                    if (strpos(strtolower($username), $searchLower) === false && 
                        strpos(strtolower($fullName), $searchLower) === false && 
                        strpos(strtolower($email), $searchLower) === false) {
                        return false;
                    }
                } else {
                    // Para doctores y enfermeros, buscar en nombre completo y email
                    $name = ($user->getNombre() ?? '') . ' ' . ($user->getApellido() ?? '');
                    
                    if (strpos(strtolower($name), $searchLower) === false && 
                        strpos(strtolower($email), $searchLower) === false) {
                        return false;
                    }
                }
            }

            // Filtro de estado
            if ($status !== 'all') {
                $isActive = $user->habilitado ?? true;
                if (($status === 'active' && !$isActive) || 
                    ($status === 'inactive' && $isActive)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @Route("/{type}/{id}/roles", name="user_management_edit_roles", methods={"GET", "POST"})
     */
    public function editRoles(Request $request, string $type, int $id): Response
    {
        // Verificar permisos
        if (!$this->authorizationService->hasPermission('user.update')) {
            throw $this->createAccessDeniedException('No tienes permisos para editar usuarios');
        }

        // Obtener el usuario según el tipo
        $user = $this->getUserByType($type, $id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        // Obtener roles disponibles
        $availableRoles = $this->entityManager->getRepository(Role::class)->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $selectedRoles = $request->request->get('roles', []);
            
            // Limpiar roles existentes
            foreach ($user->getRoleEntities() as $role) {
                $user->removeRole($role);
            }

            // Agregar nuevos roles
            foreach ($selectedRoles as $roleId) {
                $role = $this->entityManager->getRepository(Role::class)->find($roleId);
                if ($role) {
                    $user->addRole($role);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Roles actualizados correctamente');
            return $this->redirectToRoute('user_management_index');
        }

        return $this->render('admin/users/edit_roles.html.twig', [
            'user' => $user,
            'userType' => $type,
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * @Route("/create/{type}", name="user_management_create", methods={"GET", "POST"})
     */
    public function create(Request $request, string $type): Response
    {
        // Verificar permisos
        if (!$this->authorizationService->hasPermission('user.create')) {
            throw $this->createAccessDeniedException('No tienes permisos para crear usuarios');
        }

        // Crear usuario según el tipo
        $user = $this->createUserByType($type);

        if ($request->isMethod('POST')) {
            try {
                $email = $request->request->get('email');
                
                // Verificar si el email ya existe
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);
                
                if ($existingUser) {
                    $this->addFlash('error', 'Ya existe un usuario registrado con este email.');
                    return $this->render('admin/users/create.html.twig', [
                        'userType' => $type,
                        'availableRoles' => $this->entityManager->getRepository(Role::class)->findBy(['isActive' => true]),
                        'lastData' => $request->request->all() // Para mantener los datos del formulario
                    ]);
                }

                $user->setEmail($email);
                $user->setUsername($request->request->get('username'));
                $user->setPassword($request->request->get('password'));

                // Agregar roles seleccionados
                $selectedRoles = $request->request->get('roles', []);
                foreach ($selectedRoles as $roleId) {
                    $role = $this->entityManager->getRepository(Role::class)->find($roleId);
                    if ($role) {
                        $user->addRole($role);
                    }
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', 'Usuario creado correctamente');
                return $this->redirectToRoute('user_management_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Ha ocurrido un error al crear el usuario. Por favor, verifica los datos e intenta nuevamente.');
                return $this->render('admin/users/create.html.twig', [
                    'userType' => $type,
                    'availableRoles' => $this->entityManager->getRepository(Role::class)->findBy(['isActive' => true]),
                    'lastData' => $request->request->all() // Para mantener los datos del formulario
                ]);
            }
        }

        $availableRoles = $this->entityManager->getRepository(Role::class)->findBy(['isActive' => true]);

        return $this->render('admin/users/create.html.twig', [
            'userType' => $type,
            'availableRoles' => $availableRoles,
        ]);
    }

    private function getUserByType(string $type, int $id)
    {
        switch ($type) {
            case 'admin':
                return $this->entityManager->getRepository(User::class)->find($id);
            case 'doctor':
                return $this->entityManager->getRepository(Doctor::class)->find($id);
            case 'nurse':
                return $this->entityManager->getRepository(Nurse::class)->find($id);
            default:
                return null;
        }
    }

    private function createUserByType(string $type)
    {
        switch ($type) {
            case 'admin':
                return new User();
            case 'doctor':
                return new Doctor();
            case 'nurse':
                return new Nurse();
            default:
                throw new \InvalidArgumentException('Tipo de usuario no válido');
        }
    }
}
