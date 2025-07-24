<?php

namespace App\Service;

use App\Entity\Cliente;
use App\Entity\HistoriaPaciente;
use App\Entity\Habitacion;
use App\Entity\ObraSocial;
use App\Repository\HabitacionRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\BookingRepository;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Servicio para centralizar la gestión de estados de pacientes
 */
class PatientStateService
{
    // Constantes para los estados de pacientes
    public const ESTADO_INTERNADO = 'internado';    // modalidad = 2
    public const ESTADO_AMBULATORIO = 'ambulatorio'; // modalidad = 1
    public const ESTADO_DERIVADO = 'derivado';      // derivado = true
    public const ESTADO_PERMISO = 'permiso';        // de_permiso = true
    public const ESTADO_INACTIVO = 'inactivo';      // tiene fecha_egreso

    private $entityManager;
    private $habitacionRepository;
    private $historiaPacienteRepository;
    private $bookingRepository;
    private $clienteRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        HabitacionRepository $habitacionRepository,
        HistoriaPacienteRepository $historiaPacienteRepository,
        BookingRepository $bookingRepository,
        ClienteRepository $clienteRepository
    ) {
        $this->entityManager = $entityManager;
        $this->habitacionRepository = $habitacionRepository;
        $this->historiaPacienteRepository = $historiaPacienteRepository;
        $this->bookingRepository = $bookingRepository;
        $this->clienteRepository = $clienteRepository;
    }

    /**
     * Obtiene el estado actual del paciente
     * 
     * @param Cliente $cliente El paciente
     * @return string El estado actual (una de las constantes ESTADO_*)
     */
    public function getEstadoActual(Cliente $cliente): string
    {
        if ($cliente->getDerivado()) {
            return self::ESTADO_DERIVADO;
        }
        
        if ($cliente->getDePermiso()) {
            return self::ESTADO_PERMISO;
        }
        
        if ($cliente->getFEgreso() !== null) {
            return self::ESTADO_INACTIVO;
        }
        
        if ($cliente->getAmbulatorio() || $cliente->getModalidad() == 1) {
            return self::ESTADO_AMBULATORIO;
        }
        
        return self::ESTADO_INTERNADO;
    }

    /**
     * Cambia el estado de un paciente a "Internado"
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros adicionales (habitacion, cama, etc)
     * @return Cliente El paciente actualizado
     */
    public function cambiarAInternado(Cliente $cliente, UserInterface $user, array $parametros = []): Cliente
    {
        // 1. Preparar los parámetros necesarios
        $habitacionId = $parametros['habitacion'] ?? null;
        $camaId = $parametros['cama'] ?? null;
        $habPrivada = $parametros['habPrivada'] ?? 0;
        
        if (!$habitacionId || !$camaId) {
            throw new \InvalidArgumentException("Para internar a un paciente se necesita especificar habitación y cama");
        }
        
        $habitacion = $this->habitacionRepository->find($habitacionId);
        if (!$habitacion) {
            throw new \InvalidArgumentException("Habitación no encontrada");
        }
        
        // 2. Actualizar el estado del paciente
        $cliente->setModalidad(2);
        $cliente->setAmbulatorio(false);
        $cliente->setDerivado(false);
        $cliente->setDePermiso(false);
        $cliente->setHabitacion($habitacionId);
        $cliente->setNCama($camaId);
        $cliente->setHabPrivada($habPrivada);
        $cliente->setDisponibleParaTerapia(true);
        
        // 3. Actualizar la habitación
        $this->actualizarOcupacionHabitacion($habitacion, $camaId, $habPrivada);
        
        // 4. Registrar en el historial
        $parametrosHistorial = [
            'modalidad' => 2,
            'habitacion' => $habitacionId,
            'cama' => $camaId,
            'ambulatorio' => false,
            'dePermiso' => false,
            'derivadoEn' => null,
            'fechaDerivacion' => null,
            'motivoDerivacion' => null,
            'empresaTransporteDerivacion' => null,
            'fechaBajaPorPermiso' => null,
            'fechaAltaPorPermiso' => null,
        ];
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Cambia el estado de un paciente a "Ambulatorio"
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @return Cliente El paciente actualizado
     */
    public function cambiarAAmbulatorio(Cliente $cliente, UserInterface $user): Cliente
    {
        // Si estaba internado, liberar la cama
        if ($this->getEstadoActual($cliente) === self::ESTADO_INTERNADO) {
            $this->liberarCamaCliente($cliente);
        }
        
        // Actualizar el estado del paciente
        $cliente->setAmbulatorio(true);
        $cliente->setModalidad(1);
        $cliente->setFechaAmbulatorio(new \DateTime());
        $cliente->setDerivado(false);
        $cliente->setDePermiso(false);
        
        // Cancelar turnos si es necesario
        $this->cancelarTurnosPaciente($cliente);
        
        // Registrar en el historial
        $parametrosHistorial = [
            'ambulatorio' => true,
            'modalidad' => 1,
            'habitacion' => '',
            'cama' => '',
            'dePermiso' => false,
            'derivadoEn' => null,
        ];
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Cambia el estado de un paciente a "Derivado"
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros de derivación
     * @return Cliente El paciente actualizado
     */
    public function cambiarADerivado(Cliente $cliente, UserInterface $user, array $parametros): Cliente
    {
        $derivadoEn = $parametros['derivadoEn'] ?? '';
        $fechaDerivacion = $parametros['fechaDerivacion'] ?? new \DateTime();
        $motivo = $parametros['motivoDerivacion'] ?? '';
        $empDeTraslado = $parametros['empresaTransporteDerivacion'] ?? '';
        
        // Actualizar el estado del paciente
        $cliente->setDerivado(true);
        $cliente->setDerivadoEn($derivadoEn);
        $cliente->setFechaDerivacion($fechaDerivacion);
        $cliente->setMotivoDerivacion($motivo);
        $cliente->setEmpTrasladoDerivacion($empDeTraslado);
        $cliente->setDisponibleParaTerapia(false);
        
        // Si estaba internado, liberar la cama
        if ($cliente->getHabitacion()) {
            $this->liberarCamaCliente($cliente);
        }
        
        // Cancelar turnos si es necesario
        $this->cancelarTurnosPaciente($cliente);
        
        // Registrar en el historial
        $parametrosHistorial = [
            'derivadoEn' => $derivadoEn,
            'fechaDerivacion' => $fechaDerivacion,
            'motivoDerivacion' => $motivo,
            'empresaTransporteDerivacion' => $empDeTraslado,
            'habitacion' => '',
            'cama' => '',
        ];
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Cambia el estado de un paciente a "De Permiso"
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros del permiso
     * @return Cliente El paciente actualizado
     */
    public function cambiarAPermiso(Cliente $cliente, UserInterface $user, array $parametros): Cliente
    {
        $fechaPermisoDesde = $parametros['fechaBajaPorPermiso'] ?? new \DateTime();
        $fechaPermisoHasta = $parametros['fechaAltaPorPermiso'] ?? new \DateTime();
        
        // Actualizar el estado del paciente
        $cliente->setDePermiso(true);
        $cliente->setFechaBajaPorPermiso($fechaPermisoDesde);
        $cliente->setFechaAltaPorPermiso($fechaPermisoHasta);
        $cliente->setDisponibleParaTerapia(false);
        
        // No liberamos la cama, pero cancelamos los turnos
        $this->cancelarTurnosPaciente($cliente);
        
        // Registrar en el historial
        $parametrosHistorial = [
            'dePermiso' => true,
            'fechaBajaPorPermiso' => $fechaPermisoDesde,
            'fechaAltaPorPermiso' => $fechaPermisoHasta,
        ];
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Cambia el estado de un paciente a "Inactivo" (egreso)
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros del egreso
     * @return Cliente El paciente actualizado
     */
    public function cambiarAInactivo(Cliente $cliente, UserInterface $user, array $parametros = []): Cliente
    {
        $fechaEgreso = $parametros['fEgreso'] ?? new \DateTime();
        $motivoEgreso = $parametros['motivoEgreso'] ?? null;
        
        // Actualizar el estado del paciente
        $cliente->setFEgreso($fechaEgreso);
        if ($motivoEgreso) {
            $cliente->setMotivoEgr($motivoEgreso);
        }
        
        // Si todavía ocupa una cama, liberarla
        if ($cliente->getHabitacion() && $fechaEgreso <= new \DateTime()) {
            $this->liberarCamaCliente($cliente);
        }
        
        // Cancelar turnos desde la fecha de egreso
        if ($fechaEgreso instanceof \DateTime) {
            $fechaString = $fechaEgreso->setTime(00, 00, 00)->format('Y-m-d H:i:s');
            $turnos = $this->bookingRepository->turnosConFiltro('', $cliente, $fechaString);
            
            foreach ($turnos as $turno) {
                $this->entityManager->remove($turno);
            }
        }
        
        // Registrar en el historial
        $parametrosHistorial = [
            'fEgreso' => $fechaEgreso,
        ];
        
        if ($cliente->getHabitacion() === null) {
            $parametrosHistorial['habitacion'] = '';
            $parametrosHistorial['cama'] = '';
        }
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Reingresa un paciente que estaba de permiso
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @return Cliente El paciente actualizado
     */
    public function reingresarDePermiso(Cliente $cliente, UserInterface $user, array $parametros = []): Cliente
    {
        // Verificar que el paciente realmente está de permiso
        if (!$cliente->getDePermiso()) {
            throw new \InvalidArgumentException("El paciente no está de permiso");
        }
        
        // Actualizar el estado del paciente
        $cliente->setDePermiso(false);
        $cliente->setDisponibleParaTerapia(true);
        
        // Parámetros para el historial
        $parametrosHistorial = [
            'dePermiso' => false,
            'fechaBajaPorPermiso' => null,
            'fechaAltaPorPermiso' => $parametros['fechaAltaPorPermiso'] ?? null,
        ];
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Reingresa un paciente que estaba derivado
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros del reingreso
     * @return Cliente El paciente actualizado
     */
    public function reingresarDerivado(Cliente $cliente, UserInterface $user, array $parametros): Cliente
    {
        // Verificar que el paciente realmente está derivado
        if (!$cliente->getDerivado()) {
            throw new \InvalidArgumentException("El paciente no está derivado");
        }
        
        $fechaReingreso = $parametros['fechaReingresoDerivacion'] ?? new \DateTime();
        $motivoReingreso = $parametros['motivoReingresoDerivacion'] ?? '';
        $habitacionId = $parametros['habitacion'] ?? null;
        $camaId = $parametros['cama'] ?? null;
        
        // Determinar si regresa como internado o ambulatorio
        $esInternado = ($habitacionId && $camaId);
        
        // Actualizar el estado del paciente
        $cliente->setDerivado(false);
        $cliente->setFechaReingresoDerivacion($fechaReingreso);
        $cliente->setMotivoReingresoDerivacion($motivoReingreso);
        $cliente->setDisponibleParaTerapia(true);
        
        if ($esInternado) {
            $habitacion = $this->habitacionRepository->find($habitacionId);
            if (!$habitacion) {
                throw new \InvalidArgumentException("Habitación no encontrada");
            }
            
            $habPrivada = $parametros['habPrivada'] ?? 0;
            
            $cliente->setModalidad(2);
            $cliente->setAmbulatorio(false);
            $cliente->setHabitacion($habitacionId);
            $cliente->setNCama($camaId);
            $cliente->setHabPrivada($habPrivada);
            
            // Actualizar la habitación
            $this->actualizarOcupacionHabitacion($habitacion, $camaId, $habPrivada);
        } else {
            $cliente->setModalidad(1);
            $cliente->setAmbulatorio(true);
            $cliente->setFechaAmbulatorio(new \DateTime());
        }
        
        // Registrar en el historial
        $parametrosHistorial = [
            'dePermiso' => false,
            'fechaReingresoDerivacion' => $fechaReingreso,
            'derivadoEn' => null,
            'motivoDerivacion' => null,
            'empresaTransporteDerivacion' => null,
        ];
        
        if ($esInternado) {
            $parametrosHistorial['modalidad'] = 2;
            $parametrosHistorial['habitacion'] = $habitacionId;
            $parametrosHistorial['cama'] = $camaId;
            $parametrosHistorial['ambulatorio'] = false;
        } else {
            $parametrosHistorial['modalidad'] = 1;
            $parametrosHistorial['ambulatorio'] = true;
        }
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Reingresa un paciente que estaba inactivo (con egreso)
     * 
     * @param Cliente $cliente El paciente
     * @param UserInterface $user Usuario que realiza el cambio
     * @param array $parametros Parámetros del reingreso
     * @return Cliente El paciente actualizado
     */
    public function reingresarInactivo(Cliente $cliente, UserInterface $user, array $parametros): Cliente
    {
        // Verificar que el paciente realmente está inactivo
        if ($cliente->getFEgreso() === null) {
            throw new \InvalidArgumentException("El paciente no está inactivo");
        }
        
        $habitacionId = $parametros['habitacion'] ?? null;
        $camaId = $parametros['cama'] ?? null;
        
        // Determinar si regresa como internado o ambulatorio
        $esInternado = ($habitacionId && $camaId);
        
        // Actualizar el estado del paciente
        $cliente->setFEgreso(null);
        $cliente->setDisponibleParaTerapia(true);
        $cliente->setFIngreso(new \DateTime());
        
        if ($esInternado) {
            $habitacion = $this->habitacionRepository->find($habitacionId);
            if (!$habitacion) {
                throw new \InvalidArgumentException("Habitación no encontrada");
            }
            
            $habPrivada = $parametros['habPrivada'] ?? 0;
            
            $cliente->setModalidad(2);
            $cliente->setAmbulatorio(false);
            $cliente->setHabitacion($habitacionId);
            $cliente->setNCama($camaId);
            $cliente->setHabPrivada($habPrivada);
            
            // Actualizar la habitación
            $this->actualizarOcupacionHabitacion($habitacion, $camaId, $habPrivada);
        } else {
            $cliente->setModalidad(1);
            $cliente->setAmbulatorio(true);
            $cliente->setFechaAmbulatorio(new \DateTime());
        }
        
        // Registrar en el historial
        $parametrosHistorial = [
            'fechaIngreso' => new \DateTime(),
            'fEgreso' => 'null',
        ];
        
        if ($esInternado) {
            $parametrosHistorial['modalidad'] = 2;
            $parametrosHistorial['habitacion'] = $habitacionId;
            $parametrosHistorial['cama'] = $camaId;
            $parametrosHistorial['ambulatorio'] = false;
        } else {
            $parametrosHistorial['modalidad'] = 1;
            $parametrosHistorial['ambulatorio'] = true;
        }
        
        $this->registrarCambioEnHistorial($cliente, $parametrosHistorial, $user);
        
        return $cliente;
    }

    /**
     * Actualiza la ocupación de una habitación
     */
    private function actualizarOcupacionHabitacion(Habitacion $habitacion, int $camaId, int $habPrivada): void
    {
        $camasOcupadas = $habitacion->getCamasOcupadas();
        
        if ($habPrivada) {
            // Si es habitación privada, ocupar todas las camas
            for ($i = 1; $i <= $habitacion->getCamasDisponibles(); $i++) {
                $camasOcupadas[$i] = $i;
            }
        } else {
            // Si no, ocupar solo la cama asignada
            $camasOcupadas[$camaId] = $camaId;
        }
        
        $habitacion->setCamasOcupadas($camasOcupadas);
        $this->entityManager->persist($habitacion);
    }

    /**
     * Libera la cama ocupada por un paciente
     */
    private function liberarCamaCliente(Cliente $cliente): void
    {
        if (!$cliente->getHabitacion()) {
            return;
        }
        
        $habitacionActual = $this->habitacionRepository->find($cliente->getHabitacion());
        if (!$habitacionActual) {
            return;
        }
        
        // Verificar si hay otros pacientes en la misma habitación para evitar liberar sus camas
        $otrosPacientes = $this->clienteRepository->findBy([
            'habitacion' => $habitacionActual->getId(),
            'fEgreso' => null
        ]);
        
        // Filtrar el cliente actual de la lista
        $otrosPacientes = array_filter($otrosPacientes, function($p) use ($cliente) {
            return $p->getId() != $cliente->getId();
        });

        // Determinar qué camas deben permanecer ocupadas
        $camasOcupadas = [];
        $camasAsignadas = 0;
        foreach ($otrosPacientes as $paciente) {
            if ($paciente->getNCama() !== null) {
                // Si el paciente tiene número de cama (incluso si es 0), mantenerlo
                $camasOcupadas[$paciente->getNCama()] = $paciente->getNCama();
            } else {
                // Si hay pacientes sin número de cama, asignarles una
                $camasAsignadas++;
                $numeroCama = $camasAsignadas;
                
                // Buscar la primera cama disponible
                while (isset($camasOcupadas[$numeroCama])) {
                    $numeroCama++;
                }
                
                // Asignar la cama al paciente y actualizar el registro
                $paciente->setNCama($numeroCama);
                $camasOcupadas[$numeroCama] = $numeroCama;
                $this->entityManager->persist($paciente);
            }
        }
        
        // Actualizar las camas ocupadas de la habitación
        $habitacionActual->setCamasOcupadas($camasOcupadas);
        
        $cliente->setHabitacion(null);
        $cliente->setNCama(null);
        $cliente->setHabPrivada(0);
        
        $this->entityManager->persist($habitacionActual);
        $this->entityManager->persist($cliente);
        $this->entityManager->flush(); // Asegurar que los cambios se guardan inmediatamente
    }

    /**
     * Cancela los turnos de un paciente
     */
    private function cancelarTurnosPaciente(Cliente $cliente): void
    {
        $turnos = $this->bookingRepository->findBy(['cliente' => $cliente]);
        
        foreach ($turnos as $turno) {
            $this->entityManager->remove($turno);
        }
    }

    /**
     * Registra un cambio de estado en el historial del paciente
     */
    private function registrarCambioEnHistorial(Cliente $cliente, array $parametros, UserInterface $user): void
    {
        $ultimoHistorial = $this->historiaPacienteRepository->findBy(
            ['cliente' => $cliente], 
            ['fecha' => 'desc'], 
            ['limit' => 1]
        );
        
        $historial = new HistoriaPaciente();
        
        // Preservar valores previos si no se especifican
        $modalidad = $parametros['modalidad'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getModalidad() : null);
        $patologia = $parametros['patologia'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getPatologia() : null);
        $patologiaEspecifica = $parametros['patologiaEspecifica'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getPatologiaEspecifica() : null);
        $obraSocial = $parametros['obraSocial'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getObraSocial() : null);
        $nAfiliadoObraSocial = $parametros['nAfiliadoObraSocial'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getNAfiliadoObraSocial() : null);
        $sistemaDeEmergencia = $parametros['sistemaDeEmergencia'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getSistemaDeEmergencia() : null);
        $nAfiliadoSistemaDeEmergencia = $parametros['nAfiliadoSistemaDeEmergencia'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getNAfiliadoSistemaDeEmergencia() : null);
        $habitacion = $parametros['habitacion'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getHabitacion() : null);
        $cama = $parametros['cama'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getCama() : null);
        $fechaIngreso = $parametros['fechaIngreso'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaIngreso() : null);
        $fEgreso = $parametros['fEgreso'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaEngreso() : null);
        $fechaDerivacion = $parametros['fechaDerivacion'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaDerivacion() : null);
        $fechaReingresoDerivacion = $parametros['fechaReingresoDerivacion'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaReingresoDerivacion() : null);
        $motivoDerivacion = $parametros['motivoDerivacion'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getMotivoDerivacion() : null);
        $derivadoEn = $parametros['derivadoEn'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getDerivadoEn() : null);
        $empresaTransporteDerivacion = $parametros['empresaTransporteDerivacion'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getEmpresaTransporteDerivacion() : null);
        $fechaAltaPorPermiso = $parametros['fechaAltaPorPermiso'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaAltaPorPermiso() : null);
        $fechaBajaPorPermiso = $parametros['fechaBajaPorPermiso'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getFechaBajaPorPermiso() : null);
        $dePermiso = $parametros['dePermiso'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getDePermiso() : null);
        $ambulatorio = $parametros['ambulatorio'] ?? (isset($ultimoHistorial[0]) ? $ultimoHistorial[0]->getAmbulatorio() : null);
        $docReferente = null;
        
        if ((isset($parametros['docReferente']))) {
            $docIds = [];
            foreach ($parametros['docReferente'] as $doc) {
                $docIds[] = $doc->getId();
            }
            $docReferente = json_encode($docIds);
        } else if (isset($ultimoHistorial[0])) {
            $docReferente = $ultimoHistorial[0]->getDocReferente();
        }
        
        $historial->setCliente($cliente);
        $historial->setModalidad($modalidad);
        $historial->setPatologia($patologia);
        $historial->setPatologiaEspecifica($patologiaEspecifica);
        
        if ($obraSocial instanceof ObraSocial) {
            $historial->setObraSocial($obraSocial);
        } else if ($obraSocial) {
            $obraSocialRepo = $this->entityManager->getRepository(ObraSocial::class);
            $obraSocialObj = $obraSocialRepo->find($obraSocial);
            $historial->setObraSocial($obraSocialObj);
        }
        
        $fecha = new \DateTime();
        
        $historial->setNAfiliadoObraSocial($nAfiliadoObraSocial);
        $historial->setSistemaDeEmergencia($sistemaDeEmergencia);
        $historial->setNAfiliadoSistemaDeEmergencia($nAfiliadoSistemaDeEmergencia);
        $historial->setHabitacion($habitacion);
        $historial->setCama($cama);
        $historial->setIdPaciente($cliente->getId());
        $historial->setFecha($fecha);
        $historial->setFechaIngreso($fechaIngreso);
        
        // Manejar el caso especial donde fEgreso es la cadena 'null'
        if ($fEgreso === 'null') {
            $historial->setFechaEngreso(null);
        } else {
            $historial->setFechaEngreso($fEgreso);
        }
        
        $historial->setUsuario($user->getEmail());
        $historial->setFechaDerivacion($fechaDerivacion);
        $historial->setFechaReingresoDerivacion($fechaReingresoDerivacion);
        $historial->setMotivoDerivacion($motivoDerivacion);
        $historial->setDerivadoEn($derivadoEn);
        $historial->setEmpresaTransporteDerivacion($empresaTransporteDerivacion);
        $historial->setFechaAltaPorPermiso($fechaAltaPorPermiso);
        $historial->setFechaBajaPorPermiso($fechaBajaPorPermiso);
        $historial->setDePermiso($dePermiso);
        $historial->setAmbulatorio($ambulatorio);
        $historial->setDocReferente($docReferente);
        
        // Si hay un historial previo, cerrar ese registro
        if (isset($ultimoHistorial[0])) {
            $fechaFinPrev = $fEgreso instanceof \DateTime ? $fEgreso : $fecha;
            $ultimoHistorial[0]->setFechaFin($fechaFinPrev);
            $this->entityManager->persist($ultimoHistorial[0]);
        } else {
            // Si es el primer registro, usar fecha de ingreso como inicio
            $fechaIngreso = $fechaIngreso ?: $fecha;
            $historial->setFecha($fechaIngreso);
        }
        
        $this->entityManager->persist($historial);
        $this->entityManager->flush();
    }
}