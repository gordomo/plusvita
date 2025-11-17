<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Obtener usuarios con un rol específico
     */
    public function findByRole(string $roleName)
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :roleName')
            ->andWhere('r.isActive = 1')
            ->andWhere('u.habilitado = 1')
            ->setParameter('roleName', $roleName)
            ->orderBy('u.apellido', 'ASC')
            ->addOrderBy('u.nombre', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Obtener usuarios con cualquiera de los roles especificados
     */
    public function findByRoles(array $roleNames, ?string $searchTerm = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.roles', 'r')
            ->where('r.name IN (:roles)')
            ->andWhere('r.isActive = 1')
            ->andWhere('u.habilitado = 1')
            ->setParameter('roles', $roleNames)
            ->orderBy('u.apellido', 'ASC')
            ->addOrderBy('u.nombre', 'ASC');

        // Agregar filtro por búsqueda si se proporciona
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $qb->andWhere('(u.nombre LIKE :search OR u.apellido LIKE :search OR u.email LIKE :search)')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtener todos los usuarios habilitados (con o sin roles)
     */
    public function findAllEnabled(?string $searchTerm = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.habilitado = 1')
            ->orderBy('u.apellido', 'ASC')
            ->addOrderBy('u.nombre', 'ASC');

        // Agregar filtro por búsqueda si se proporciona
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $qb->andWhere('(u.nombre LIKE :search OR u.apellido LIKE :search OR u.email LIKE :search OR u.username LIKE :search)')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtener doctores referentes (Fisiatras, Directores Médicos)
     */
    public function findDocReferente()
    {
        return $this->findByRoles(['fisiatra', 'director_medico', 'sub_director_medico']);
    }

    /**
     * Obtener todos los usuarios con roles médicos
     */
    public function findDoctors()
    {
        $rolesDoctor = [
            'medico_clinico', 'fisiatra', 'neurologo', 'cardiologo', 'psiquiatra',
            'infectologo', 'urologo', 'hematologo', 'neumonologo', 'cirujano',
            'traumatologo', 'director_medico', 'sub_director_medico', 'medico_guardia',
            'nutricionista'
        ];
        
        return $this->findByRoles($rolesDoctor);
    }

    /**
     * Obtener todos los enfermeros
     */
    public function findNurses()
    {
        return $this->findByRoles(['enfermero', 'auxiliar_enfermeria', 'asistente_enfermeria', 'coordinador_enfermeria']);
    }

    /**
     * Obtener emails de todos los usuarios
     */
    public function findEmails()
    {
        return $this->createQueryBuilder('u')
            ->select('u.email')
            ->where('u.habilitado = 1')
            ->getQuery()
            ->getResult();
    }
}
