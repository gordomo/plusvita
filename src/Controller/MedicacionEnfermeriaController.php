<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\HorarioToma;
use App\Entity\SignosVitales;
use App\Entity\ConsumiblesClientes;
use App\Entity\Evolucion;
use App\Repository\HorarioTomaRepository;
use App\Repository\ConsumiblesClientesRepository;
use App\Repository\ConsumibleRepository;
use App\Repository\SignosVitalesRepository;
use App\Repository\UserRepository;
use App\Service\HorarioTomaCalculatorService;
use App\Service\TurnoService;
use App\Service\PatientStateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/medicacion-enfermeria")
 */
class MedicacionEnfermeriaController extends AbstractController
{
    /**
     * @Route("/", name="medicacion_enfermeria_index", methods={"GET"})
     */
    public function indexGeneral(HorarioTomaRepository $horarioTomaRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('patient.kardex') && !$this->isGranted('ROLE_NURSE')) {
            throw $this->createAccessDeniedException('No tienes permisos para acceder al Kardex');
        }
        
        $fechaHoy = new \DateTime();
        $fechaHoy->setTime(0, 0, 0);
        
        // Obtener los IDs de clientes que tienen medicación programada para hoy
        $clienteIds = $horarioTomaRepository->findClienteIdsConMedicacionHoy($fechaHoy);
        
        // Obtener los datos completos de los clientes
        $pacientes = [];
        if (!empty($clienteIds)) {
            $pacientes = $entityManager->getRepository(Cliente::class)->findBy(['id' => $clienteIds]);
        }
        
