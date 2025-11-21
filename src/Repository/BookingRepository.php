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
    public function turnosParaAgenda($doctor, $dia, $periodo, $user = [], $desde = '', $hasta = '', $completado = '')
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


        $query = $this->createQueryBuilder('b')
            // JOIN con cliente y obraSocial para evitar consultas N+1
            ->leftJoin('b.cliente', 'c')
            ->addSelect('c')
            ->leftJoin('c.obraSocial', 'os')
            ->addSelect('os')
            // JOIN con notas para evitar consultas adicionales
            ->leftJoin('b.notas', 'n')
            ->addSelect('n');

        if($periodo !== 'anteriores' || ($desde != '' && $hasta != '')) {
            $query = $query
                ->andWhere('b.beginAt >= :start')
                ->andWhere('b.beginAt <= :end')
                ->setParameter('end', $end)
                ->setParameter('start', $start);
        } else {
            // Para período "anteriores", solo mostrar turnos pasados y limitar resultados
            $query = $query
                ->andWhere('b.beginAt < :now')
                ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
                ->setMaxResults(500); // Limitar a 500 registros para evitar cargar demasiados datos
        }

        if(!empty($doctor)) {
            $query = $query
                ->andWhere('b.doctor = :doctor')
                ->setParameter('doctor', $doctor);
        }
        if(!empty($user)) {
          $query = $query->andWhere('b.cliente IN (:cliente)')->setParameter('cliente', $user);
        };
        if(!empty($completado)) {
          $query = $query->andWhere('b.completado = :completado')->setParameter('completado', $completado);
        };
        
        // Ordenar según el período: ascendente para períodos normales, descendente para "anteriores"
        if($periodo === 'anteriores' && ($desde == '' || $hasta == '')) {
            $query = $query->orderBy('b.beginAt', 'desc');
        } else {
            $query = $query->orderBy('b.beginAt', 'asc');
        }
        
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
            $query = $query
                ->andWhere('b.beginAt >= :desde')
                ->setParameter('desde', $desde);
        }

        if(!empty($hasta)) {
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
