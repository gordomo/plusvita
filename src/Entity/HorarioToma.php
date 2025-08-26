<?php

namespace App\Entity;

use App\Repository\HorarioTomaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HorarioTomaRepository::class)
 */
class HorarioToma
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ConsumiblesClientes::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $indicacion;

    /**
     * @ORM\Column(type="date", options={"comment"="Fecha para la que se programa esta toma"})
     */
    private $fecha;

    /**
     * @ORM\Column(type="time", options={"comment"="Horario programado para la toma"})
     */
    private $horario;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false, "comment"="Indica si la toma fue administrada"})
     */
    private $administrado = false;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"comment"="Fecha y hora cuando se marcó como administrado"})
     */
    private $fechaAdministracion;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"comment"="ID del usuario que administró la medicación"})
     */
    private $administradoPorUserId;

    /**
     * @ORM\Column(type="text", nullable=true, options={"comment"="Observaciones sobre la administración"})
     */
    private $observaciones;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true, "comment"="Indica si el horario está habilitado para administrar"})
     */
    private $habilitado = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndicacion(): ?ConsumiblesClientes
    {
        return $this->indicacion;
    }

    public function setIndicacion(?ConsumiblesClientes $indicacion): self
    {
        $this->indicacion = $indicacion;

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

    public function getHorario(): ?\DateTimeInterface
    {
        return $this->horario;
    }

    public function setHorario(\DateTimeInterface $horario): self
    {
        $this->horario = $horario;

        return $this;
    }

    public function isAdministrado(): bool
    {
        return $this->administrado;
    }

    public function setAdministrado(bool $administrado): self
    {
        $this->administrado = $administrado;

        return $this;
    }

    public function getFechaAdministracion(): ?\DateTimeInterface
    {
        return $this->fechaAdministracion;
    }

    public function setFechaAdministracion(?\DateTimeInterface $fechaAdministracion): self
    {
        $this->fechaAdministracion = $fechaAdministracion;

        return $this;
    }

    public function getAdministradoPorUserId(): ?int
    {
        return $this->administradoPorUserId;
    }

    public function setAdministradoPorUserId(?int $administradoPorUserId): self
    {
        $this->administradoPorUserId = $administradoPorUserId;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): self
    {
        $this->observaciones = $observaciones;

        return $this;
    }

    public function isHabilitado(): bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): self
    {
        $this->habilitado = $habilitado;

        return $this;
    }

    /**
     * Verifica si el horario está en la ventana de administración (2 horas antes y después)
     */
    public function estaEnVentanaAdministracion(\DateTime $ahora = null): bool
    {
        if (!$ahora) {
            $ahora = new \DateTime();
        }

        $fechaHoraProgramada = clone $this->fecha;
        $horario = $this->horario->format('H:i:s');
        $fechaHoraProgramada->setTime(...explode(':', $horario));

        // Ventana de 2 horas antes y 2 horas después
        $ventanaInicio = clone $fechaHoraProgramada;
        $ventanaInicio->modify('-2 hours');
        
        $ventanaFin = clone $fechaHoraProgramada;
        $ventanaFin->modify('+2 hours');

        return $ahora >= $ventanaInicio && $ahora <= $ventanaFin;
    }
}