        return $this->render('medicacion_enfermeria/lista_pacientes.html.twig', [
            'pacientes' => $pacientes,
            'fecha' => $fechaHoy,
        ]);
    }

    /**
     * @Route("/{id}", name="medicacion_enfermeria", methods={"GET"})
     */
    public function index(
        Cliente $cliente, 
        HorarioTomaRepository $horarioTomaRepository, 
        ConsumiblesClientesRepository $consumiblesRepository, 
        ConsumibleRepository $consumibleRepository,
        SignosVitalesRepository $signosVitalesRepository,
        TurnoService $turnoService,
        PatientStateService $patientStateService,
        UserRepository $userRepository
    ): Response
    {
        $fechaHoy = new \DateTime();
        $fechaHoy->setTime(0, 0, 0); // Resetear a medianoche para comparaciones de fecha
        
        // Verificar si el paciente está internado
        $estaInternado = $patientStateService->getEstadoActual($cliente) === PatientStateService::ESTADO_INTERNADO;
        
        // Obtener turno actual y datos de signos vitales
        $turnoActual = null;
        $signosVitalesCompletados = false;
        $signosVitalesBloqueados = false;
        $indicacionesPendientesEnTurno = [];
        $fechaBaseTurno = null;
        $signosVitalesDelTurno = [];
        
        if ($estaInternado) {
            $ahora = new \DateTime();
            $turnoActual = $turnoService->obtenerTurnoActual($ahora);
            $fechaBaseTurno = $turnoService->obtenerFechaBaseTurno($ahora, $turnoActual);
            
            // Obtener todas las tomas de signos vitales del turno actual
            $signosVitalesDelTurno = $signosVitalesRepository->findTodasPorPacienteFechaTurno($cliente, $fechaBaseTurno, $turnoActual);
            
            // Obtener información de los usuarios que registraron los signos vitales
            $usuariosSignosVitales = [];
            foreach ($signosVitalesDelTurno as $sv) {
                if ($sv->getRegistradoPorUserId()) {
                    $usuario = $userRepository->find($sv->getRegistradoPorUserId());
                    if ($usuario) {
                        $usuariosSignosVitales[$sv->getId()] = $usuario;
                    }
                }
            }
            
            // Verificar si hay al menos una toma completada para este turno
            $signosVitalesCompletados = !empty($signosVitalesDelTurno);
            
            // Verificar si hay indicaciones pendientes en el turno actual
            $indicacionesPendientesEnTurno = $horarioTomaRepository->findIndicacionesPendientesEnTurno(
                $cliente->getId(), 
                $turnoActual, 
                $fechaBaseTurno
            );
            
            // Los signos vitales están bloqueados si hay indicaciones pendientes en el turno Y no hay ninguna toma registrada
            $signosVitalesBloqueados = !empty($indicacionesPendientesEnTurno) && empty($signosVitalesDelTurno);
        }
        
        // Obtener horarios programados para hoy
        $horariosHoy = $horarioTomaRepository->findHorariosPorClienteYFecha($cliente->getId(), $fechaHoy);
        
        // Obtener indicaciones activas para mostrar información adicional
        $indicacionesActivas = $consumiblesRepository->findBy([
            'clienteId' => $cliente->getId(),
            'activo' => true,
            'estadoSuspendido' => false
        ]);
        
        // Crear array de consumibles para mostrar nombres
        $consumiblesArray = [];
        foreach ($indicacionesActivas as $indicacion) {
            if ($indicacion->getConsumibleId()) {
                $consumible = $consumibleRepository->find($indicacion->getConsumibleId());
                if ($consumible) {
                    $consumiblesArray[$indicacion->getConsumibleId()] = $consumible->getNombre();
                }
            }
        }
        
        // Organizar horarios por indicación
        $horariosPorIndicacion = [];
        foreach ($horariosHoy as $horario) {
            $indicacionId = $horario->getIndicacion()->getId();
            if (!isset($horariosPorIndicacion[$indicacionId])) {
                $horariosPorIndicacion[$indicacionId] = [
                    'indicacion' => $horario->getIndicacion(),
                    'horarios' => []
                ];
            }
            $horariosPorIndicacion[$indicacionId]['horarios'][] = $horario;
        }
        
        // Agregar indicaciones activas que NO tienen horarios generados (para diagnóstico)
        foreach ($indicacionesActivas as $indicacion) {
            if (!isset($horariosPorIndicacion[$indicacion->getId()])) {
                $horariosPorIndicacion[$indicacion->getId()] = [
                    'indicacion' => $indicacion,
                    'horarios' => [],
                    'sin_horarios' => true  // Flag para identificar indicaciones sin horarios
                ];
            }
        }
        
        // Obtener horarios en ventana de administración y calcular límites de ventana
        $horariosEnVentana = $horarioTomaRepository->findHorariosEnVentana($cliente->getId());
        $horariosEnVentanaIds = array_map(function($h) { return $h->getId(); }, $horariosEnVentana);
        
        // Calcular ventanas horarias para cada horario
        $ventanasHorarias = [];
        foreach ($horariosHoy as $horario) {
            $horaBase = clone $horario->getHorario();
            $ventanaInicio = clone $horaBase;
            $ventanaInicio->modify('-2 hours');
            $ventanaFin = clone $horaBase;
            $ventanaFin->modify('+2 hours');
            
            $ventanasHorarias[$horario->getId()] = [
                'inicio' => $ventanaInicio,
                'fin' => $ventanaFin
            ];
        }
        
        return $this->render('medicacion_enfermeria/index.html.twig', [
            'cliente' => $cliente,
            'horariosPorIndicacion' => $horariosPorIndicacion,
            'consumiblesArray' => $consumiblesArray,
            'fechaHoy' => $fechaHoy,
            'horariosEnVentanaIds' => $horariosEnVentanaIds,
            'ventanasHorarias' => $ventanasHorarias,
            'estaInternado' => $estaInternado,
            'turnoActual' => $turnoActual,
            'signosVitalesCompletados' => $signosVitalesCompletados,
            'signosVitalesBloqueados' => $signosVitalesBloqueados,
            'indicacionesPendientesEnTurno' => $indicacionesPendientesEnTurno,
            'turnoService' => $turnoService,
            'fechaBaseTurno' => $fechaBaseTurno,
            'signosVitalesDelTurno' => $signosVitalesDelTurno ?? [],
            'usuariosSignosVitales' => $usuariosSignosVitales ?? []
        ]);
    }
    
    /**
     * @Route("/administrar/{id}", name="medicacion_administrar", methods={"POST"})
     */
    public function administrarMedicacion(HorarioToma $horario, Request $request, HorarioTomaCalculatorService $horarioCalculator): JsonResponse
    {
        $observaciones = $request->request->get('observaciones', '');
        $userId = $this->getUser() ? $this->getUser()->getId() : null;
        
        try {
            $resultado = $horarioCalculator->marcarComoAdministrado($horario, $userId, $observaciones);
            
            if ($resultado) {
                return $this->json([
                    'success' => true,
                    'message' => 'Medicación marcada como administrada correctamente',
                    'hora_administracion' => $horario->getFechaAdministracion()->format('H:i')
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'No se puede administrar fuera de la ventana horaria permitida (±2 horas del horario programado)'
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al procesar la administración: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * @Route("/revertir/{id}", name="medicacion_revertir", methods={"POST"})
     */
    public function revertirMedicacion(HorarioToma $horario, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Solo permitir revertir si fue administrado hoy
            if ($horario->getFechaAdministracion() && 
                $horario->getFechaAdministracion()->format('Y-m-d') === date('Y-m-d')) {
                
                $horario->setAdministrado(false);
                $horario->setFechaAdministracion(null);
                $horario->setAdministradoPorUserId(null);
                $horario->setObservaciones(null);
                
                $entityManager->persist($horario);
                $entityManager->flush();
                
                return $this->json([
                    'success' => true,
                    'message' => 'Administración revertida correctamente'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Solo se pueden revertir administraciones del día actual'
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al revertir: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * @Route("/resumen/{id}", name="medicacion_resumen_dia", methods={"GET"})
     */
    public function resumenDia(Cliente $cliente, HorarioTomaRepository $horarioTomaRepository): JsonResponse
    {
        $fechaHoy = new \DateTime();
        
        $horarios = $horarioTomaRepository->findHorariosPorClienteYFecha($cliente->getId(), $fechaHoy);
        
        $total = count($horarios);
        $administrados = array_filter($horarios, function($h) { return $h->isAdministrado(); });
        $pendientes = $total - count($administrados);
        
        return $this->json([
            'total' => $total,
            'administrados' => count($administrados),
            'pendientes' => $pendientes,
            'porcentaje' => $total > 0 ? round((count($administrados) / $total) * 100, 1) : 0
        ]);
    }
    
    /**
     * @Route("/registrar-primera-toma/{id}", name="medicacion_registrar_primera_toma", methods={"POST"})
     */
    public function registrarPrimeraToma(ConsumiblesClientes $indicacion, Request $request, HorarioTomaCalculatorService $horarioCalculator): JsonResponse
    {
        try {
            $horaPrimeraToma = $request->request->get('hora');
            if (!$horaPrimeraToma) {
                throw new \Exception('Debe especificar la hora de la primera toma');
            }

            // Marcar como administrada la primera toma y generar horarios subsiguientes
            $fechaHoy = new \DateTime();
            $horaPrimeraToma = \DateTime::createFromFormat('H:i', $horaPrimeraToma);
            
            if (!$horaPrimeraToma) {
                throw new \Exception('Formato de hora inválido');
            }
            
            // Establecer la hora de primera toma en la indicación
            $indicacion->setHorarioPrimeraToma($horaPrimeraToma);
            
            // Generar horarios a partir de la primera toma
            $horarios = $horarioCalculator->generarHorariosToma($indicacion);
            
            // Si se generaron horarios exitosamente, marcar el primero como administrado
            if (!empty($horarios)) {
                $primerHorario = $horarios[0];
                $userId = $this->getUser() ? $this->getUser()->getId() : null;
                $horarioCalculator->marcarComoAdministrado($primerHorario, $userId);
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Primera toma registrada y horarios generados correctamente'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al registrar primera toma: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * @Route("/registrar-signos-vitales/{id}", name="medicacion_registrar_signos_vitales", methods={"POST"})
     */
    public function registrarSignosVitales(
        Cliente $cliente, 
        Request $request, 
        EntityManagerInterface $entityManager,
        SignosVitalesRepository $signosVitalesRepository,
        TurnoService $turnoService,
        HorarioTomaRepository $horarioTomaRepository,
        PatientStateService $patientStateService
    ): JsonResponse
    {
        try {
            // Verificar que el paciente esté internado
            $estaInternado = $patientStateService->getEstadoActual($cliente) === PatientStateService::ESTADO_INTERNADO;
            if (!$estaInternado) {
                return $this->json([
                    'success' => false,
                    'message' => 'Los signos vitales solo se registran para pacientes internados'
                ], 400);
            }

            $notas = $request->request->get('notas', '');
            if (empty($notas)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Debe ingresar las notas de los signos vitales'
                ], 400);
            }

            $ahora = new \DateTime();
            $turnoActual = $turnoService->obtenerTurnoActual($ahora);
            $fechaBaseTurno = $turnoService->obtenerFechaBaseTurno($ahora, $turnoActual);

            // Ya no bloqueamos si hay una toma previa, permitimos múltiples tomas

            // Verificar si hay indicaciones pendientes en el turno
            $indicacionesPendientes = $horarioTomaRepository->findIndicacionesPendientesEnTurno(
                $cliente->getId(), 
                $turnoActual, 
                $fechaBaseTurno
            );

            if (!empty($indicacionesPendientes)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Debe completar primero todas las indicaciones pendientes del turno antes de registrar los signos vitales',
                    'indicaciones_pendientes' => count($indicacionesPendientes)
                ], 400);
            }

            // Verificar si es la primera toma del turno
            $tomasExistentes = $signosVitalesRepository->findTodasPorPacienteFechaTurno($cliente, $fechaBaseTurno, $turnoActual);
            $esPrimeraToma = empty($tomasExistentes);
            
            // Crear nuevo registro (siempre se crea uno nuevo, no se modifica el existente)
            $signosVitales = new SignosVitales();
            $signosVitales->setPaciente($cliente);
            $signosVitales->setFecha($fechaBaseTurno);
            $signosVitales->setTurno($turnoActual);
            $signosVitales->setNotas($notas);
            $signosVitales->setFechaHoraRegistro($ahora);
            $signosVitales->setRegistradoPorUserId($this->getUser() ? $this->getUser()->getId() : null);
            $signosVitales->setCompletado(true);

            $entityManager->persist($signosVitales);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Signos vitales registrados correctamente. Debe ingresar una evolución de enfermería.',
                'requiere_evolucion' => true // Siempre requiere evolución después de registrar signos vitales
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al registrar signos vitales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/evolucion-enfermeria/{id}", name="medicacion_evolucion_enfermeria", methods={"GET"})
     */
    public function evolucionEnfermeria(Cliente $cliente): Response
    {
        if (!$this->isGranted('patient.evolve.enfermeria')) {
            throw $this->createAccessDeniedException('No tienes permisos para crear evoluciones de enfermería');
        }

        return $this->render('medicacion_enfermeria/evolucion_enfermeria.html.twig', [
            'cliente' => $cliente,
        ]);
    }

    /**
     * @Route("/guardar-evolucion-enfermeria/{id}", name="medicacion_guardar_evolucion_enfermeria", methods={"POST"})
     */
    public function guardarEvolucionEnfermeria(
        Cliente $cliente,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $descripcion = $request->request->get('descripcion', '');
            if (empty(trim($descripcion))) {
                return $this->json([
                    'success' => false,
                    'message' => 'Debe ingresar la evolución de enfermería'
                ], 400);
            }

            // Agregar prefijo para identificar que es una evolución de enfermería
            $descripcionConPrefijo = '[Evolución de Enfermería] ' . trim($descripcion);

            // Crear nueva evolución
            $evolucion = new Evolucion();
            $evolucion->setPaciente($cliente);
            $evolucion->setUser($user->getEmail());
            $evolucion->setFecha(new \DateTime());
            $evolucion->setDescription($descripcionConPrefijo);
            
            // Determinar tipo: usar el tipo de contrato del usuario si existe, sino usar tipo de profesional
            $tipoEvolucion = null;
            $contratoActivo = $user->getActiveContract();
            if ($contratoActivo) {
                $tipoEvolucion = $contratoActivo->getTipoLabel();
            } else {
                // Fallback al tipo de profesional si no hay contrato activo
                $tipoEvolucion = $user->getTipoProfesional();
            }
            $evolucion->setTipo($tipoEvolucion);

            // Guardar datos del usuario que firma
            $evolucion->setFirmaDoctorNombre($user->getNombre());
            $evolucion->setFirmaDoctorApellido($user->getApellido());
            $evolucion->setFirmaDoctorMatricula($user->getLegajo());

            // Obtener la firma activa si existe
            $firmaActiva = $user->getActiveFirma();
            if ($firmaActiva) {
                $evolucion->setFirmaDoctorPath($firmaActiva->getFilePath());
            }

            $entityManager->persist($evolucion);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Evolución de enfermería guardada correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al guardar la evolución: ' . $e->getMessage()
            ], 500);
        }
    }
}
