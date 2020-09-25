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
     * @ORM\Column(type="string", length=255)
     */
    private $usuario;

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
}
