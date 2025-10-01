<?php

namespace App\Entity;

use App\Repository\UbicacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UbicacionRepository::class)
 */
class Ubicacion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $descripcion;

    /**
     * @ORM\OneToMany(targetEntity=Item::class, mappedBy="ubicacion_actual", orphanRemoval=true)
     */
    private $items;

    /**
     * @ORM\OneToMany(targetEntity=NovedadUbicacion::class, mappedBy="ubicacion", orphanRemoval=true)
     */
    private $novedades;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->novedades = new ArrayCollection();
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setUbicacionActual($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getUbicacionActual() === $this) {
                $item->setUbicacionActual(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NovedadUbicacion>
     */
    public function getNovedades(): Collection
    {
        return $this->novedades;
    }

    public function addNovedade(NovedadUbicacion $novedade): self
    {
        if (!$this->novedades->contains($novedade)) {
            $this->novedades[] = $novedade;
            $novedade->setUbicacion($this);
        }

        return $this;
    }

    public function removeNovedade(NovedadUbicacion $novedade): self
    {
        if ($this->novedades->removeElement($novedade)) {
            // set the owning side to null (unless already changed)
            if ($novedade->getUbicacion() === $this) {
                $novedade->setUbicacion(null);
            }
        }

        return $this;
    }
   
}