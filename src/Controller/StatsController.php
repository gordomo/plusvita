<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Habitacion;
use App\Entity\HistoriaEgreso;
use App\Entity\HistoriaHabitaciones;
use App\Entity\HistoriaIngreso;
use App\Entity\Item;
use App\Entity\Movimiento;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/estadisticas")
 */
class StatsController extends AbstractController
{
    /**
     * @Route("/", name="app_stats_index")
     */
    public function index(EntityManagerInterface $em): Response
    {
        // Datos para el panel de resumen 
        $totalHabitaciones = $em->getRepository(Habitacion::class)->count([]);
        $totalCamas = $em->createQuery('SELECT SUM(h.camasDisponibles) FROM App\Entity\Habitacion h')->getSingleScalarResult() ?: 0;
        
        // Contar total de ingresos y egresos en el mes actual
        $startOfMonth = new \DateTime('first day of this month midnight');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');
        
        $ingresosDelMes = $em->getRepository(Cliente::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.fIngreso BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
            
        $egresosDelMes = $em->getRepository(HistoriaEgreso::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.fecha BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Datos para gráficos de ocupación
        $ocupacionPorDia = $this->getOcupacionPorDia($em);
        
        return $this->render('stats/index.html.twig', [
            'totalHabitaciones' => $totalHabitaciones,
            'totalCamas' => $totalCamas,
            'ingresosDelMes' => $ingresosDelMes,
            'egresosDelMes' => $egresosDelMes,
            'ocupacionPorDia' => json_encode($ocupacionPorDia),
        ]);
    }
    
    /**
     * @Route("/ocupacion", name="app_stats_ocupacion")
     */
    public function ocupacion(Request $request, EntityManagerInterface $em): Response
    {
        $periodo = $request->query->get('periodo', 'mes');
        
        // Definir el rango de fechas según el período seleccionado
        switch ($periodo) {
            case 'semana':
                $startDate = new \DateTime('monday this week');
                $endDate = new \DateTime('sunday this week');
                break;
            case 'trimestre':
                $startDate = new \DateTime('first day of this month midnight');
                $startDate->modify('-' . ($startDate->format('m') % 3 - 1) . ' month');
                $endDate = clone $startDate;
                $endDate->modify('+2 month');
                $endDate->modify('last day of this month');
                break;
            case 'anio':
                $startDate = new \DateTime('first day of january this year');
                $endDate = new \DateTime('last day of december this year');
                break;
            default: // mes
                $startDate = new \DateTime('first day of this month');
                $endDate = new \DateTime('last day of this month');
        }
        
        // Calcular ocupación diaria para el rango de fechas
        $ocupacionPorDia = $this->getOcupacionPorDia($em, $startDate, $endDate);
        
        // Calcular promedio de ocupación para el período
        $totalDias = count($ocupacionPorDia);
        $sumaOcupacion = array_sum(array_column($ocupacionPorDia, 'porcentaje'));
        $promedioOcupacion = $totalDias > 0 ? $sumaOcupacion / $totalDias : 0;
        
        // Obtener datos de camas por habitación para mostrar en barras
        $habitaciones = $em->getRepository(Habitacion::class)->findAll();
        $camasPorHabitacion = [];
        
        foreach ($habitaciones as $habitacion) {
            $camasOcupadas = $habitacion->getCamasOcupadas() ? count($habitacion->getCamasOcupadas()) : 0;
            $camasTotales = $habitacion->getCamasDisponibles();
            
            $camasPorHabitacion[] = [
                'nombre' => $habitacion->getNombre(),
                'ocupadas' => $camasOcupadas,
                'disponibles' => $camasTotales - $camasOcupadas,
                'porcentaje' => $camasTotales > 0 ? round(($camasOcupadas / $camasTotales) * 100, 2) : 0
            ];
        }
        
        return $this->render('stats/ocupacion.html.twig', [
            'periodo' => $periodo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'ocupacionPorDia' => json_encode($ocupacionPorDia),
            'promedioOcupacion' => $promedioOcupacion,
            'camasPorHabitacion' => $camasPorHabitacion
        ]);
    }
    
    /**
     * @Route("/altas", name="app_stats_altas")
     */
    public function altas(Request $request, EntityManagerInterface $em): Response
    {
        $year = $request->query->get('year', date('Y'));
        
        // Obtener todos los egresos del año agrupados por mes
        $egresos = $em->getRepository(HistoriaEgreso::class)
            ->createQueryBuilder('e')
            ->select('MONTH(e.fecha) as mes, COUNT(e.id) as total')
            ->where('YEAR(e.fecha) = :year')
            ->setParameter('year', $year)
            ->groupBy('mes')
            ->getQuery()
            ->getResult();
            
        // Formatear datos para gráfico
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $datosPorMes = array_fill(0, 12, 0);
        
        foreach ($egresos as $egreso) {
            $datosPorMes[$egreso['mes'] - 1] = (int)$egreso['total'];
        }
        
        // Obtener análisis de texto de egresos (epicrisis)
        // Esto es una simplificación - idealmente usaríamos análisis de texto más avanzado
        $egresoTextos = $em->getRepository(HistoriaEgreso::class)
            ->createQueryBuilder('e')
            ->select('e.epicrisis_alta')
            ->where('e.epicrisis_alta IS NOT NULL')
            ->andWhere('YEAR(e.fecha) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getResult();
            
        // Análisis simple de palabras clave para categorizar altas
        $palabrasClave = [
            'Recuperación' => ['recuperado', 'mejoria', 'favorable', 'estable'],
            'Derivación' => ['derivado', 'traslado', 'transferido'],
            'Alta voluntaria' => ['voluntaria', 'solicitud del paciente', 'contra indicación'],
            'Fallecimiento' => ['fallecido', 'defunción', 'óbito']
        ];
        
        $categorias = array_fill_keys(array_keys($palabrasClave), 0);
        $otrasAltas = 0;
        
        foreach ($egresoTextos as $texto) {
            $epicrisis = strtolower($texto['epicrisis_alta'] ?? '');
            $categorizado = false;
            
            foreach ($palabrasClave as $categoria => $palabras) {
                foreach ($palabras as $palabra) {
                    if (strpos($epicrisis, $palabra) !== false) {
                        $categorias[$categoria]++;
                        $categorizado = true;
                        break 2;
                    }
                }
            }
            
            if (!$categorizado) {
                $otrasAltas++;
            }
        }
        
        // Añadir "Otras" al arreglo de categorías
        $categorias['Otras'] = $otrasAltas;
        
        return $this->render('stats/altas.html.twig', [
            'year' => $year,
            'meses' => $meses,
            'datosPorMes' => $datosPorMes,
            'categorias' => $categorias
        ]);
    }
    
    /**
     * @Route("/inventario", name="app_stats_inventario")
     */
    public function inventario(EntityManagerInterface $em): Response
    {
        // Obtener estadísticas de inventario por tipo
        $itemsPorTipo = $em->createQuery('
            SELECT t.nombre as tipo, COUNT(i.id) as cantidad
            FROM App\Entity\Item i
            JOIN i.tipo t
            GROUP BY t.id
        ')->getResult();
        
        // Obtener movimientos recientes para análisis de tendencias
        $startDate = new \DateTime('-30 days');
        
        // Usar QueryBuilder en lugar de DQL para evitar el uso de DATE()
        $qb = $em->createQueryBuilder();
        $movimientosPorDia = $qb->select('SUBSTRING(m.fecha, 1, 10) as fecha, COUNT(m.id) as cantidad')
            ->from('App\Entity\Movimiento', 'm')
            ->where('m.fecha >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('fecha')
            ->orderBy('fecha', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Formatear datos para gráficos
        $fechas = [];
        $cantidades = [];
        
        foreach ($movimientosPorDia as $movimiento) {
            // Convertir la fecha extraída a DateTime para formatearla
            $fechaObj = \DateTime::createFromFormat('Y-m-d', $movimiento['fecha']);
            if ($fechaObj) {
                $fechas[] = $fechaObj->format('d/m');
                $cantidades[] = $movimiento['cantidad'];
            }
        }
        
        // Obtener items por ubicación
        $itemsPorUbicacion = $em->createQuery('
            SELECT u.nombre as ubicacion, COUNT(i.id) as cantidad
            FROM App\Entity\Item i
            JOIN i.ubicacion_actual u
            GROUP BY u.id
        ')->getResult();
        
        return $this->render('stats/inventario.html.twig', [
            'itemsPorTipo' => $itemsPorTipo,
            'fechas' => json_encode($fechas),
            'cantidades' => json_encode($cantidades),
            'itemsPorUbicacion' => $itemsPorUbicacion
        ]);
    }
    
    /**
     * Calcula la ocupación de camas por día para un período específico
     */
    private function getOcupacionPorDia(EntityManagerInterface $em, \DateTime $startDate = null, \DateTime $endDate = null): array
    {
        if (!$startDate) {
            $startDate = new \DateTime('-30 days');
        }
        
        if (!$endDate) {
            $endDate = new \DateTime();
        }
        
        // Obtener el total de camas disponibles
        $totalCamas = $em->createQuery('SELECT SUM(h.camasDisponibles) FROM App\Entity\Habitacion h')->getSingleScalarResult() ?: 0;
        
        // Consultar el historial de habitaciones para el período
        $qb = $em->createQueryBuilder();
        $historico = $qb->select('SUBSTRING(h.fecha, 1, 10) as fecha, COUNT(h.id) as ocupadas')
            ->from('App\Entity\HistoriaHabitaciones', 'h')
            ->where('h.fecha BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('fecha')
            ->getQuery()
            ->getResult();
            
        // Formatear los datos por día
        $ocupacionPorDia = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $ocupadas = 0;
            
            // Buscar si hay datos para esta fecha
            foreach ($historico as $record) {
                if ($record['fecha'] == $dateStr) {
                    $ocupadas = $record['ocupadas'];
                    break;
                }
            }
            
            $porcentaje = $totalCamas > 0 ? round(($ocupadas / $totalCamas) * 100, 2) : 0;
            
            $ocupacionPorDia[] = [
                'fecha' => $currentDate->format('d/m'),
                'ocupadas' => $ocupadas,
                'total' => $totalCamas,
                'porcentaje' => $porcentaje
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return $ocupacionPorDia;
    }
}