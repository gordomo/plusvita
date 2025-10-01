<?php

namespace App\Entity;

use App\Repository\NovedadUbicacionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NovedadUbicacionRepository::class)
 */
class NovedadUbicacion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Ubicacion::class, inversedBy="novedades")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ubicacion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $titulo;

    /**
     * @ORM\Column(type="text")
     */
    private $descripcion;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fecha_creacion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fecha_resolucion;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $estado;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $prioridad;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $usuario_creador;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $usuario_asignado;

    public function __construct()
    {
        $this->fecha_creacion = new \DateTime();
        $this->estado = 'pendiente';
        $this->prioridad = 'media';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): self
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getFechaCreacion(): ?\DateTimeInterface
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion(\DateTimeInterface $fecha_creacion): self
    {
        $this->fecha_creacion = $fecha_creacion;

        return $this;
    }

    public function getFechaResolucion(): ?\DateTimeInterface
    {
        return $this->fecha_resolucion;
    }

    public function setFechaResolucion(?\DateTimeInterface $fecha_resolucion): self
    {
        $this->fecha_resolucion = $fecha_resolucion;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;

        return $this;
    }

    public function getPrioridad(): ?string
    {
        return $this->prioridad;
    }

    public function setPrioridad(?string $prioridad): self
    {
        $this->prioridad = $prioridad;

        return $this;
    }

    public function getUsuarioCreador(): ?User
    {
        return $this->usuario_creador;
    }

    public function setUsuarioCreador(?User $usuario_creador): self
    {
        $this->usuario_creador = $usuario_creador;

        return $this;
    }

    public function getUsuarioAsignado(): ?User
    {
        return $this->usuario_asignado;
    }

    public function setUsuarioAsignado(?User $usuario_asignado): self
    {
        $this->usuario_asignado = $usuario_asignado;

        return $this;
    }
}
