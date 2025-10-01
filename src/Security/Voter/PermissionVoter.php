<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // Solo manejar permisos específicos (no roles que empiecen con ROLE_)
        return !str_starts_with($attribute, 'ROLE_');
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Usar el método hasPermission de la entidad User
        return $user->hasPermission($attribute);
    }
}
