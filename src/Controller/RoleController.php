<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Permission;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Service\AuthorizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/roles")
 */
class RoleController extends AbstractController
{
    private $authorizationService;

    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * @Route("/", name="role_index", methods={"GET"})
     */
    public function index(RoleRepository $roleRepository): Response
    {
        // Verificar que el usuario sea administrador
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        $roles = $roleRepository->findWithPermissions();
        
        // Agrupar roles por categoría
        $rolesByCategory = [];
        $categories = Role::getCategories();
        
        // Inicializar arrays para cada categoría
        foreach ($categories as $categoryKey => $categoryName) {
            $rolesByCategory[$categoryKey] = [
                'name' => $categoryName,
                'roles' => []
            ];
        }
        
        // Agregar categoría "Sin categoría" para roles sin categoría asignada
        $rolesByCategory['uncategorized'] = [
            'name' => 'Sin Categoría',
            'roles' => []
        ];
        
        // Agrupar roles por categoría
        foreach ($roles as $role) {
            $category = $role->getCategory();
            if ($category && isset($rolesByCategory[$category])) {
                $rolesByCategory[$category]['roles'][] = $role;
            } else {
                $rolesByCategory['uncategorized']['roles'][] = $role;
            }
        }
        
        // Eliminar categorías vacías
        $rolesByCategory = array_filter($rolesByCategory, function($categoryData) {
            return !empty($categoryData['roles']);
        });

        return $this->render('role/index.html.twig', [
            'rolesByCategory' => $rolesByCategory,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/new", name="role_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rol creado correctamente.');
            return $this->redirectToRoute('role_index');
        }

        return $this->render('role/new.html.twig', [
            'role' => $role,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="role_show", methods={"GET"})
     */
    public function show(Role $role): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        return $this->render('role/show.html.twig', [
            'role' => $role,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="role_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Rol actualizado correctamente.');
            return $this->redirectToRoute('role_index');
        }

        return $this->render('role/edit.html.twig', [
            'role' => $role,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="role_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        if ($role->getIsSystem()) {
            $this->addFlash('error', 'No se puede eliminar un rol del sistema.');
            return $this->redirectToRoute('role_index');
        }

        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->request->get('_token'))) {
            $entityManager->remove($role);
            $entityManager->flush();
            $this->addFlash('success', 'Rol eliminado correctamente.');
        }

        return $this->redirectToRoute('role_index');
    }

    /**
     * @Route("/{id}/permissions", name="role_permissions", methods={"GET","POST"})
     */
    public function permissions(Request $request, Role $role, PermissionRepository $permissionRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('role.update');

        if ($request->isMethod('POST')) {
            $permissionIds = $request->request->get('permissions', []);
            
            // Limpiar permisos actuales
            foreach ($role->getPermissions() as $permission) {
                $role->removePermission($permission);
            }
            
            // Agregar nuevos permisos
            foreach ($permissionIds as $permissionId) {
                $permission = $permissionRepository->find($permissionId);
                if ($permission) {
                    $role->addPermission($permission);
                }
            }
            
            $role->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            
            $this->addFlash('success', 'Permisos del rol actualizados correctamente.');
            return $this->redirectToRoute('role_show', ['id' => $role->getId()]);
        }

        $permissionsByCategory = $this->authorizationService->getPermissionsByCategory();
        $rolePermissionIds = [];
        foreach ($role->getPermissions() as $permission) {
            $rolePermissionIds[] = $permission->getId();
        }

        return $this->render('role/permissions.html.twig', [
            'role' => $role,
            'permissionsByCategory' => $permissionsByCategory,
            'rolePermissionIds' => $rolePermissionIds,
        ]);
    }

    /**
     * @Route("/dashboard", name="role_dashboard", methods={"GET"})
     */
    public function dashboard(RoleRepository $roleRepository, PermissionRepository $permissionRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acceso denegado. Solo los administradores pueden acceder a esta sección.');
        }

        // Estadísticas básicas
        $totalUsers = $entityManager->createQuery('SELECT COUNT(u) FROM App\Entity\User u WHERE u.habilitado = true')->getSingleScalarResult();
        $totalRoles = $roleRepository->count(['isActive' => true]);
        $totalPermissions = $permissionRepository->count(['isActive' => true]);
        
        // Usuarios sin roles
        $usersWithoutRoles = $entityManager->createQuery('
            SELECT COUNT(u) FROM App\Entity\User u 
            WHERE u.habilitado = true 
            AND (u.roles IS NULL OR SIZE(u.roles) = 0)
        ')->getSingleScalarResult();
        
        // Usuarios sin roles (lista)
        $usersWithoutRolesList = $entityManager->createQuery('
            SELECT u FROM App\Entity\User u 
            WHERE u.habilitado = true 
            AND (u.roles IS NULL OR SIZE(u.roles) = 0)
        ')->setMaxResults(5)->getResult();
        
        // Roles más utilizados
        $popularRoles = $entityManager->createQuery('
            SELECT r, COUNT(u.id) as userCount 
            FROM App\Entity\Role r 
            LEFT JOIN r.users u 
            WHERE r.isActive = true 
            GROUP BY r.id 
            ORDER BY userCount DESC
        ')->setMaxResults(5)->getResult();
        
        // Roles con permisos
        $rolesWithPermissions = $roleRepository->findWithPermissions();

        return $this->render('admin/users/role_dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalRoles' => $totalRoles,
            'totalPermissions' => $totalPermissions,
            'usersWithoutRoles' => $usersWithoutRoles,
            'usersWithoutRolesList' => $usersWithoutRolesList,
            'popularRoles' => $popularRoles,
            'rolesWithPermissions' => $rolesWithPermissions,
        ]);
    }
}
