<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;

class DoctorService
{

    /**
     * @var Security
     */
    private $security;
    
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Devuelve true si el usuario es doctor o no pero tiene presente
     * 
     * @return bool
     */
    public function puedeEvolucionar(): bool {

        $rolesUsuario = $this->security->getUser()->getRoles();

        if(!in_array('ROLE_STAFF',$rolesUsuario)){
            return true;
        }

        if($this->security->getUser()->getPresente()){
            return true;
        }

        $medicos = ['Director medico',
                    'Sub director medico',
                    'Psiquiatra',
                    'Infectologo',
                    'Medico de guardia',
                    'Medico Clínico',
                    'Fonoaudiologo',
                    'Psicologo',
                    'Fisiatra',
                    'Neurologo',
                    'Cardiologo',
                    'Urologo',
                    'Hematologo',
                    'Neumonologo',
                    'Cirujano',
                    'Traumatologo',
                    'Neumonologo'];

        return in_array($this->security->getUser()->getModalidad()[0], $medicos);                         
    }
}