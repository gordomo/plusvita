<?php

namespace App\Repository;

use App\Entity\SignosVitales;
use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SignosVitales|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignosVitales|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignosVitales[]    findAll()
 * @method SignosVitales[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignosVitalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignosVitales::class);
    }

    /**
     * Busca el primer registro de signos vitales para un paciente en una fecha y turno específicos
     * (puede haber múltiples tomas, este método devuelve solo la primera)
     */
    public function findPorPacienteFechaTurno(Cliente $paciente, \DateTimeInterface $fecha, string $turno): ?SignosVitales
    {
        return $this->createQueryBuilder('sv')
            ->andWhere('sv.paciente = :paciente')
            ->andWhere('sv.fecha = :fecha')
            ->andWhere('sv.turno = :turno')
            ->setParameter('paciente', $paciente)
            ->setParameter('fecha', $fecha)
            ->setParameter('turno', $turno)
            ->orderBy('sv.fechaHoraRegistro', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Verifica si los signos vitales ya fueron completados para un turno específico
     * (verifica si hay al menos una toma completada)
     */
    public function estaCompletado(Cliente $paciente, \DateTimeInterface $fecha, string $turno): bool
    {
        $tomas = $this->findTodasPorPacienteFechaTurno($paciente, $fecha, $turno);
        // Si hay al menos una toma completada, se considera completado
        foreach ($tomas as $toma) {
            if ($toma->isCompletado()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtiene todas las tomas de signos vitales para un paciente en una fecha y turno específicos
     * Ordenadas cronológicamente (la más antigua primero)
     */
    public function findTodasPorPacienteFechaTurno(Cliente $paciente, \DateTimeInterface $fecha, string $turno): array
    {
        return $this->createQueryBuilder('sv')
            ->andWhere('sv.paciente = :paciente')
            ->andWhere('sv.fecha = :fecha')
            ->andWhere('sv.turno = :turno')
            ->setParameter('paciente', $paciente)
            ->setParameter('fecha', $fecha)
            ->setParameter('turno', $turno)
            ->orderBy('sv.fechaHoraRegistro', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

