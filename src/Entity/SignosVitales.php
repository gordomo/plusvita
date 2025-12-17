<?php

namespace App\Entity;

use App\Repository\SignosVitalesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SignosVitalesRepository::class)
 * @ORM\Table(name="signos_vitales")
 */
class SignosVitales
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $paciente;

    /**
     * @ORM\Column(type="date")
     */
    private $fecha;

    /**
     * @ORM\Column(type="string", length=20)
     * Turno: "mañana" (6-14), "tarde" (14-22), "noche" (22-06)
     */
    private $turno;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaHoraRegistro;

    /**
     * @ORM\Column(type="text", nullable=true)
     * Notas sobre los signos vitales (temperatura, presión, frecuencia cardíaca, etc.)
     */
    private $notas;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * ID del usuario enfermero que registró los signos vitales
     */
    private $registradoPorUserId;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private $completado = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaciente(): ?Cliente
    {
        return $this->paciente;
    }

    public function setPaciente(?Cliente $paciente): self
    {
        $this->paciente = $paciente;
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

    public function getTurno(): ?string
    {
        return $this->turno;
    }

    public function setTurno(string $turno): self
    {
        $this->turno = $turno;
        return $this;
    }

    public function getFechaHoraRegistro(): ?\DateTimeInterface
    {
        return $this->fechaHoraRegistro;
    }

    public function setFechaHoraRegistro(\DateTimeInterface $fechaHoraRegistro): self
    {
        $this->fechaHoraRegistro = $fechaHoraRegistro;
        return $this;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;
        return $this;
    }

    public function getRegistradoPorUserId(): ?int
    {
        return $this->registradoPorUserId;
    }

    public function setRegistradoPorUserId(?int $registradoPorUserId): self
    {
        $this->registradoPorUserId = $registradoPorUserId;
        return $this;
    }

    public function isCompletado(): bool
    {
        return $this->completado;
    }

    public function setCompletado(bool $completado): self
    {
        $this->completado = $completado;
        return $this;
    }
}

