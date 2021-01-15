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

    public function findByContratos($valueArray)
    {
        $qb = $this->createQueryBuilder('d');
            foreach($valueArray as $value) {
                $qb = $qb->orWhere("JSON_CONTAINS (d.modalidad, '\"$value\"', '$') = 1");
            }
        return $qb->getQuery()->getResult();
    }

    public function findColoresEnUso() {
        return $this->createQueryBuilder('d')
            ->select('d.color')
            ->where('d.color is not null')
            ->getQuery()
            ->getResult();

    }
}
