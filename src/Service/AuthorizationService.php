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
    public function getRoles(): array
    {
        $roles = $this->roleRepository->findBy(['isActive' => true]);
        $roleLabels = [];
        
        foreach ($roles as $role) {
            $roleLabels[$role->getDisplayName()] = $role->getId();
        }
        
        return $roleLabels;
    }
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
    public function createRole(string $name, string $displayName, ?string $description = null, ?string $category = null): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setDisplayName($displayName);
        $role->setDescription($description);
        $role->setIsActive(true);
        $role->setIsSystem(false);
        
        // Asignar categoría si se proporciona
        if ($category) {
            $role->setCategory($category);
        } else {
            // Intentar asignar categoría automáticamente basándose en el nombre
            $role->setCategory($this->guessCategoryFromName($name));
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }
    
    /**
     * Guess category from role name
     */
    private function guessCategoryFromName(string $name): ?string
    {
        $nameLower = strtolower($name);
        
        // Roles médicos
        if (strpos($nameLower, 'medico') !== false || 
            strpos($nameLower, 'director_medico') !== false ||
            strpos($nameLower, 'sub_director_medico') !== false ||
            strpos($nameLower, 'psiquiatra') !== false ||
            strpos($nameLower, 'infectologo') !== false ||
            strpos($nameLower, 'neurologo') !== false ||
            strpos($nameLower, 'cardiologo') !== false ||
            strpos($nameLower, 'urologo') !== false ||
            strpos($nameLower, 'hematologo') !== false ||
            strpos($nameLower, 'neumonologo') !== false ||
            strpos($nameLower, 'cirujano') !== false ||
            strpos($nameLower, 'traumatologo') !== false ||
            strpos($nameLower, 'fisiatra') !== false ||
            strpos($nameLower, 'medico_guardia') !== false ||
            strpos($nameLower, 'medico_clinico') !== false) {
            return Role::CATEGORY_MEDICAL;
        }
        
        // Roles de enfermería
        if (strpos($nameLower, 'enfermer') !== false || 
            strpos($nameLower, 'coordinador_enfermeria') !== false) {
            return Role::CATEGORY_NURSING;
        }
        
        // Roles administrativos
        if (strpos($nameLower, 'admin') !== false ||
            strpos($nameLower, 'administrativo') !== false ||
            strpos($nameLower, 'recepcionista') !== false ||
            strpos($nameLower, 'coordinador_general') !== false ||
            strpos($nameLower, 'coordinador_pisos') !== false ||
            strpos($nameLower, 'directivo') !== false ||
            strpos($nameLower, 'contador') !== false ||
            strpos($nameLower, 'abogado') !== false ||
            strpos($nameLower, 'estudio_contable') !== false ||
            strpos($nameLower, 'programador') !== false) {
            return Role::CATEGORY_ADMINISTRATIVE;
        }
        
        // Roles de cocina
        if (strpos($nameLower, 'cocin') !== false || 
            strpos($nameLower, 'ayudante_cocina') !== false) {
            return Role::CATEGORY_KITCHEN;
        }
        
        // Roles de mantenimiento
        if (strpos($nameLower, 'mantenimiento') !== false || 
            strpos($nameLower, 'mucamo') !== false) {
            return Role::CATEGORY_MAINTENANCE;
        }
        
        // Por defecto, categoría "Otro"
        return Role::CATEGORY_OTHER;
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
            ['patient.edit_evolve', 'Editar Evoluciones Médicas', 'Pacientes', 'Permite editar evoluciones médicas existentes'],
            ['patient.evolve_without_presence', 'Evolucionar sin Presente', 'Pacientes', 'Permite crear evoluciones sin necesidad de tener presente del día'],
            ['patient.prescription', 'Prescripciones', 'Pacientes', 'Permite gestionar prescripciones médicas'],
            ['patient.kardex', 'Completar Kardex', 'Pacientes', 'Permite completar el kardex del paciente (signos vitales, medicación, cuidados)'],
            ['patient.indication', 'Gestionar Indicaciones Médicas', 'Pacientes', 'Permite crear, editar y gestionar indicaciones médicas (medicamentos, procedimientos, controles)'],
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
            
            // ===== INFORMES MENSUALES =====
            ['informe_mensual.view', 'Ver Informes Mensuales', 'Informes Mensuales', 'Permite ver los informes mensuales (solo los propios si no tiene manage)'],
            ['informe_mensual.manage', 'Administrar Informes Mensuales', 'Informes Mensuales', 'Permite ver, crear, editar y eliminar todos los informes mensuales'],
            
            // ===== REPORTES Y ESTADÍSTICAS =====
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
            
            // ===== FIRMAS =====
            ['firma.create', 'Crear Firmas', 'Firmas', 'Permite crear firmas digitales propias'],
            ['firma.update', 'Actualizar Firmas', 'Firmas', 'Permite actualizar firmas digitales propias'],
            ['firma.delete', 'Eliminar Firmas', 'Firmas', 'Permite eliminar firmas digitales propias'],
            ['firma.manage_others', 'Gestionar Firmas de Otros', 'Firmas', 'Permite gestionar firmas digitales de otros usuarios'],
            
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

        // Crear roles por defecto (basados en las antiguas modalidades)
        $roles = [
            // ===== ROLES ADMINISTRATIVOS =====
            [
                'name' => 'admin',
                'displayName' => 'Administrador',
                'description' => 'Acceso completo al sistema',
                'permissions' => '*' // Todos los permisos
            ],
            
            // ===== ROLES DE EMPLEADOS (TIPO CONTRATO 1) =====
            [
                'name' => 'mucamo',
                'displayName' => 'Mucamo/a',
                'description' => 'Personal de limpieza y mantenimiento básico',
                'permissions' => ['patient.view', 'stats.view']
            ],
            [
                'name' => 'enfermero',
                'displayName' => 'Enfermero/a',
                'description' => 'Personal de enfermería profesional',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.kardex', 'patient.attendance', 'patient.prescription',
                    'agenda.view', 'consumables.view', 'stats.view'
                ]
            ],
            [
                'name' => 'auxiliar_enfermeria',
                'displayName' => 'Auxiliar de Enfermería',
                'description' => 'Asistente de enfermería',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.kardex', 'patient.attendance',
                    'agenda.view', 'consumables.view', 'stats.view'
                ]
            ],
            [
                'name' => 'asistente_enfermeria',
                'displayName' => 'Asistente de Enfermería',
                'description' => 'Asistente de enfermería',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.kardex', 'patient.attendance',
                    'consumables.view', 'stats.view'
                ]
            ],
            [
                'name' => 'mantenimiento',
                'displayName' => 'Mantenimiento',
                'description' => 'Personal de mantenimiento',
                'permissions' => ['stats.view']
            ],
            [
                'name' => 'cocinero',
                'displayName' => 'Cocinero',
                'description' => 'Personal de cocina',
                'permissions' => ['patient.view', 'stats.view']
            ],
            [
                'name' => 'ayudante_cocina',
                'displayName' => 'Ayudante de Cocina',
                'description' => 'Asistente de cocina',
                'permissions' => ['patient.view', 'stats.view']
            ],
            [
                'name' => 'administrativo',
                'displayName' => 'Administrativo',
                'description' => 'Personal administrativo',
                'permissions' => [
                    'patient.view', 'patient.create', 'patient.edit',
                    'agenda.view', 'agenda.manage', 'config.works', 'stats.view'
                ]
            ],
            [
                'name' => 'recepcionista',
                'displayName' => 'Recepcionista',
                'description' => 'Personal de recepción',
                'permissions' => [
                    'patient.view', 'agenda.view', 'agenda.manage', 'stats.view'
                ]
            ],
            [
                'name' => 'coordinador_pisos',
                'displayName' => 'Coordinador de Pisos',
                'description' => 'Coordinador de pisos',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.kardex', 'patient.attendance',
                    'nurse.read', 'stats.view', 'config.rooms'
                ]
            ],
            [
                'name' => 'coordinador_general',
                'displayName' => 'Coordinador General',
                'description' => 'Coordinador general del establecimiento',
                'permissions' => [
                    'patient.view', 'patient.create', 'patient.edit', 'patient.history',
                    'nurse.read', 'doctor.read', 'stats.view', 'config.rooms', 'config.staff',
                    'liquidations.view', 'claims.view', 'claims.manage'
                ]
            ],
            [
                'name' => 'coordinador_enfermeria',
                'displayName' => 'Coordinador de Enfermería',
                'description' => 'Coordinador del equipo de enfermería',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.kardex', 'patient.attendance',
                    'nurse.read', 'nurse.create', 'nurse.update', 'consumables.view', 'consumables.manage',
                    'stats.view'
                ]
            ],
            [
                'name' => 'coordinador_general',
                'displayName' => 'Coordinador General',
                'description' => 'Coordinador general de la institución',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.create', 'patient.edit',
                    'nurse.read', 'doctor.read', 'staff.read', 'stats.view', 'config.rooms',
                    'consumables.view', 'consumables.manage', 'claims.view', 'claims.manage'
                ]
            ],
            [
                'name' => 'coordinador_pisos',
                'displayName' => 'Coordinador de Pisos',
                'description' => 'Coordinador de pisos y habitaciones',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.attendance',
                    'nurse.read', 'config.rooms', 'consumables.view', 'stats.view'
                ]
            ],
            
            // ===== ROLES DE PERSONAL DIRECTO (TIPO CONTRATO 2) =====
            [
                'name' => 'nutricionista',
                'displayName' => 'Nutricionista',
                'description' => 'Profesional de nutrición',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'director_medico',
                'displayName' => 'Director Médico',
                'description' => 'Director médico del establecimiento',
                'permissions' => [
                    'user.read', 'patient.view', 'patient.create', 'patient.edit', 'patient.history',
                    'patient.evolve', 'patient.edit_evolve', 'patient.evolve_without_presence',
                    'patient.prescription', 'patient.indication', 'patient.discharge', 'patient.refer', 'patient.permission',
                    'doctor.read', 'doctor.create', 'doctor.update', 'nurse.read',
                    'agenda.view', 'agenda.manage', 'liquidations.view', 'liquidations.manage',
                    'informe_mensual.view', 'informe_mensual.manage', 'stats.view',
                    'config.staff', 'claims.view', 'claims.manage'
                ]
            ],
            [
                'name' => 'sub_director_medico',
                'displayName' => 'Sub Director Médico',
                'description' => 'Sub director médico',
                'permissions' => [
                    'patient.view', 'patient.create', 'patient.edit', 'patient.history',
                    'patient.evolve', 'patient.edit_evolve', 'patient.prescription', 'patient.indication',
                    'patient.discharge', 'patient.refer', 'patient.permission',
                    'doctor.read', 'nurse.read',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view',
                    'claims.view'
                ]
            ],
            [
                'name' => 'trabajador_social',
                'displayName' => 'Trabajador Social',
                'description' => 'Trabajador social',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'stats.view'
                ]
            ],
            [
                'name' => 'psiquiatra',
                'displayName' => 'Psiquiatra',
                'description' => 'Médico psiquiatra',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'infectologo',
                'displayName' => 'Infectólogo',
                'description' => 'Médico infectólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'contador',
                'displayName' => 'Contador',
                'description' => 'Contador del establecimiento',
                'permissions' => [
                    'liquidations.view', 'liquidations.manage', 'stats.view', 'consumables.view'
                ]
            ],
            [
                'name' => 'abogado',
                'displayName' => 'Abogado',
                'description' => 'Asesor legal',
                'permissions' => [
                    'patient.view', 'claims.view', 'claims.manage', 'stats.view'
                ]
            ],
            [
                'name' => 'estudio_contable',
                'displayName' => 'Estudio Contable',
                'description' => 'Personal del estudio contable',
                'permissions' => [
                    'liquidations.view', 'liquidations.manage', 'stats.view'
                ]
            ],
            [
                'name' => 'directivo',
                'displayName' => 'Directivo',
                'description' => 'Directivo del establecimiento',
                'permissions' => [
                    'user.read', 'patient.view', 'patient.history',
                    'doctor.read', 'nurse.read', 'liquidations.view', 'stats.view',
                    'config.works', 'config.rooms', 'config.staff', 'claims.view'
                ]
            ],
            [
                'name' => 'programador',
                'displayName' => 'Programador',
                'description' => 'Desarrollador de sistemas',
                'permissions' => '*' // Acceso total para mantenimiento del sistema
            ],
            
            // ===== ROLES DE PRESTACIÓN (TIPO CONTRATO 3) =====
            [
                'name' => 'profesional_prestacion',
                'displayName' => 'Profesional por Prestación',
                'description' => 'Profesional contratado por prestación',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'medico_clinico',
                'displayName' => 'Médico Clínico',
                'description' => 'Médico clínico',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'patient.discharge', 'patient.refer',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'hidroterapia_motora',
                'displayName' => 'HidroTerapia Motora',
                'description' => 'Especialista en hidroterapia motora',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'medico_guardia',
                'displayName' => 'Médico de Guardia',
                'description' => 'Médico de guardia',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'stats.view'
                ]
            ],
            [
                'name' => 'kinesiologo_motora',
                'displayName' => 'Kinesiólogo Motora',
                'description' => 'Kinesiólogo especializado en motricidad',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'kinesiologo_respiratorio',
                'displayName' => 'Kinesiólogo Respiratorio',
                'description' => 'Kinesiólogo respiratorio',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'terapista_ocupacional',
                'displayName' => 'Terapista Ocupacional',
                'description' => 'Terapista ocupacional',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'fonoaudiologo',
                'displayName' => 'Fonoaudiólogo',
                'description' => 'Profesional en fonoaudiología',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'psicologo',
                'displayName' => 'Psicólogo',
                'description' => 'Profesional en psicología',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'fisiatra',
                'displayName' => 'Fisiatra',
                'description' => 'Médico fisiatra - puede ser doctor referente',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'patient.discharge', 'patient.refer', 'patient.permission',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'neurologo',
                'displayName' => 'Neurólogo',
                'description' => 'Médico neurólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'cardiologo',
                'displayName' => 'Cardiólogo',
                'description' => 'Médico cardiólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'urologo',
                'displayName' => 'Urólogo',
                'description' => 'Médico urólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'hematologo',
                'displayName' => 'Hematólogo',
                'description' => 'Médico hematólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'neumonologo',
                'displayName' => 'Neumónologo',
                'description' => 'Médico neumónologo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            
            // ===== ROLES SIN CONTRATO (TIPO CONTRATO 4) =====
            [
                'name' => 'cirujano',
                'displayName' => 'Cirujano',
                'description' => 'Médico cirujano',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
            [
                'name' => 'traumatologo',
                'displayName' => 'Traumatólogo',
                'description' => 'Médico traumatólogo',
                'permissions' => [
                    'patient.view', 'patient.history', 'patient.evolve', 'patient.prescription', 'patient.indication',
                    'agenda.view', 'liquidations.view', 'informe_mensual.view', 'stats.view'
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            $existing = $this->roleRepository->findByName($roleData['name']);
            if (!$existing) {
                $role = $this->createRole($roleData['name'], $roleData['displayName'], $roleData['description']);
                
                // Asignar permisos al rol
                if ($roleData['permissions'] === '*') {
                    // Asignar todos los permisos
                    $allPermissions = $this->permissionRepository->findActive();
                    foreach ($allPermissions as $permission) {
                        $this->assignPermissionToRole($role, $permission);
                    }
                } else {
                    foreach ($roleData['permissions'] as $permissionName) {
                        $permission = $this->permissionRepository->findByName($permissionName);
                        if ($permission) {
                            $this->assignPermissionToRole($role, $permission);
                        }
                    }
                }
            }
        }
    }
}
