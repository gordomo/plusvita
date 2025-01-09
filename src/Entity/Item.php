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
     * @ORM\Column(type="integer")
     */
    private $cantidad;

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

    public function __construct()
    {
        $this->movimientos = new ArrayCollection();
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

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): self
    {
        $this->cantidad = $cantidad;

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
}
