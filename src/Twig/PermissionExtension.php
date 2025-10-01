<?php

namespace App\Twig;

use App\Service\PermissionService;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PermissionExtension extends AbstractExtension
{
    private $permissionService;
    private $security;

    public function __construct(PermissionService $permissionService, Security $security)
    {
        $this->permissionService = $permissionService;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_manage_users', [$this, 'canManageUsers']),
            new TwigFunction('can_access_medical_records', [$this, 'canAccessMedicalRecords']),
            new TwigFunction('can_edit_medical_records', [$this, 'canEditMedicalRecords']),
            new TwigFunction('can_manage_inventory', [$this, 'canManageInventory']),
            new TwigFunction('can_view_reports', [$this, 'canViewReports']),
            new TwigFunction('can_manage_bookings', [$this, 'canManageBookings']),
            new TwigFunction('can_view_patient_info', [$this, 'canViewPatientInfo']),
            new TwigFunction('can_manage_system_config', [$this, 'canManageSystemConfig']),
            new TwigFunction('can_access_admin_panel', [$this, 'canAccessAdminPanel']),
            new TwigFunction('get_user_access_level', [$this, 'getUserAccessLevel']),
            new TwigFunction('get_user_permissions', [$this, 'getUserPermissions']),
        ];
    }

    public function canManageUsers(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canManageUsers($user) : false;
    }

    public function canAccessMedicalRecords(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canAccessMedicalRecords($user) : false;
    }

    public function canEditMedicalRecords(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canEditMedicalRecords($user) : false;
    }

    public function canManageInventory(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canManageInventory($user) : false;
    }

    public function canViewReports(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canViewReports($user) : false;
    }

    public function canManageBookings(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canManageBookings($user) : false;
    }

    public function canViewPatientInfo(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canViewPatientInfo($user) : false;
    }

    public function canManageSystemConfig(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canManageSystemConfig($user) : false;
    }

    public function canAccessAdminPanel(): bool
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->canAccessAdminPanel($user) : false;
    }

    public function getUserAccessLevel(): string
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->getUserAccessLevel($user) : 'No autenticado';
    }

    public function getUserPermissions(): array
    {
        $user = $this->security->getUser();
        return $user ? $this->permissionService->getUserPermissions($user) : [];
    }
}
