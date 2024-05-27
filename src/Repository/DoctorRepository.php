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
        if($vencidos) {
            $qb->where('d.vtoContrato <= :hoy')
                ->setParameter('hoy', $hoy);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllVencenEsteMes()
    {
        $hoy = new \DateTime();
        $esteMes = $hoy->format('n');
        return $this->createQueryBuilder('d')
            ->where('MONTH(d.vtoContrato) = :esteMes')
            ->setParameter('esteMes', $esteMes)
            ->getQuery()->getResult();
    }

    public function findAllVencidos()
    {
        $hoy = new \DateTime();
        return $this->createQueryBuilder('d')
            ->where('d.vtoContrato <= :hoy')
            ->setParameter('hoy', $hoy)
            ->getQuery()->getResult();
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
