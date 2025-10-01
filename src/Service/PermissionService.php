<?php

namespace App\Service;

use Symfony\Component\Security\Core\User\UserInterface;

class PermissionService
{
    /**
     * Verifica si un usuario puede gestionar usuarios
     */
    public function canManageUsers(UserInterface $user): bool
    {
        return $this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    /**
     * Verifica si un usuario puede acceder a registros médicos
     */
    public function canAccessMedicalRecords(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_DOCTOR', 
            'ROLE_NURSE', 
            'ROLE_THERAPIST', 
            'ROLE_MEDICAL_STAFF', 
            'ROLE_MEDICAL_DIRECTOR',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede editar registros médicos
     */
    public function canEditMedicalRecords(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_DOCTOR', 
            'ROLE_NURSE', 
            'ROLE_THERAPIST',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede gestionar items/inventario
     */
    public function canManageInventory(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_ADMINISTRATIVE_STAFF',
            'ROLE_OPERATOR',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede ver reportes
     */
    public function canViewReports(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_MEDICAL_DIRECTOR',
            'ROLE_ADMINISTRATIVE_STAFF',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede gestionar turnos
     */
    public function canManageBookings(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_ADMINISTRATIVE_STAFF',
            'ROLE_OPERATOR',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede ver información de pacientes
     */
    public function canViewPatientInfo(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_DOCTOR', 
            'ROLE_NURSE', 
            'ROLE_THERAPIST', 
            'ROLE_MEDICAL_STAFF', 
            'ROLE_MEDICAL_DIRECTOR',
            'ROLE_ADMINISTRATIVE_STAFF',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Verifica si un usuario puede gestionar configuración del sistema
     */
    public function canManageSystemConfig(UserInterface $user): bool
    {
        return $this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    /**
     * Verifica si un usuario puede acceder al panel de administración
     */
    public function canAccessAdminPanel(UserInterface $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_MEDICAL_DIRECTOR',
            'ROLE_ADMINISTRATIVE_STAFF',
            'ROLE_ADMIN', 
            'ROLE_SUPER_ADMIN'
        ]);
    }

    /**
     * Obtiene el nivel de acceso del usuario (para mostrar en la interfaz)
     */
    public function getUserAccessLevel(UserInterface $user): string
    {
        if ($this->hasRole($user, ['ROLE_SUPER_ADMIN'])) {
            return 'Super Administrador';
        }
        if ($this->hasRole($user, ['ROLE_ADMIN'])) {
            return 'Administrador';
        }
        if ($this->hasRole($user, ['ROLE_MEDICAL_DIRECTOR'])) {
            return 'Director Médico';
        }
        if ($this->hasRole($user, ['ROLE_DOCTOR'])) {
            return 'Médico';
        }
        if ($this->hasRole($user, ['ROLE_NURSE'])) {
            return 'Enfermero/a';
        }
        if ($this->hasRole($user, ['ROLE_THERAPIST'])) {
            return 'Terapeuta';
        }
        if ($this->hasRole($user, ['ROLE_MEDICAL_STAFF'])) {
            return 'Staff Médico';
        }
        if ($this->hasRole($user, ['ROLE_ADMINISTRATIVE_STAFF'])) {
            return 'Staff Administrativo';
        }
        if ($this->hasRole($user, ['ROLE_OPERATOR'])) {
            return 'Operador';
        }
        if ($this->hasRole($user, ['ROLE_VIEWER'])) {
            return 'Solo Lectura';
        }
        
        return 'Usuario';
    }

    /**
     * Verifica si un usuario tiene al menos uno de los roles especificados
     */
    private function hasRole(UserInterface $user, array $roles): bool
    {
        $userRoles = $user->getRoles();
        
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtiene los permisos específicos del usuario
     */
    public function getUserPermissions(UserInterface $user): array
    {
        return [
            'manage_users' => $this->canManageUsers($user),
            'access_medical_records' => $this->canAccessMedicalRecords($user),
            'edit_medical_records' => $this->canEditMedicalRecords($user),
            'manage_inventory' => $this->canManageInventory($user),
            'view_reports' => $this->canViewReports($user),
            'manage_bookings' => $this->canManageBookings($user),
            'view_patient_info' => $this->canViewPatientInfo($user),
            'manage_system_config' => $this->canManageSystemConfig($user),
            'access_admin_panel' => $this->canAccessAdminPanel($user),
        ];
    }
}
