<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ItemRepository::class)
 */
class Item
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
     * @ORM\ManyToOne(targetEntity=TipoItem::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $tipo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $codigo_qr;

    /**
     * @ORM\OneToMany(targetEntity=Movimiento::class, mappedBy="item", orphanRemoval=true)
     */
    private $movimientos;

    /**
     * @ORM\ManyToOne(targetEntity=Ubicacion::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ubicacion_actual;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imagen;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $identificador;

    /**
     * @ORM\OneToMany(targetEntity=NotaItem::class, mappedBy="item", orphanRemoval=true)
     */
    private $notas;

    public function __construct()
    {
        $this->movimientos = new ArrayCollection();
        $this->notas = new ArrayCollection();
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

    public function getTipo(): ?TipoItem
    {
        return $this->tipo;
    }

    public function setTipo(?TipoItem $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getCodigoQr(): ?string
    {
        return $this->codigo_qr;
    }

    public function setCodigoQr(string $codigo_qr): self
    {
        $this->codigo_qr = $codigo_qr;

        return $this;
    }

    /**
     * @return Collection<int, Movimiento>
     */
    public function getMovimientos(): Collection
    {
        return $this->movimientos;
    }

    public function addMovimiento(Movimiento $movimiento): self
    {
        if (!$this->movimientos->contains($movimiento)) {
            $this->movimientos[] = $movimiento;
            $movimiento->setItem($this);
        }

        return $this;
    }

    public function removeMovimiento(Movimiento $movimiento): self
    {
        if ($this->movimientos->removeElement($movimiento)) {
            // set the owning side to null (unless already changed)
            if ($movimiento->getItem() === $this) {
                $movimiento->setItem(null);
            }
        }

        return $this;
    }

    public function getUbicacionActual(): ?Ubicacion
    {
        return $this->ubicacion_actual;
    }

    public function setUbicacionActual(?Ubicacion $ubicacion_actual): self
    {
        $this->ubicacion_actual = $ubicacion_actual;

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): self
    {
        $this->imagen = $imagen;

        return $this;
    }
    
    public function getIdentificador(): ?string
    {
        return $this->identificador;
    }

    public function setIdentificador(?string $identificador): self
    {
        $this->identificador = $identificador;

        return $this;
    }

    /**
     * @return Collection<int, NotaItem>
     */
    public function getNotas(): Collection
    {
        return $this->notas;
    }

    public function addNota(NotaItem $nota): self
    {
        if (!$this->notas->contains($nota)) {
            $this->notas[] = $nota;
            $nota->setItem($this);
        }

        return $this;
    }

    public function removeNota(NotaItem $nota): self
    {
        if ($this->notas->removeElement($nota)) {
            // set the owning side to null (unless already changed)
            if ($nota->getItem() === $this) {
                $nota->setItem(null);
            }
        }

        return $this;
    }
}
