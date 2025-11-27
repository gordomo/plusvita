<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter para manejar la compatibilidad con roles legacy (ROLE_ADMIN, ROLE_DOCTOR, ROLE_STAFF, ROLE_NURSE)
 * Mapea los roles antiguos al nuevo sistema de roles basado en permisos
 */
class LegacyRoleVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // Solo soportamos los roles legacy
        return in_array($attribute, [
            'ROLE_ADMIN',
            'ROLE_DOCTOR',
            'ROLE_STAFF',
            'ROLE_NURSE',
            'ROLE_USER',
            'ROLE_EDIT_HC' // Rol legacy para editar historias clínicas
        ]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si no está autenticado, denegar
        if (!$user instanceof User) {
            return false;
        }

        // ROLE_USER: todos los usuarios autenticados
        if ($attribute === 'ROLE_USER') {
            return true;
        }

        // ROLE_ADMIN: usuarios con rol admin
        if ($attribute === 'ROLE_ADMIN') {
            return $user->hasRole('admin');
        }

        // ROLE_DOCTOR: cualquier usuario con rol médico (verificado por categoría)
        if ($attribute === 'ROLE_DOCTOR') {
            return $user->hasMedicalRole();
        }

        // ROLE_STAFF: cualquier usuario con roles de staff (médicos, enfermeros, administrativos, etc.)
        if ($attribute === 'ROLE_STAFF') {
            // Verificar si tiene algún rol que no sea solo 'admin'
            $userRoles = $user->getRoleEntities();
            foreach ($userRoles as $role) {
                if ($role->getIsActive() && $role->getName() !== 'admin') {
                    return true;
                }
            }
            return false;
        }

        // ROLE_NURSE: usuarios con rol de enfermería
        if ($attribute === 'ROLE_NURSE') {
            return $user->hasRole('enfermero') || 
                   $user->hasRole('auxiliar_enfermeria') || 
                   $user->hasRole('asistente_enfermeria') ||
                   $user->hasRole('coordinador_enfermeria');
        }

        // ROLE_EDIT_HC: usuarios que pueden editar historias clínicas
        if ($attribute === 'ROLE_EDIT_HC') {
            return $user->hasPermission('patient.edit_evolve');
        }

        return false;
    }
}
