<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function turnosParaAgenda($doctor, $dia, $periodo, $user = [], $desde = '', $hasta = '')
    {
        if($desde != '') $dia = $desde;
        $midnightyesterday2 = clone $dia;
        $midnightyesterday2->setTime(0, 0);
        $start= $midnightyesterday2->format("Y-m-d H:i:s");

        if($hasta != '') $dia = $hasta;
        $endofdayyesterday2 = clone $dia;
        if($periodo == 'semana') {
            $endofdayyesterday2->modify('+7days');
        } else if ($periodo == 'mes') {
            $endofdayyesterday2->modify('+1month');
        }
        $endofdayyesterday2->setTime(23, 59, 59);
        $end = $endofdayyesterday2->format("Y-m-d H:i:s");


        $query = $this->createQueryBuilder('b');

        if($periodo !== 'anteriores' || ($desde != '' && $hasta != '')) {
            $query = $query
                ->andWhere('b.beginAt >= :start')
                ->andWhere('b.beginAt <= :end')
                ->setParameter('end', $end)
                ->setParameter('start', $start);
        }

        if(!empty($doctor)) {
            $query = $query
                ->andWhere('b.doctor = :doctor')
                ->setParameter('doctor', $doctor);
        }
        if(!empty($user)) {
          $query = $query->andWhere('b.cliente IN (:cliente)')->setParameter('cliente', $user);
        };
        $query = $query->orderBy('b.beginAt', 'asc');
        $query = $query->getQuery();

        return $query->getResult();
    }


    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function turnosConFiltro($doctor = '', $paciente = '', $desde = '', $hasta = '', $completados = '')
    {
        $query = $this->createQueryBuilder('b');

        if(!empty($doctor)) {
            $query = $query
                ->andWhere('b.doctor = :doctor')
                ->setParameter('doctor', $doctor);
        }

        if(!empty($paciente)) {
            $query = $query
                ->andWhere('b.cliente = :cliente')
                ->setParameter('cliente', $paciente);
        }

        if(!empty($desde)) {
            $from = (new \DateTime($desde));
            $from->setTime(00, 00, 00);
            $desde = $from->format("Y-m-d H:i:s");

            $query = $query
                ->andWhere('b.beginAt >= :desde')
                ->setParameter('desde', $desde);
        }

        if(!empty($hasta)) {
            $to = (new \DateTime($hasta));
            $to->setTime(23, 59, 59);
            $hasta = $to->format("Y-m-d H:i:s");

            $query = $query
                ->andWhere('b.endAt <= :hasta')
                ->setParameter('hasta', $hasta);
        }

           if($completados === false) {
                $query = $query
                    ->andWhere('b.completado IS NULL');
           } else if($completados === true) {
                $query = $query
                    ->andWhere('b.completado = :completado')
                    ->setParameter('completado', $completados);
           }

        $query = $query->orderBy('b.beginAt', 'asc');
        $query = $query->getQuery();

        return $query->getResult();

    }



    // /**
    //  * @return Booking[] Returns an array of Booking objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
