<?php

namespace App\Entity;

use App\Repository\ComentarioReclamoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ComentarioReclamoRepository::class)
 */
class ComentarioReclamo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $texto;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fecha;

    /**
     * @ORM\ManyToOne(targetEntity=Reclamo::class, inversedBy="comentarios")
     */
    private $reclamo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTexto(): ?string
    {
        return $this->texto;
    }

    public function setTexto(string $texto): self
    {
        $this->texto = $texto;

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

    public function getReclamo(): ?Reclamo
    {
        return $this->reclamo;
    }

    public function setReclamo(?Reclamo $reclamo): self
    {
        $this->reclamo = $reclamo;

        return $this;
    }
}
