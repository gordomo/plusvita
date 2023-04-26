<?php

namespace App\Entity;

use App\Repository\ObraSocialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ObraSocialRepository::class)
 */
class ObraSocial
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tel;

    /**
     * @ORM\OneToMany(targetEntity=Cliente::class, mappedBy="obraSocial")
     */
    private $clientes;

    public function __construct()
    {
        $this->clientes = new ArrayCollection();
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

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * @return Collection|Cliente[]
     */
    public function getClientes(): Collection
    {
        return $this->clientes;
    }

    public function addCliente(Cliente $cliente): self
    {
        if (!$this->clientes->contains($cliente)) {
            $this->clientes[] = $cliente;
            $cliente->setObraSocial($this);
        }

        return $this;
    }

    public function removeCliente(Cliente $cliente): self
    {
        if ($this->clientes->removeElement($cliente)) {
            // set the owning side to null (unless already changed)
            if ($cliente->getObraSocial() === $this) {
                $cliente->setObraSocial(null);
            }
        }

        return $this;
    }
}
