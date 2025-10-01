<?php

namespace App\Security;

use App\Entity\Doctor;
use App\Entity\Nurse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class MultiEntityUserProvider implements UserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByUsername($username)
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Buscar en User
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $identifier]);
        if ($user) {
            return $user;
        }

        // Buscar en Doctor
        $user = $this->entityManager->getRepository(Doctor::class)->findOneBy(['username' => $identifier]);
        if ($user) {
            return $user;
        }

        // Buscar en Nurse
        $user = $this->entityManager->getRepository(Nurse::class)->findOneBy(['username' => $identifier]);
        if ($user) {
            return $user;
        }

        // Si no encuentra por username, buscar por email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
        if ($user) {
            return $user;
        }

        $user = $this->entityManager->getRepository(Doctor::class)->findOneBy(['email' => $identifier]);
        if ($user) {
            return $user;
        }

        $user = $this->entityManager->getRepository(Nurse::class)->findOneBy(['email' => $identifier]);
        if ($user) {
            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Usuario "%s" no encontrado.', $identifier));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User && !$user instanceof Doctor && !$user instanceof Nurse) {
            throw new UnsupportedUserException(sprintf('Instancia de "%s" no soportada.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUsername());
    }

    public function supportsClass($class)
    {
        return User::class === $class || Doctor::class === $class || Nurse::class === $class;
    }
}
