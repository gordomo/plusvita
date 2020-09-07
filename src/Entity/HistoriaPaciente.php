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
    private $habitacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cama;

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

    public function setPatologiaEspecifica(?string $patologia_especifica): self
    {
        $this->patologia_especifica = $patologia_especifica;

        return $this;
    }

    public function getObraSocial(): ?string
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
}
