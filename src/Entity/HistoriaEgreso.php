<?php

namespace App\Entity;

use App\Repository\HistoriaEgresoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoriaEgresoRepository::class)
 */
class HistoriaEgreso
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $epicrisis_alta;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class, inversedBy="historiaEgresos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cliente;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fecha;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEpicrisisAlta(): ?string
    {
        return $this->epicrisis_alta;
    }

    public function setEpicrisisAlta(?string $epicrisis_alta): self
    {
        $this->epicrisis_alta = $epicrisis_alta;

        return $this;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): self
    {
        $this->cliente = $cliente;

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
}
