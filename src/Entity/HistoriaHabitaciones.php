<?php

namespace App\Entity;

use App\Repository\HistoriaHabitacionesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoriaHabitacionesRepository::class)
 */
class HistoriaHabitaciones
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class, inversedBy="historiaHabitaciones")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cliente;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nCama;

    /**
     * @ORM\Column(type="date")
     */
    private $fecha;

    /**
     * @ORM\ManyToOne(targetEntity=Habitacion::class, inversedBy="historiaHabitaciones", cascade={"remove"})
     * @ORM\JoinColumn(nullable=false,  onDelete="CASCADE")
     */
    private $habitacion;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): self
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getNCama(): ?string
    {
        return $this->nCama;
    }

    public function setNCama(?string $nCama): self
    {
        $this->nCama = $nCama;

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

    public function getHabitacion(): ?Habitacion
    {
        return $this->habitacion;
    }

    public function setHabitacion(?Habitacion $habitacion): self
    {
        $this->habitacion = $habitacion;

        return $this;
    }
}
