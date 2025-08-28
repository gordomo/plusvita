<?php

namespace App\Repository;

use App\Entity\NotaItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotaItem>
 *
 * @method NotaItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotaItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotaItem[]    findAll()
 * @method NotaItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotaItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotaItem::class);
    }

    public function findByItem($itemId)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.item = :val')
            ->setParameter('val', $itemId)
            ->orderBy('n.fecha_creacion', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
