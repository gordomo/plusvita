<?php

namespace App\Entity;

use App\Repository\HabitacionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HabitacionRepository::class)
 */
class Habitacion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="integer")
     */
    private $camasDisponibles;

    /**
     * @ORM\Column(type="json")
     */
    private $camasOcupadas = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getCamasDisponibles(): ?int
    {
        return $this->camasDisponibles;
    }

    public function setCamasDisponibles(int $camasDisponibles): self
    {
        $this->camasDisponibles = $camasDisponibles;

        return $this;
    }

    public function getCamasOcupadas(): ?array
    {
        return $this->camasOcupadas;
    }

    public function setCamasOcupadas(array $camasOcupadas): self
    {
        $this->camasOcupadas = $camasOcupadas;

        return $this;
    }
}
