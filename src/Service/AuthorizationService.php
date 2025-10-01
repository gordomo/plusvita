<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Permission;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class AuthorizationService
{
    private $entityManager;
    private $roleRepository;
    private $permissionRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
        $this->security = $security;
    }

    /**
     * Check if current user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        return $user->hasPermission($permissionName);
    }

    /**
     * Check if current user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        return $user->hasRole($roleName);
    }

    /**
     * Check if current user has any of the specified roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has all of the specified permissions
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if (!$this->hasPermission($permissionName)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if current user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all permissions for current user
     */
    public function getUserPermissions(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return [];
        }

        $permissions = [];
        foreach ($user->getRoleEntities() as $role) {
            if ($role->getIsActive()) {
                foreach ($role->getPermissions() as $permission) {
                    if ($permission->getIsActive()) {
                        $permissions[] = $permission->getName();
                    }
                }
            }
        }

        return array_unique($permissions);
    }

    /**
     * Get all roles for current user
     */
    public function getUserRoles(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return [];
        }

        $roles = [];
        foreach ($user->getRoleEntities() as $role) {
            if ($role->getIsActive()) {
                $roles[] = $role->getName();
            }
        }

        return $roles;
    }

    /**
     * Create a new role
     */
    public function createRole(string $name, string $displayName, ?string $description = null): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setDisplayName($displayName);
        $role->setDescription($description);
        $role->setIsActive(true);
        $role->setIsSystem(false);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    /**
     * Create a new permission
     */
    public function createPermission(string $name, string $displayName, string $category, ?string $description = null): Permission
    {
        $permission = new Permission();
        $permission->setName($name);
        $permission->setDisplayName($displayName);
        $permission->setCategory($category);
        $permission->setDescription($description);
        $permission->setIsActive(true);
        $permission->setIsSystem(false);

        $this->entityManager->persist($permission);
        $this->entityManager->flush();

        return $permission;
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole(Role $role, Permission $permission): void
    {
        $role->addPermission($permission);
        $this->entityManager->flush();
    }

    /**
     * Remove permission from role
     */
    public function removePermissionFromRole(Role $role, Permission $permission): void
    {
        $role->removePermission($permission);
        $this->entityManager->flush();
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(User $user, Role $role): void
    {
        $user->addRole($role);
        $this->entityManager->flush();
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user, Role $role): void
    {
        $user->removeRole($role);
        $this->entityManager->flush();
    }

    /**
     * Get all available permissions grouped by category
     */
    public function getPermissionsByCategory(): array
    {
        $permissions = $this->permissionRepository->findActive();
        $grouped = [];

        foreach ($permissions as $permission) {
            $category = $permission->getCategory();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get all available roles
     */
    public function getAvailableRoles(): array
    {
        return $this->roleRepository->findActive();
    }

    /**
     * Check if user can access a specific resource
     */
    public function canAccess(string $resource, string $action = 'read'): bool
    {
        $permissionName = $resource . '.' . $action;
        return $this->hasPermission($permissionName);
    }

    /**
     * Initialize default roles and permissions
     */
    public function initializeDefaultRolesAndPermissions(): void
    {
        // Crear permisos por defecto
        $permissions = [
            // ===== ADMINISTRACIÓN =====
            ['user.create', 'Crear Usuarios', 'Administración', 'Permite crear nuevos usuarios'],
            ['user.read', 'Ver Usuarios', 'Administración', 'Permite ver la lista de usuarios'],
            ['user.update', 'Editar Usuarios', 'Administración', 'Permite editar usuarios existentes'],
            ['user.delete', 'Eliminar Usuarios', 'Administración', 'Permite eliminar usuarios'],
            ['role.create', 'Crear Roles', 'Administración', 'Permite crear nuevos roles'],
            ['role.read', 'Ver Roles', 'Administración', 'Permite ver la lista de roles'],
            ['role.update', 'Editar Roles', 'Administración', 'Permite editar roles existentes'],
            ['role.delete', 'Eliminar Roles', 'Administración', 'Permite eliminar roles'],
            
            // ===== PACIENTES =====
            ['patient.view', 'Ver Pacientes', 'Pacientes', 'Permite ver la lista de pacientes'],
            ['patient.create', 'Crear Pacientes', 'Pacientes', 'Permite crear nuevos pacientes'],
            ['patient.edit', 'Editar Pacientes', 'Pacientes', 'Permite editar información de pacientes'],
            ['patient.delete', 'Eliminar Pacientes', 'Pacientes', 'Permite eliminar pacientes'],
            ['patient.history', 'Historia Clínica', 'Pacientes', 'Permite acceder a historias clínicas'],
            ['patient.evolve', 'Evolucionar Pacientes', 'Pacientes', 'Permite crear evoluciones médicas'],
            ['patient.prescription', 'Prescripciones', 'Pacientes', 'Permite gestionar prescripciones médicas'],
            ['patient.cardex', 'Completar Cardex', 'Pacientes', 'Permite completar el cardex del paciente (signos vitales, medicación, cuidados)'],
            ['patient.discharge', 'Egresar Pacientes', 'Pacientes', 'Permite egresar pacientes del sistema'],
            ['patient.refer', 'Derivar Pacientes', 'Pacientes', 'Permite derivar pacientes a otros centros'],
            ['patient.permission', 'Dar Permisos', 'Pacientes', 'Permite otorgar permisos de salida a pacientes'],
            ['patient.outpatient', 'Gestionar Ambulatorios', 'Pacientes', 'Permite gestionar pacientes ambulatorios'],
            ['patient.attendance', 'Gestionar Asistencia', 'Pacientes', 'Permite marcar asistencia de pacientes'],
            
            // ===== INVENTARIO =====
            ['inventory.view', 'Ver Inventario', 'Inventario', 'Permite ver el inventario de items'],
            ['inventory.manage', 'Gestionar Inventario', 'Inventario', 'Permite crear, editar y eliminar items'],
            ['inventory.clone', 'Clonar Items', 'Inventario', 'Permite clonar items del inventario'],
            
            // ===== CONSUMIBLES =====
            ['consumables.view', 'Ver Consumibles', 'Consumibles', 'Permite ver la lista de consumibles'],
            ['consumables.manage', 'Gestionar Consumibles', 'Consumibles', 'Permite gestionar consumibles'],
            
            // ===== AGENDA =====
            ['agenda.view', 'Ver Agenda', 'Agenda', 'Permite ver la agenda médica'],
            ['agenda.manage', 'Gestionar Agenda', 'Agenda', 'Permite gestionar turnos y citas'],
            
            // ===== LIQUIDACIONES =====
            ['liquidations.view', 'Ver Liquidaciones', 'Liquidaciones', 'Permite ver liquidaciones profesionales'],
            ['liquidations.manage', 'Gestionar Liquidaciones', 'Liquidaciones', 'Permite gestionar liquidaciones'],
            
            // ===== REPORTES Y ESTADÍSTICAS =====
            ['reports.view', 'Ver Reportes', 'Reportes', 'Permite ver reportes del sistema'],
            ['reports.generate', 'Generar Reportes', 'Reportes', 'Permite generar nuevos reportes'],
            ['reports.export', 'Exportar Reportes', 'Reportes', 'Permite exportar reportes'],
            ['stats.view', 'Ver Estadísticas', 'Estadísticas', 'Permite ver estadísticas del sistema'],
            
            // ===== CONFIGURACIÓN =====
            ['config.works', 'Gestionar Obras Sociales', 'Configuración', 'Permite gestionar obras sociales'],
            ['config.rooms', 'Gestionar Habitaciones', 'Configuración', 'Permite gestionar habitaciones'],
            ['config.staff', 'Gestionar Staff', 'Configuración', 'Permite gestionar personal médico'],
            
            // ===== RECLAMOS =====
            ['claims.view', 'Ver Reclamos', 'Reclamos', 'Permite ver reclamos'],
            ['claims.manage', 'Gestionar Reclamos', 'Reclamos', 'Permite gestionar reclamos'],
            
            // ===== QR CODES =====
            ['qr.generate', 'Generar Códigos QR', 'QR', 'Permite generar códigos QR'],
            
            // ===== ENFERMERÍA =====
            ['nurse.create', 'Crear Enfermeros', 'Enfermería', 'Permite crear nuevos enfermeros'],
            ['nurse.read', 'Ver Enfermeros', 'Enfermería', 'Permite ver la lista de enfermeros'],
            ['nurse.update', 'Editar Enfermeros', 'Enfermería', 'Permite editar enfermeros existentes'],
            ['nurse.delete', 'Eliminar Enfermeros', 'Enfermería', 'Permite eliminar enfermeros'],
            
            // ===== DOCTORES =====
            ['doctor.create', 'Crear Doctores', 'Doctores', 'Permite crear nuevos doctores'],
            ['doctor.read', 'Ver Doctores', 'Doctores', 'Permite ver la lista de doctores'],
            ['doctor.update', 'Editar Doctores', 'Doctores', 'Permite editar doctores existentes'],
            ['doctor.delete', 'Eliminar Doctores', 'Doctores', 'Permite eliminar doctores'],
        ];

        foreach ($permissions as $permissionData) {
            $existing = $this->permissionRepository->findByName($permissionData[0]);
            if (!$existing) {
                $this->createPermission($permissionData[0], $permissionData[1], $permissionData[2], $permissionData[3]);
            }
        }

        // Crear roles por defecto
        $roles = [
            [
                'name' => 'admin',
                'displayName' => 'Administrador',
                'description' => 'Acceso completo al sistema',
                'permissions' => [
                    'user.create', 'user.read', 'user.update', 'user.delete',
                    'role.create', 'role.read', 'role.update', 'role.delete',
                    'patient.view', 'patient.create', 'patient.edit', 'patient.delete',
                    'patient.history', 'patient.evolve', 'patient.prescription', 'patient.cardex',
                    'patient.discharge', 'patient.refer', 'patient.permission', 'patient.outpatient', 'patient.attendance',
                    'inventory.view', 'inventory.manage', 'inventory.clone', 'consumables.view', 'consumables.manage',
                    'agenda.view', 'agenda.manage', 'liquidations.view', 'liquidations.manage',
                    'reports.view', 'reports.generate', 'reports.export', 'stats.view', 'config.works', 'config.rooms', 'config.staff',
                    'claims.view', 'claims.manage', 'qr.generate', 
                    'nurse.create', 'nurse.read', 'nurse.update', 'nurse.delete',
                    'doctor.create', 'doctor.read', 'doctor.update', 'doctor.delete'
                ]
            ],
            [
                'name' => 'doctor',
                'displayName' => 'Doctor',
                'description' => 'Acceso para personal médico',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription',
                    'patient.discharge', 'patient.refer', 'patient.outpatient',
                    'agenda.view', 'liquidations.view', 'reports.view', 'reports.generate', 'stats.view'
                ]
            ],
            [
                'name' => 'nurse',
                'displayName' => 'Enfermero',
                'description' => 'Acceso para personal de enfermería',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.cardex', 'patient.attendance',
                    'agenda.view', 'consumables.view'
                ]
            ],
            [
                'name' => 'operator',
                'displayName' => 'Operador',
                'description' => 'Acceso básico de operación',
                'permissions' => [
                    'patient.view', 'inventory.view', 'consumables.view', 'agenda.view', 'agenda.manage'
                ]
            ],
            [
                'name' => 'manager',
                'displayName' => 'Gerente',
                'description' => 'Acceso de gestión al sistema',
                'permissions' => [
                    'patient.view', 'patient.create', 'patient.edit', 'patient.cardex',
                    'inventory.view', 'inventory.manage', 'consumables.view', 'consumables.manage',
                    'agenda.view', 'agenda.manage', 'liquidations.view', 'liquidations.manage',
                    'reports.view', 'reports.generate', 'reports.export', 'stats.view', 'claims.view', 'claims.manage'
                ]
            ],
            [
                'name' => 'inventory_manager',
                'displayName' => 'Administrador de Inventario',
                'description' => 'Especializado en la gestión de items del inventario',
                'permissions' => [
                    'inventory.view', 'inventory.manage', 'inventory.clone',
                    'consumables.view', 'consumables.manage',
                    'reports.view', 'stats.view'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $existing = $this->roleRepository->findByName($roleData['name']);
            if (!$existing) {
                $role = $this->createRole($roleData['name'], $roleData['displayName'], $roleData['description']);
                
                // Asignar permisos al rol
                foreach ($roleData['permissions'] as $permissionPrefix) {
                    $permissions = $this->permissionRepository->findActive();
                    foreach ($permissions as $permission) {
                        if (strpos($permission->getName(), $permissionPrefix) === 0) {
                            $this->assignPermissionToRole($role, $permission);
                        }
                    }
                }
            }
        }
    }
}
