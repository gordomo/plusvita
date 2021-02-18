<?php

namespace App\Entity;

use App\Repository\ConsumibleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsumibleRepository::class)
 */
class Consumible
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
    private $existencia;

    /**
     * @ORM\Column(type="float")
     */
    private $precio;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unidades;

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

    public function getExistencia(): ?int
    {
        return $this->existencia;
    }

    public function setExistencia(int $existencia): self
    {
        $this->existencia = $existencia;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): self
    {
        $this->precio = $precio;

        return $this;
    }

    public function getUnidades(): ?string
    {
        return $this->unidades;
    }

    public function setUnidades(?string $unidades): self
    {
        $this->unidades = $unidades;

        return $this;
    }

    /**
     * @return Collection|Cliente[]
     */
    public function getCliente(): Collection
    {
        return $this->cliente;
    }

    public function addCliente(Cliente $cliente): self
    {
        if (!$this->cliente->contains($cliente)) {
            $this->cliente[] = $cliente;
        }

        return $this;
    }

    public function removeCliente(Cliente $cliente): self
    {
        if ($this->cliente->contains($cliente)) {
            $this->cliente->removeElement($cliente);
        }

        return $this;
    }
}
