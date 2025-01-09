<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ItemUbicacion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Item::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $item;

    /**
     * @ORM\ManyToOne(targetEntity=Ubicacion::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $ubicacion;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaCambio;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getUbicacion(): ?Ubicacion
    {
        return $this->ubicacion;
    }

    public function setUbicacion(?Ubicacion $ubicacion): self
    {
        $this->ubicacion = $ubicacion;

        return $this;
    }

    public function getFechaCambio(): ?\DateTimeInterface
    {
        return $this->fechaCambio;
    }

    public function setFechaCambio(\DateTimeInterface $fechaCambio): self
    {
        $this->fechaCambio = $fechaCambio;

        return $this;
    }
}
