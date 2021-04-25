<?php

namespace App\Entity;

use App\Repository\TipoConsumibleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TipoConsumibleRepository::class)
 */
class TipoConsumible
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $nombre;

    /**
     * @ORM\OneToMany(targetEntity=Consumible::class, mappedBy="tipo")
     */
    private $comsumibles;

    public function __construct()
    {
        $this->comsumibles = new ArrayCollection();
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

    /**
     * @return Collection|Consumible[]
     */
    public function getComsumibles(): Collection
    {
        return $this->comsumibles;
    }

    public function addComsumible(Consumible $comsumible): self
    {
        if (!$this->comsumibles->contains($comsumible)) {
            $this->comsumibles[] = $comsumible;
            $comsumible->setTipo($this);
        }

        return $this;
    }

    public function removeComsumible(Consumible $comsumible): self
    {
        if ($this->comsumibles->contains($comsumible)) {
            $this->comsumibles->removeElement($comsumible);
            // set the owning side to null (unless already changed)
            if ($comsumible->getTipo() === $this) {
                $comsumible->setTipo(null);
            }
        }

        return $this;
    }
}
