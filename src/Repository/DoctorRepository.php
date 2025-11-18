<?php

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Doctor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Doctor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Doctor[]    findAll()
 * @method Doctor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctor::class);
    }

    public function findByContrato($value)
    {
        return $this->createQueryBuilder('d')
            ->where("JSON_CONTAINS (d.modalidad, '\"$value\"', '$') = 1")
            //->setParameter('val', $value)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByContratos($valueArray, $vencidos)
    {
        $hoy = new \DateTime();
        $qb = $this->createQueryBuilder('d');
        foreach($valueArray as $value) {
            $qb = $qb->orWhere("JSON_CONTAINS (d.modalidad, '\"$value\"', '$') = 1");
        }
        // El campo vtoContrato ya no existe en Doctor, se maneja en UserContract
        // Si se necesita filtrar por contratos vencidos, debe hacerse a través de UserContract
        if($vencidos) {
            // TODO: Implementar filtro por contratos vencidos usando UserContract
            // Por ahora, retornar todos los resultados sin filtrar por vencimiento
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllVencenEsteMes()
    {
        // El campo vtoContrato ya no existe en Doctor, se maneja en UserContract
        // TODO: Implementar usando UserContract si es necesario
        // Por ahora, retornar array vacío
        return [];
    }

    public function findAllVencidos()
    {
        // El campo vtoContrato ya no existe en Doctor, se maneja en UserContract
        // TODO: Implementar usando UserContract si es necesario
        // Por ahora, retornar array vacío
        return [];
    }

    public function findColoresEnUso() {
        return $this->createQueryBuilder('d')
            ->select('d.color')
            ->where('d.color is not null')
            ->getQuery()
            ->getResult();

    }

    public function findDocReferente() {
        return $this->createQueryBuilder('u')
            ->where("JSON_CONTAINS (u.modalidad, '\"Fisiatra\"', '$') = 1")
            ->orWhere("JSON_CONTAINS (u.modalidad, '\"Director medico\"', '$') = 1")
            ->orWhere("JSON_CONTAINS (u.modalidad, '\"Sub director medico\"', '$') = 1")
            ->getQuery()
            ->getResult();
    }

    public function findEmails() {
        return $this->createQueryBuilder('d')
        ->select('d.email')
        ->getQuery()->getResult();
    }
}
