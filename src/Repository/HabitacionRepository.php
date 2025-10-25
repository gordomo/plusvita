<?php

namespace App\Repository;

use App\Entity\Habitacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Habitacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Habitacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Habitacion[]    findAll()
 * @method Habitacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HabitacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitacion::class);
    }

    public function findAllInNameOrder()
    {
        return $this->createQueryBuilder('q')
                    ->addSelect('ABS(q.nombre) AS HIDDEN foo')
                    ->orderBy('foo')
                    ->getQuery()
                    ->getResult();
    }

    // public function findHabitacionConCamasDisponibles()
    // {
    //     $resp = [];
    //     $todas = $this->findAll();
    //     foreach ($todas as $habitacion) {
    //         $arrayCamas = $habitacion->getCamasOcupadas();

    //         if ($habitacion->getCamasDisponibles() > count($arrayCamas)) {
    //            $resp[]  = $habitacion;

    //         }
    //     }
    //     return $resp;

    // }

    public function findHabitacionConCamasDisponibles($clienteRepository)
    {
        $resp = [];
        $todas = $this->findBy(array(), array('nombre' => 'ASC'));
        foreach ($todas as $habitacion) {
            $totalCamas = $habitacion->getCamasDisponibles();

            // Obtener todos los pacientes, incluyendo los que están de permiso
            // Ya que queremos que los pacientes de permiso sigan ocupando su cama
            $pacientesConCamaFisica = $clienteRepository->findClienteEnHabitacion($habitacion, true, true);
            
            if (count($pacientesConCamaFisica) < $totalCamas) {
                $resp[]  = $habitacion;
            }
        }
        return $resp;

    }

    public function findHabitacionSinCamasDisponibles($clienteRepository = null)
    {
        $resp = [];
        $todas = $this->findBy(array(), array('nombre' => 'ASC'));
        foreach ($todas as $habitacion) {
            $totalCamas = $habitacion->getCamasDisponibles();
            
            if ($clienteRepository) {
                // Usar el método de repositorio para contar pacientes incluyendo los de permiso
                // Ya que queremos que los pacientes de permiso sigan ocupando su cama
                $pacientesConCamaFisica = $clienteRepository->findClienteEnHabitacion($habitacion, true, true);
                
                if (count($pacientesConCamaFisica) >= $totalCamas) {
                    $resp[] = $habitacion;
                }
            } else {
                // Método antiguo basado en JSON de camas ocupadas (mantener por compatibilidad)
                $arrayCamas = $habitacion->getCamasOcupadas();
                if ($habitacion->getCamasDisponibles() == count($arrayCamas)) {
                    $resp[] = $habitacion;
                }
            }
        }
        return $resp;
    }

    public function getCamasDisp(int $id)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function getHabitacionesConPacientes()
    {
        $resp = [];
        $todas = $this->findAll();
        foreach ($todas as $habitacion) {
            $arrayCamas = $habitacion->getCamasOcupadas();

            if ($arrayCamas) {
                $resp[]  = $habitacion;
            }
        }
        return $resp;
    }

    public function findCamasOcupadasYDisponibles(): array
{
    $habitaciones = $this->findAll();
    $clienteRepository = $this->getEntityManager()->getRepository(\App\Entity\Cliente::class);
    $resultados = [];

    foreach ($habitaciones as $habitacion) {
        $numeroHabitacion = $habitacion->getNombre(); // Asegurar que es string
        $piso = $numeroHabitacion[0]; // Obtener el primer número como identificador del piso

        $camasTotales = $habitacion->getCamasDisponibles();
        
        // Calcular camas ocupadas reales consultando pacientes activos
        $pacientesConCamaFisica = $clienteRepository->findClienteEnHabitacion($habitacion, true, true);
        $camasOcupadasReales = [];
        foreach ($pacientesConCamaFisica as $paciente) {
            if ($paciente->getNCama() > 0) {
                $camasOcupadasReales[$paciente->getNCama()] = $paciente->getNCama();
            }
        }
        $camasOcupadas = count($camasOcupadasReales);

        // Calcular las camas disponibles
        $camasDisponibles = $camasTotales - $camasOcupadas;

        if (!isset($resultados['total'])) {
            $resultados['total'] = [
                'total_camas_disponibles' => 0,
                'total_camas_ocupadas' => 0,
                'total_camas' => 0
            ];
        }

        // Inicializar el piso si no existe en el array
        if (!isset($resultados['piso: ' . $piso])) {
            $resultados['piso: ' . $piso] = [
                'total_camas_disponibles' => 0,
                'total_camas_ocupadas' => 0,
                'total_camas' => 0
            ];
        }
        

        // Sumar las camas por piso
        $resultados['piso: ' . $piso]['total_camas_disponibles'] += $camasDisponibles;
        $resultados['piso: ' . $piso]['total_camas_ocupadas'] += $camasOcupadas;
        $resultados['piso: ' . $piso]['total_camas'] += $camasTotales;
        
        $resultados['total']['total_camas_disponibles'] += $camasDisponibles;
        $resultados['total']['total_camas_ocupadas'] += $camasOcupadas;
        $resultados['total']['total_camas'] += $camasTotales;
    }

    return $resultados;
}

    // /**
    //  * @return Habitacion[] Returns an array of Habitacion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Habitacion
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
