<?php

namespace App\Entity;

use App\Repository\ReclamoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReclamoRepository::class)
 */
class Reclamo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $tipo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $area;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adjunto;

    /**
     * @ORM\Column(type="text")
     */
    private $texto;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contacto;

    /**
     * @ORM\Column(type="integer")
     */
    private $estado;

    /**
     * @ORM\OneToMany(targetEntity=ComentarioReclamo::class, mappedBy="reclamo")
     */
    private $comentarios;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fecha;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaUltimaActualizacion;

    public function __construct()
    {
        $this->comentarios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): ?int
    {
        return $this->tipo;
    }

    public function setTipo(int $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(string $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getAdjunto(): ?string
    {
        return $this->adjunto;
    }

    public function setAdjunto(?string $adjunto): self
    {
        $this->adjunto = $adjunto;

        return $this;
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

    public function getContacto(): ?string
    {
        return $this->contacto;
    }

    public function setContacto(?string $contacto): self
    {
        $this->contacto = $contacto;

        return $this;
    }

    public function getEstado(): ?int
    {
        return $this->estado ? $this->estado : 1;
    }

    public function setEstado(int $estado = 1): self
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection<int, ComentarioReclamo>
     */
    public function getComentarios(): Collection
    {
        return $this->comentarios;
    }

    public function addComentario(ComentarioReclamo $comentario): self
    {
        if (!$this->comentarios->contains($comentario)) {
            $this->comentarios[] = $comentario;
            $comentario->setReclamo($this);
        }

        return $this;
    }

    public function removeComentario(ComentarioReclamo $comentario): self
    {
        if ($this->comentarios->removeElement($comentario)) {
            // set the owning side to null (unless already changed)
            if ($comentario->getReclamo() === $this) {
                $comentario->setReclamo(null);
            }
        }

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

    public function getFechaUltimaActualizacion(): ?\DateTimeInterface
    {
        return $this->fechaUltimaActualizacion;
    }

    public function setFechaUltimaActualizacion(\DateTimeInterface $fechaUltimaActualizacion): self
    {
        $this->fechaUltimaActualizacion = $fechaUltimaActualizacion;

        return $this;
    }
}
