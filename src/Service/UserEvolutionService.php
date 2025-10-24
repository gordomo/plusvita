<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use App\Repository\UserPresenteRepository;

/**
 * Servicio para validar permisos de evolución (creación de evoluciones médicas) por usuarios
 * 
 * Verifica si un usuario puede evolucionar considerando:
 * - Presente del día en user_presentes, O
 * - Permiso especial 'patient.evolve_without_presence'
 */
class UserEvolutionService
{
    /**
     * @var Security
     */
    private $security;
    
    /**
     * @var UserPresenteRepository
     */
    private $userPresenteRepository;
    
    public function __construct(Security $security, UserPresenteRepository $userPresenteRepository)
    {
        $this->security = $security;
        $this->userPresenteRepository = $userPresenteRepository;
    }

    /**
     * Verifica si el usuario logueado puede evolucionar hoy
     * 
     * Retorna true si:
     * - Tiene el permiso especial 'patient.evolve_without_presence', O
     * - Tiene presente registrado para hoy
     * 
     * Nota: El permiso 'patient.evolve' debe validarse en el controller con isGranted()
     * 
     * @return bool
     */
    public function canEvolveToday(): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        // Si tiene el permiso especial, puede evolucionar sin presente
        if ($this->security->isGranted('patient.evolve_without_presence')) {
            return true;
        }

        // Si no tiene el permiso especial, requiere presente del día
        return $this->userPresenteRepository->hasPresente($user, new \DateTime('today'));
    }

    /**
     * Alias para compatibilidad con código viejo que referencia puedeEvolucionar
     * @deprecated Use canEvolveToday() instead
     */
    public function puedeEvolucionar(): bool
    {
        return $this->canEvolveToday();
    }
}
