<?php

namespace App\Entity;

use App\Repository\HistoriaPacienteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoriaPacienteRepository::class)
 */
class HistoriaPaciente
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $modalidad;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $patologia;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $patologia_especifica;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $obra_social;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nAfiliadoObraSocial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sistemaDeEmergencia;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nAfiliadoSistemaDeEmergencia;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $habitacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cama;

    /**
     * @ORM\Column(type="integer")
     */
    private $id_paciente;

    /**
     * @ORM\Column(type="date")
     */
    private $fecha;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fechaIngreso;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fechaEngreso;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $usuario;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Cliente", inversedBy="historia")
     */
    private $cliente;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaDerivacion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaReingresoDerivacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivoDerivacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $derivadoEn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $empresaTransporteDerivacion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaAltaPorPermiso;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaBajaPorPermiso;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $dePermiso;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ambulatorio;

    /**
     * @return mixed
     */
    public function getFechaEngreso()
    {
        return $this->fechaEngreso;
    }

    /**
     * @param mixed $fechaEngreso
     */
    public function setFechaEngreso($fechaEngreso): void
    {
        $this->fechaEngreso = $fechaEngreso;
    }

    /**
     * @return mixed
     */
    public function getFechaIngreso()
    {
        return $this->fechaIngreso;
    }

    /**
     * @param mixed $fechaIngreso
     */
    public function setFechaIngreso($fechaIngreso): void
    {
        $this->fechaIngreso = $fechaIngreso;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModalidad(): ?string
    {
        return $this->modalidad;
    }

    public function setModalidad(?string $modalidad): self
    {
        $this->modalidad = $modalidad;

        return $this;
    }

    public function getPatologia(): ?string
    {
        return $this->patologia;
    }

    public function setPatologia(?string $patologia): self
    {
        $this->patologia = $patologia;

        return $this;
    }

    public function getPatologiaEspecifica(): ?string
    {
        return $this->patologia_especifica;
    }

    public function getpatologia_especifica(): ?string
    {
        return $this->patologia_especifica;
    }

    public function setPatologiaEspecifica(?string $patologia_especifica): self
    {
        $this->patologia_especifica = $patologia_especifica;

        return $this;
    }

    public function getObraSocial(): ?string
    {
        return $this->obra_social;
    }
    public function getobra_social(): ?string
    {
        return $this->obra_social;
    }

    public function setObraSocial(?string $obra_social): self
    {
        $this->obra_social = $obra_social;

        return $this;
    }

    public function getNAfiliadoObraSocial(): ?string
    {
        return $this->nAfiliadoObraSocial;
    }

    public function setNAfiliadoObraSocial(?string $nAfiliadoObraSocial): self
    {
        $this->nAfiliadoObraSocial = $nAfiliadoObraSocial;

        return $this;
    }

    public function getSistemaDeEmergencia(): ?string
    {
        return $this->sistemaDeEmergencia;
    }

    public function setSistemaDeEmergencia(?string $sistemaDeEmergencia): self
    {
        $this->sistemaDeEmergencia = $sistemaDeEmergencia;

        return $this;
    }

    public function getNAfiliadoSistemaDeEmergencia(): ?string
    {
        return $this->nAfiliadoSistemaDeEmergencia;
    }

    public function setNAfiliadoSistemaDeEmergencia(?string $nAfiliadoSistemaDeEmergencia): self
    {
        $this->nAfiliadoSistemaDeEmergencia = $nAfiliadoSistemaDeEmergencia;

        return $this;
    }

    public function getHabitacion(): ?string
    {
        return $this->habitacion;
    }

    public function setHabitacion(?string $habitacion): self
    {
        $this->habitacion = $habitacion;

        return $this;
    }

    public function getCama(): ?string
    {
        return $this->cama;
    }

    public function setCama(?string $cama): self
    {
        $this->cama = $cama;

        return $this;
    }

    public function getIdPaciente(): ?int
    {
        return $this->id_paciente;
    }

    public function setIdPaciente(int $id_paciente): self
    {
        $this->id_paciente = $id_paciente;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getUsuario(): ?string
    {
        return $this->usuario;
    }

    public function setUsuario(string $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * @return Cliente
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param Cliente $cliente
     */
    public function setCliente(Cliente $cliente): void
    {
        $this->cliente = $cliente;
    }

    public function getFechaDerivacion(): ?\DateTimeInterface
    {
        return $this->fechaDerivacion;
    }

    public function setFechaDerivacion(?\DateTimeInterface $fechaDerivacion): self
    {
        $this->fechaDerivacion = $fechaDerivacion;

        return $this;
    }

    public function getFechaReingresoDerivacion(): ?\DateTimeInterface
    {
        return $this->fechaReingresoDerivacion;
    }

    public function setFechaReingresoDerivacion(?\DateTimeInterface $fechaReingresoDerivacion): self
    {
        $this->fechaReingresoDerivacion = $fechaReingresoDerivacion;

        return $this;
    }

    public function getMotivoDerivacion(): ?string
    {
        return $this->motivoDerivacion;
    }

    public function setMotivoDerivacion(?string $motivoDerivacion): self
    {
        $this->motivoDerivacion = $motivoDerivacion;

        return $this;
    }

    public function getDerivadoEn(): ?string
    {
        return $this->derivadoEn;
    }

    public function setDerivadoEn(?string $derivadoEn): self
    {
        $this->derivadoEn = $derivadoEn;

        return $this;
    }

    public function getEmpresaTransporteDerivacion(): ?string
    {
        return $this->empresaTransporteDerivacion;
    }

    public function setEmpresaTransporteDerivacion(?string $empresaTransporteDerivacion): self
    {
        $this->empresaTransporteDerivacion = $empresaTransporteDerivacion;

        return $this;
    }

    public function getFechaAltaPorPermiso(): ?\DateTimeInterface
    {
        return $this->fechaAltaPorPermiso;
    }

    public function setFechaAltaPorPermiso(?\DateTimeInterface $fechaAltaPorPermiso): self
    {
        $this->fechaAltaPorPermiso = $fechaAltaPorPermiso;

        return $this;
    }

    public function getFechaBajaPorPermiso(): ?\DateTimeInterface
    {
        return $this->fechaBajaPorPermiso;
    }

    public function setFechaBajaPorPermiso(?\DateTimeInterface $fechaBajaPorPermiso): self
    {
        $this->fechaBajaPorPermiso = $fechaBajaPorPermiso;

        return $this;
    }

    public function getDePermiso(): ?bool
    {
        return $this->dePermiso;
    }

    public function setDePermiso(?bool $dePermiso): self
    {
        $this->dePermiso = $dePermiso;

        return $this;
    }

    public function getAmbulatorio(): ?bool
    {
        return $this->ambulatorio;
    }

    public function setAmbulatorio(?bool $ambulatorio): self
    {
        $this->ambulatorio = $ambulatorio;

        return $this;
    }
}
