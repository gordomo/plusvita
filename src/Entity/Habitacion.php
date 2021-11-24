<?php

namespace App\Entity;

use App\Repository\HabitacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @ORM\OneToMany(targetEntity=HistoriaHabitaciones::class, mappedBy="habitacion")
     */
    private $historiaHabitaciones;

    public function __construct()
    {
        $this->historiaHabitaciones = new ArrayCollection();
    }

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

    /**
     * @return Collection|HistoriaHabitaciones[]
     */
    public function getHistoriaHabitaciones(): Collection
    {
        return $this->historiaHabitaciones;
    }

    public function addHistoriaHabitacione(HistoriaHabitaciones $historiaHabitacione): self
    {
        if (!$this->historiaHabitaciones->contains($historiaHabitacione)) {
            $this->historiaHabitaciones[] = $historiaHabitacione;
            $historiaHabitacione->setHabitacion($this);
        }

        return $this;
    }

    public function removeHistoriaHabitacione(HistoriaHabitaciones $historiaHabitacione): self
    {
        if ($this->historiaHabitaciones->removeElement($historiaHabitacione)) {
            // set the owning side to null (unless already changed)
            if ($historiaHabitacione->getHabitacion() === $this) {
                $historiaHabitacione->setHabitacion(null);
            }
        }

        return $this;
    }
}
