<?php

namespace App\Repository;

use App\Entity\PresentesDoctores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PresentesDoctores>
 *
 * @method PresentesDoctores|null find($id, $lockMode = null, $lockVersion = null)
 * @method PresentesDoctores|null findOneBy(array $criteria, array $orderBy = null)
 * @method PresentesDoctores[]    findAll()
 * @method PresentesDoctores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresentesDoctoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PresentesDoctores::class);
    }


}
