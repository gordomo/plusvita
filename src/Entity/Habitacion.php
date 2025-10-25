<?php

namespace App\Entity;

use App\Repository\HabitacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=HabitacionRepository::class)
 * @UniqueEntity("nombre", message="Ya existe una habitación con este nombre")
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
     * @ORM\Column(type="integer", name="camas_disponibles")
     */
    private $camasDisponibles;

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

    // Métodos eliminados - campo camasOcupadas ya no existe en BD
    // Se mantienen temporalmente para compatibilidad pero retornan valores por defecto
    public function getCamasOcupadas(): ?array
    {
        // Retornar array vacío por compatibilidad con código legacy
        return [];
    }

    public function setCamasOcupadas(array $camasOcupadas): self
    {
        // No-op: este campo ya no se persiste en BD
        // Se mantiene el método para evitar errores en código que aún lo llama
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

    /**
     * Calcula dinámicamente las camas ocupadas consultando los pacientes reales
     * Este método recibe los datos pre-calculados para evitar N+1 queries
     * 
     * @param array $camasOcupadasReales Array con las camas realmente ocupadas [1 => 1, 2 => 2]
     * @return int Cantidad de camas ocupadas
     */
    public function getCamasOcupadasCount(array $camasOcupadasReales = []): int
    {
        return count($camasOcupadasReales);
    }

    /**
     * Calcula dinámicamente las camas disponibles
     * 
     * @param array $camasOcupadasReales Array con las camas realmente ocupadas
     * @return int Cantidad de camas disponibles
     */
    public function getCamasDisponiblesCount(array $camasOcupadasReales = []): int
    {
        return $this->camasDisponibles - $this->getCamasOcupadasCount($camasOcupadasReales);
    }
}
