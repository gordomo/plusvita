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
        
        // Crear objetos de fecha para el período seleccionado
        $startOfMonth = new \DateTime("$year-$month-01 00:00:00");
        $endOfMonth = clone $startOfMonth;
        $endOfMonth->modify('last day of this month 23:59:59');
        
        // Datos para el panel de resumen con manejo de errores
        try {
            $totalHabitaciones = count($em->getRepository(Habitacion::class)->findAll());
            $totalCamas = $em->createQuery('SELECT SUM(h.camasDisponibles) FROM App\Entity\Habitacion h')->getSingleScalarResult() ?: 0;
        } catch (\Exception $e) {
            $totalHabitaciones = 0;
            $totalCamas = 0;
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
            
            // En períodos pasados o en curso, considerar todos los días
            // Para el mes actual, solo considerar los días hasta hoy
            foreach ($ocupacionPorDia as $index => $dia) {
                // Crear fecha completa con año para comparación
                $fechaCompleta = \DateTime::createFromFormat(
                    'd/m/Y', 
                    $dia['fecha'] . '/' . $year
                );
                
                if (!$fechaCompleta) {
                    // Si hay error al formatear fecha, usar el índice y reconstruir fecha
                    $currentDate = clone $startOfMonth;
                    $currentDate->modify("+{$index} days");
                    
                    // Si es mes pasado o día hasta hoy, incluirlo
                    if ($currentDate->format('m') < $currentMonth || 
                        ($currentDate->format('m') == $currentMonth && $currentDate->format('Y') < $currentYear) || 
                        $currentDate <= $hoy) {
                        $diasValidos[] = $dia['porcentaje'];
                    }
                } else {
                    // Si es mes pasado o día hasta hoy, incluirlo
                    if ($fechaCompleta <= $hoy || $month < $currentMonth || $year < $currentYear) {
                        $diasValidos[] = $dia['porcentaje'];
                    }
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
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'periodLabel' => $meses[$month - 1] . ' ' . $year,
            'statsInternacion' => $statsInternacion,
            'ocupacionActual' => round($ocupacionActual),
        ]);
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
                $endDate->modify('+2 months last day of month');
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
        
        // Consulta directa SQL para obtener el total de camas disponibles
        try {
            $conn = $em->getConnection();
            $sql = "SELECT SUM(camas_disponibles) as total FROM habitacion";
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery();
            $totalCamas = (int)$result->fetchOne() ?: 0;
            
            // Si el resultado es NULL o 0, establecemos un valor predeterminado
            if (!$totalCamas) {
                $totalCamas = 52; // Valor predeterminado basado en la consulta previa
            }
        } catch (\Exception $e) {
            // Si hay un error, asignamos un valor predeterminado
            $totalCamas = 52; // Valor predeterminado
        }
        
        // Consultar el historial de habitaciones para el período usando SQL directo
        $historico = [];
        try {
            $conn = $em->getConnection();
            $sql = "SELECT DATE(fecha) as fecha, COUNT(id) as ocupadas 
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
        $esPeriodoFuturo = $startDate > $hoy;
        
        // Variable para almacenar el último valor válido conocido
        $ultimoValorValido = $ocupacionActualCalculada;
        
        // Verificar si tenemos datos históricos para este período
        $hayDatosHistoricos = !empty($historico);
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $ocupadas = 0;
            
            // Solo las fechas posteriores a la actual se consideran futuro
            $esFuturo = $currentDate > $hoy;
            
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
            // Si es una fecha futura (posterior a hoy), mostrar 0
            else if ($esFuturo) {
                $ocupadas = 0;
            }
            // Para fechas pasadas sin dato específico pero con histórico general
            else if ($currentDate < $hoy && $hayDatosHistoricos) {
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