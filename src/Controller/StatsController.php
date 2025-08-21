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
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        // Recuperar filtros de fecha (desde - hasta)
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        // Obtener fechas desde la request o usar por defecto (mes actual)
        $fechaDesde = $request->query->get('fecha_desde');
        $fechaHasta = $request->query->get('fecha_hasta');
        
        if ($fechaDesde && $fechaHasta) {
            try {
                $startOfMonth = new \DateTime($fechaDesde . ' 00:00:00');
                $endOfMonth = new \DateTime($fechaHasta . ' 23:59:59');
            } catch (\Exception $e) {
                // Si hay error en las fechas, usar mes actual
                $startOfMonth = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
                $endOfMonth = clone $startOfMonth;
                $endOfMonth->modify('last day of this month 23:59:59');
            }
        } else {
            // Por defecto: mes actual
            $startOfMonth = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
            $endOfMonth = clone $startOfMonth;
            $endOfMonth->modify('last day of this month 23:59:59');
        }
        
        // Calcular la diferencia en días para mostrar en la etiqueta
        $diasPeriodo = $startOfMonth->diff($endOfMonth)->days + 1;
        
        // Datos para el panel de resumen con manejo de errores
        try {
            $totalHabitaciones = count($em->getRepository(Habitacion::class)->findAll());
            
            // Usar capacidad según el año del período consultado
            $year = (int)$startOfMonth->format('Y');
            $totalCamas = $this->getCapacidadCamasSegunAno($year);
            
            // Para períodos actuales (2025+), verificar datos reales en BD
            if ($year >= 2025) {
                $camasActuales = $em->createQuery('SELECT SUM(h.camasDisponibles) FROM App\Entity\Habitacion h')->getSingleScalarResult() ?: 0;
                if ($camasActuales > 0) {
                    $totalCamas = $camasActuales;
                }
            }
        } catch (\Exception $e) {
            $totalHabitaciones = 0;
            // Usar capacidad por defecto según año
            $year = (int)$startOfMonth->format('Y');
            $totalCamas = $this->getCapacidadCamasSegunAno($year);
        }
        
        try {
            // Consulta SQL directa para ingresos del período seleccionado
            $conn = $em->getConnection();
            $sql = "SELECT COUNT(id) as total FROM cliente WHERE f_ingreso BETWEEN :start AND :end";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startOfMonth->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endOfMonth->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $ingresosDelMes = $result->fetchOne() ?: 0;
        } catch (\Exception $e) {
            $ingresosDelMes = 0;
        }
        
        try {
            // Consulta SQL directa para egresos del período seleccionado
            $conn = $em->getConnection();
            $sql = "SELECT COUNT(id) as total FROM historia_paciente WHERE fecha_engreso BETWEEN :start AND :end";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startOfMonth->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endOfMonth->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $egresosDelMes = $result->fetchOne() ?: 0;
        } catch (\Exception $e) {
            $egresosDelMes = 0;
        }
        
        // Datos para gráficos de ocupación ajustados al período seleccionado
        $ocupacionPorDia = $this->getOcupacionPorDia($em, $startOfMonth, $endOfMonth);
        
        // Verificar si estamos consultando un período futuro
        $hoy = new \DateTime();
        $esPeriodoFuturo = $startOfMonth > $hoy;
        
        if ($esPeriodoFuturo) {
            // Si es un período futuro, la ocupación es 0%
            $ocupacionActual = 0;
        } else {
            // Para períodos pasados o en curso, calculamos el promedio correctamente
            $diasValidos = [];
            
            // Para períodos pasados o en curso, considerar todos los días hasta hoy
            foreach ($ocupacionPorDia as $index => $dia) {
                // Reconstruir fecha usando el índice y la fecha de inicio
                $currentDate = clone $startOfMonth;
                $currentDate->modify("+{$index} days");
                
                // Si la fecha es anterior o igual a hoy, incluirla en el cálculo
                if ($currentDate <= $hoy) {
                    $diasValidos[] = $dia['porcentaje'];
                }
            }
            
            $totalDias = count($diasValidos);
            $sumaOcupacion = array_sum($diasValidos);
            $ocupacionActual = $totalDias > 0 ? round($sumaOcupacion / $totalDias) : 0;
            
            // Asegurarse que ocupacionActual sea un número positivo
            if ($ocupacionActual < 0) {
                $ocupacionActual = 0;
            }
        }
        
        // Definir los nombres de los meses en español
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        // --- Métrica: días de internación por patología ---
        // Mapeo de IDs a nombres de patologías
        $patologiasLabels = [
            1 => 'Neurológicas',
            2 => 'Traumatológicas',
            3 => 'Respiratorias',
            4 => 'Paliativos',
            5 => 'Patologías laborales',
            0 => 'Otra Patología',
        ];
        
        $conn = $em->getConnection();
        $sql = "SELECT patologia, patologia_especifica, fecha_ingreso, fecha_engreso FROM historia_paciente 
               WHERE modalidad = '2' AND fecha_ingreso IS NOT NULL 
               AND fecha_engreso IS NOT NULL AND patologia IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $internaciones = $result->fetchAllAssociative();

        // Estructura para almacenar datos por patología principal y subcategoría
        $diasPorPatologia = [];
        $diasPorSubcategoria = [];
        
        // Mapeamos las subcategorías conocidas por patología principal
        $subcategoriasConocidas = [
            1 => [ // Neurológicas
                'acv hemorragico' => 'ACV Hemorrágico',
                'acv izquemico' => 'ACV Isquémico',
                'ela' => 'ELA',
                'guillain barre' => 'Guillain Barré',
                'pop' => 'POP Neurocirugía',
                'tec' => 'TEC',
                'trauma medular' => 'Trauma Medular',
                'otras' => 'Otras Neurológicas'
            ],
            2 => [ // Traumatológicas
                'amputaciones' => 'Amputaciones',
                'politrauma' => 'Politrauma',
                'pop' => 'POP Traumatología',
                'otras' => 'Otras Traumatológicas'
            ],
            3 => [ // Respiratorias
                'pop' => 'POP Cirugía Torácica',
                'rehabilitacion respiratoria' => 'Rehabilitación Respiratoria',
                'otras' => 'Otras Respiratorias'
            ],
            4 => [ // Paliativos
                'ca' => 'Cáncer',
                'otros' => 'Otros Paliativos'
            ],
            5 => [ // Laborales
                'otros' => 'Otros Laborales'
            ],
            0 => [ // Otra
                'otras' => 'Sin Especificar'
            ]
        ];

        // Procesar cada internación
        foreach ($internaciones as $row) {
            $patologiaId = $row['patologia'];
            $subcategoria = !empty($row['patologia_especifica']) ? strtolower($row['patologia_especifica']) : 'otras';
            
            // Determinar nombre de patología principal
            $nombrePatologia = isset($patologiasLabels[$patologiaId]) ? $patologiasLabels[$patologiaId] : ('Patología ' . $patologiaId);
            
            // Determinar nombre de subcategoría
            $nombreSubcategoria = isset($subcategoriasConocidas[$patologiaId][$subcategoria]) 
                ? $subcategoriasConocidas[$patologiaId][$subcategoria] 
                : ucfirst($subcategoria);
            
            // Calcular días de internación
            $fechaIngreso = $row['fecha_ingreso'];
            $fechaEngreso = $row['fecha_engreso'];
            if ($fechaIngreso && $fechaEngreso) {
                $dias = (new \DateTime($fechaIngreso))->diff(new \DateTime($fechaEngreso))->days + 1;
                
                // Agregar a patología principal
                $diasPorPatologia[$nombrePatologia][] = $dias;
                
                // Agregar a subcategoría
                $clave = $nombrePatologia . '|' . $nombreSubcategoria;
                $diasPorSubcategoria[$clave][] = $dias;
            }
        }
        
        // Calcular estadísticas para patologías principales
        $statsInternacion = [];
        foreach ($diasPorPatologia as $patologia => $diasArr) {
            if (count($diasArr) > 0) {
                $statsInternacion[$patologia] = [
                    'promedio' => round(array_sum($diasArr) / count($diasArr), 1),
                    'min' => min($diasArr),
                    'max' => max($diasArr),
                    'total' => array_sum($diasArr),
                    'casos' => count($diasArr),
                    'id' => strtolower(str_replace(' ', '-', $patologia)),
                    'subcategorias' => []
                ];
            }
        }
        
        // Calcular estadísticas para subcategorías y agrupar por patología principal
        foreach ($diasPorSubcategoria as $clave => $diasArr) {
            list($patologia, $subcategoria) = explode('|', $clave);
            
            if (count($diasArr) > 0 && isset($statsInternacion[$patologia])) {
                $statsInternacion[$patologia]['subcategorias'][$subcategoria] = [
                    'promedio' => round(array_sum($diasArr) / count($diasArr), 1),
                    'min' => min($diasArr),
                    'max' => max($diasArr),
                    'total' => array_sum($diasArr),
                    'casos' => count($diasArr)
                ];
            }
        }

        return $this->render('stats/index.html.twig', [
            'totalHabitaciones' => $totalHabitaciones,
            'totalCamas' => $totalCamas,
            'ingresosDelMes' => $ingresosDelMes,
            'egresosDelMes' => $egresosDelMes,
            'ocupacionPorDia' => json_encode($ocupacionPorDia),
            'fechaDesde' => $startOfMonth->format('Y-m-d'),
            'fechaHasta' => $endOfMonth->format('Y-m-d'),
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'periodLabel' => $startOfMonth->format('d/m/Y') . ' - ' . $endOfMonth->format('d/m/Y') . ' (' . $diasPeriodo . ' días)',
            'statsInternacion' => $statsInternacion,
            'ocupacionActual' => round($ocupacionActual),
        ]);
    }
    
    /**
     * @Route("/comparacion", name="app_stats_comparacion")
     */
    public function comparacion(EntityManagerInterface $em, Request $request): Response
    {
        // Obtener el período base desde la request o usar mes actual
        $fechaDesde = $request->query->get('fecha_desde');
        $fechaHasta = $request->query->get('fecha_hasta');
        $anosComparacion = (int)$request->query->get('anos_comparacion', 3);
        
        // Validar que el número de años esté en un rango razonable
        if ($anosComparacion < 2 || $anosComparacion > 10) {
            $anosComparacion = 3;
        }
        
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        if ($fechaDesde && $fechaHasta) {
            try {
                $basePeriodStart = new \DateTime($fechaDesde . ' 00:00:00');
                $basePeriodEnd = new \DateTime($fechaHasta . ' 23:59:59');
            } catch (\Exception $e) {
                // Si hay error, usar mes actual
                $basePeriodStart = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
                $basePeriodEnd = clone $basePeriodStart;
                $basePeriodEnd->modify('last day of this month 23:59:59');
            }
        } else {
            // Por defecto: mes actual
            $basePeriodStart = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
            $basePeriodEnd = clone $basePeriodStart;
            $basePeriodEnd->modify('last day of this month 23:59:59');
        }
        
        // Calcular los mismos períodos para los años solicitados
        $periodos = [];
        $year = (int)$basePeriodStart->format('Y');
        
        for ($i = 0; $i < $anosComparacion; $i++) {
            $yearToCompare = $year - $i;
            
            // Crear las fechas para este año
            $startDate = clone $basePeriodStart;
            $endDate = clone $basePeriodEnd;
            $startDate->setDate($yearToCompare, (int)$basePeriodStart->format('m'), (int)$basePeriodStart->format('d'));
            $endDate->setDate($yearToCompare, (int)$basePeriodEnd->format('m'), (int)$basePeriodEnd->format('d'));
            
            $periodos[] = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'year' => $yearToCompare,
                'label' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')
            ];
        }
        
        // Obtener datos para cada período
        $datosComparacion = [];
        
        foreach ($periodos as $index => $periodo) {
            // Obtener métricas para este período
            $metrics = $this->getMetricsForPeriod($em, $periodo['startDate'], $periodo['endDate']);
            
            $datosComparacion[$index] = [
                'period' => $periodo,
                'periodLabel' => $periodo['label'],
                'metrics' => $metrics,
                'ocupacionPorDia' => $this->getOcupacionPorDia($em, $periodo['startDate'], $periodo['endDate'])
            ];
        }
        
        // Calcular datos comparativos
        $comparaciones = $this->calculateComparisons($datosComparacion);
        
        return $this->render('stats/comparacion.html.twig', [
            'datosComparacion' => $datosComparacion,
            'comparaciones' => $comparaciones,
            'periodos' => $periodos,
            'fechaDesde' => $basePeriodStart->format('Y-m-d'),
            'fechaHasta' => $basePeriodEnd->format('Y-m-d'),
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'anosComparacion' => $anosComparacion
        ]);
    }
    
    /**
     * Obtiene métricas para un período específico
     */
    private function getMetricsForPeriod(EntityManagerInterface $em, \DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $conn = $em->getConnection();
            
            // Ingresos del período
            $sql = "SELECT COUNT(id) as total FROM cliente WHERE f_ingreso BETWEEN :start AND :end";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endDate->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $ingresos = $result->fetchOne() ?: 0;
            
            // Egresos del período
            $sql = "SELECT COUNT(id) as total FROM historia_paciente WHERE fecha_engreso BETWEEN :start AND :end";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endDate->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $egresos = $result->fetchOne() ?: 0;
            
            // Ocupación promedio del período
            $ocupacionData = $this->getOcupacionPorDia($em, $startDate, $endDate);
            $diasValidos = array_filter($ocupacionData, function($dia) {
                return $dia['porcentaje'] >= 0;
            });
            
            $ocupacionPromedio = 0;
            if (count($diasValidos) > 0) {
                $sumaOcupacion = array_sum(array_column($diasValidos, 'porcentaje'));
                $ocupacionPromedio = $sumaOcupacion / count($diasValidos);
            }
            
            // Días de internación promedio
            $sql = "SELECT AVG(DATEDIFF(fecha_engreso, fecha_ingreso) + 1) as promedio 
                   FROM historia_paciente 
                   WHERE fecha_ingreso BETWEEN :start AND :end 
                   AND fecha_engreso IS NOT NULL 
                   AND modalidad = '2'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end', $endDate->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $diasInternacionPromedio = $result->fetchOne() ?: 0;
            
            return [
                'ingresos' => (int)$ingresos,
                'egresos' => (int)$egresos,
                'ocupacionPromedio' => round($ocupacionPromedio, 1),
                'diasInternacionPromedio' => round((float)$diasInternacionPromedio, 1),
                'rotacion' => $ingresos > 0 ? round($egresos / $ingresos * 100, 1) : 0
            ];
        } catch (\Exception $e) {
            return [
                'ingresos' => 0,
                'egresos' => 0,
                'ocupacionPromedio' => 0,
                'diasInternacionPromedio' => 0,
                'rotacion' => 0
            ];
        }
    }
    
    /**
     * Calcula comparaciones entre períodos
     */
    private function calculateComparisons(array $datosComparacion): array
    {
        $comparaciones = [];
        
        if (count($datosComparacion) < 2) {
            return $comparaciones;
        }
        
        // Comparar cada período con el anterior
        for ($i = 1; $i < count($datosComparacion); $i++) {
            $actual = $datosComparacion[$i]['metrics'];
            $anterior = $datosComparacion[$i-1]['metrics'];
            
            $comparaciones[] = [
                'fromLabel' => $datosComparacion[$i-1]['periodLabel'],
                'toLabel' => $datosComparacion[$i]['periodLabel'],
                'ingresos' => $this->calculatePercentageChange($anterior['ingresos'], $actual['ingresos']),
                'egresos' => $this->calculatePercentageChange($anterior['egresos'], $actual['egresos']),
                'ocupacionPromedio' => $this->calculatePercentageChange($anterior['ocupacionPromedio'], $actual['ocupacionPromedio']),
                'diasInternacionPromedio' => $this->calculatePercentageChange($anterior['diasInternacionPromedio'], $actual['diasInternacionPromedio']),
                'rotacion' => $this->calculatePercentageChange($anterior['rotacion'], $actual['rotacion'])
            ];
        }
        
        return $comparaciones;
    }
    
    /**
     * Calcula el cambio porcentual entre dos valores
     */
    private function calculatePercentageChange($old, $new): array
    {
        if ($old == 0) {
            return [
                'value' => $new,
                'change' => $new > 0 ? 100 : 0,
                'trend' => $new > 0 ? 'up' : 'neutral',
                'formatted' => $new > 0 ? '+100%' : '0%'
            ];
        }
        
        $change = (($new - $old) / $old) * 100;
        $trend = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');
        $formatted = ($change > 0 ? '+' : '') . round($change, 1) . '%';
        
        return [
            'value' => $new,
            'change' => round($change, 1),
            'trend' => $trend,
            'formatted' => $formatted
        ];
    }
    
    /**
     * @Route("/ocupacion", name="app_stats_ocupacion")
     */
    public function ocupacion(Request $request, EntityManagerInterface $em): Response
    {
        // Recuperar filtros de fecha (mes y año)
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        $month = (int)$request->query->get('month', $currentMonth);
        $year = (int)$request->query->get('year', $currentYear);
        
        // Validación básica
        if ($month < 1 || $month > 12) {
            $month = $currentMonth;
        }
        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }
        
        $periodo = $request->query->get('periodo', 'mes');
        
        // Definir los nombres de los meses en español
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        // Definir el rango de fechas según el período seleccionado
        switch ($periodo) {
            case 'semana':
                // Primero establecemos el primer día del mes seleccionado
                $baseDate = new \DateTime("$year-$month-01");
                // Luego obtenemos el primer lunes del mes
                $startDate = clone $baseDate;
                if ($startDate->format('N') != 1) {
                    $startDate->modify('next monday');
                }
                $endDate = clone $startDate;
                $endDate->modify('+6 days');
                break;
            case 'trimestre':
                // Calculamos el trimestre basado en el mes seleccionado
                $quarterStart = floor(($month - 1) / 3) * 3 + 1;
                $startDate = new \DateTime("$year-$quarterStart-01");
                $endDate = clone $startDate;
                $endDate->modify('+2 months')->modify('last day of this month');
                break;
            case 'anio':
                $startDate = new \DateTime("$year-01-01");
                $endDate = new \DateTime("$year-12-31");
                break;
            default: // mes
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');
                break;
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
            'camasPorHabitacion' => $camasPorHabitacion,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'periodLabel' => $meses[$startDate->format('n') - 1] . ' ' . $startDate->format('Y')
        ]);
    }
    
    /**
     * @Route("/altas", name="app_stats_altas")
     */
    public function altas(Request $request, EntityManagerInterface $em): Response
    {
        // Recuperar filtros de fecha (mes y año)
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        $month = (int)$request->query->get('month', 0); // 0 = todos los meses
        $year = (int)$request->query->get('year', $currentYear);
        
        // Validación básica
        if ($month < 0 || $month > 12) {
            $month = 0;
        }
        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }
        
        $conn = $em->getConnection();
        // Altas por mes
        if ($month > 0) {
            // Si se especifica un mes, filtrar por ese mes específico
            $sql = "SELECT MONTH(fecha_engreso) as mes, COUNT(id) as total FROM historia_paciente 
                   WHERE fecha_engreso IS NOT NULL 
                   AND YEAR(fecha_engreso) = :year 
                   AND MONTH(fecha_engreso) = :month 
                   GROUP BY mes";
            $stmt = $conn->prepare($sql);
            $egresos = $stmt->executeQuery(['year' => $year, 'month' => $month])->fetchAllAssociative();
        } else {
            // Si no se especifica mes, mostrar todos los meses del año
            $sql = "SELECT MONTH(fecha_engreso) as mes, COUNT(id) as total FROM historia_paciente 
                   WHERE fecha_engreso IS NOT NULL 
                   AND YEAR(fecha_engreso) = :year 
                   GROUP BY mes";
            $stmt = $conn->prepare($sql);
            $egresos = $stmt->executeQuery(['year' => $year])->fetchAllAssociative();
        }
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $datosPorMes = array_fill(0, 12, 0);
        foreach ($egresos as $egreso) {
            $datosPorMes[$egreso['mes'] - 1] = (int)$egreso['total'];
        }
        // Clasificación de altas
        if ($month > 0) {
            $sql2 = "SELECT modalidad, motivo_derivacion, derivado_en FROM historia_paciente 
                    WHERE fecha_engreso IS NOT NULL 
                    AND YEAR(fecha_engreso) = :year
                    AND MONTH(fecha_engreso) = :month";
            $stmt2 = $conn->prepare($sql2);
            $altas = $stmt2->executeQuery(['year' => $year, 'month' => $month])->fetchAllAssociative();
        } else {
            $sql2 = "SELECT modalidad, motivo_derivacion, derivado_en FROM historia_paciente 
                    WHERE fecha_engreso IS NOT NULL 
                    AND YEAR(fecha_engreso) = :year";
            $stmt2 = $conn->prepare($sql2);
            $altas = $stmt2->executeQuery(['year' => $year])->fetchAllAssociative();
        }
        $categorias = [
            'Recuperación' => 0,
            'Derivación' => 0,
            'Alta voluntaria' => 0,
            'Fallecimiento' => 0,
            'Otras' => 0
        ];
        foreach ($altas as $alta) {
            // Modalidad 2: recuperación (internación)
            if ($alta['modalidad'] == '2') {
                $categorias['Recuperación']++;
            } elseif (!empty($alta['motivo_derivacion']) || !empty($alta['derivado_en'])) {
                $categorias['Derivación']++;
            } elseif ($alta['modalidad'] == '1') {
                $categorias['Alta voluntaria']++;
            } elseif ($alta['modalidad'] == '4') {
                $categorias['Fallecimiento']++;
            } else {
                $categorias['Otras']++;
            }
        }
        // Crear una etiqueta para el período seleccionado
        $periodLabel = $year;
        if ($month > 0) {
            $date = new \DateTime("$year-$month-01");
            $periodLabel = $meses[$month - 1] . ' ' . $year;
        }
        
        return $this->render('stats/altas.html.twig', [
            'year' => $year,
            'month' => $month,
            'meses' => $meses,
            'datosPorMes' => $datosPorMes,
            'categorias' => $categorias,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'periodLabel' => $periodLabel
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
     * Determina la capacidad de camas según el año histórico
     */
    private function getCapacidadCamasSegunAno(int $year): int
    {
        if ($year <= 2023) {
            return 30; // Desde inicio de actividades hasta 2024
        } elseif ($year == 2024) {
            return 40; // Entre 2024 y 2025
        } else {
            return 52; // Desde 2025 en adelante
        }
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
        
        // Determinar la capacidad de camas según el año del período
        $year = (int)$startDate->format('Y');
        $totalCamas = $this->getCapacidadCamasSegunAno($year);
        
        // Solo usar la consulta a la BD para períodos actuales (2025+)
        if ($year >= 2025) {
            try {
                $conn = $em->getConnection();
                $sql = "SELECT SUM(camas_disponibles) as total FROM habitacion";
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery();
                $camasActuales = (int)$result->fetchOne() ?: 0;
                
                // Si hay datos actuales válidos, usarlos para períodos actuales
                if ($camasActuales > 0) {
                    $totalCamas = $camasActuales;
                }
            } catch (\Exception $e) {
                // Si hay un error, usar la capacidad histórica predeterminada
                error_log('Error consultando camas actuales: ' . $e->getMessage());
            }
        }
        
        // Consultar el historial de habitaciones para el período usando SQL directo
        $historico = [];
        try {
            $conn = $em->getConnection();
            $sql = "SELECT DATE(fecha) as fecha, COUNT(DISTINCT cliente_id) as ocupadas 
                   FROM historia_habitaciones 
                   WHERE fecha BETWEEN :start AND :end 
                   GROUP BY DATE(fecha)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('start', $startDate->format('Y-m-d'));
            $stmt->bindValue('end', $endDate->format('Y-m-d'));
            $result = $stmt->executeQuery();
            $historico = $result->fetchAllAssociative();
        } catch (\Exception $e) {
            // Si hay un error, inicializamos con un arreglo vacío e imprimimos el error
            error_log('Error obteniendo historico: ' . $e->getMessage());
            $historico = [];
        }
        
        $ocupacionPorDia = [];
        $currentDate = clone $startDate;
        
        // Precalculamos la ocupación actual para asegurar que siempre tengamos datos reales
        $ocupacionActualCalculada = 0;
        try {
            // Calcular ocupación actual real
            $habitaciones = $em->getRepository('App\\Entity\\Habitacion')->findAll();
            $ocupadasHoy = 0;
            foreach ($habitaciones as $hab) {
                $camasOcupadas = $hab->getCamasOcupadas();
                // Validar que camasOcupadas sea un array antes de contar
                if (is_array($camasOcupadas) && !empty($camasOcupadas)) {
                    $ocupadasHoy += count(array_filter($camasOcupadas));
                }
            }
            $ocupacionActualCalculada = $ocupadasHoy;
        } catch (\Exception $e) {
            // Si hay un error, registrarlo pero continuar
            error_log('Error calculando ocupación actual: ' . $e->getMessage());
        }
        
        // Organizar el histórico por fecha para búsqueda más eficiente
        $historicoIndexado = [];
        foreach ($historico as $record) {
            $historicoIndexado[$record['fecha']] = (int)$record['ocupadas'];
        }
        
        // Verificar si estamos en un período futuro
        $hoy = new \DateTime();
        $hoy->setTime(0, 0, 0); // Establecer a medianoche para comparaciones justas
        $esPeriodoFuturo = $startDate > $hoy;
        
        // Variable para almacenar el último valor válido conocido
        $ultimoValorValido = $ocupacionActualCalculada;
        
        // Verificar si tenemos datos históricos para este período
        $hayDatosHistoricos = !empty($historico);
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $ocupadas = 0;
            
            // Comparar solo las fechas (sin hora) para determinar si es futuro
            $currentDateOnly = clone $currentDate;
            $currentDateOnly->setTime(0, 0, 0);
            $esFuturo = $currentDateOnly > $hoy;
            
            // Solo excluir si es realmente una fecha futura (después de hoy)
            if ($esFuturo) {
                // No agregar este punto al array para que la línea se corte en "hoy"
                $currentDate->modify('+1 day');
                continue;
            }
            
            // Si hay datos históricos para esta fecha específica, usarlos (prioridad máxima)
            if (isset($historicoIndexado[$dateStr])) {
                $ocupadas = $historicoIndexado[$dateStr];
                $ultimoValorValido = $ocupadas; // Actualizar último valor válido
            }
            // Si es el día actual, usar ocupación actual calculada
            else if ($dateStr === $hoy->format('Y-m-d') && $ocupacionActualCalculada > 0) {
                $ocupadas = $ocupacionActualCalculada;
                $ultimoValorValido = $ocupadas;
            }
            // Para fechas pasadas sin dato específico pero con histórico general
            else if ($currentDateOnly < $hoy && $hayDatosHistoricos) {
                // Usar el último valor válido para mantener continuidad
                $ocupadas = $ultimoValorValido;
            }
            // Caso base: sin datos históricos
            else {
                $ocupadas = 0;
            }
            
            // Evitar división por cero y asegurar un valor positivo
            $porcentaje = $totalCamas > 0 ? max(0, round(($ocupadas / $totalCamas) * 100, 2)) : 0;
            
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