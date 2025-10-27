<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserContractRepository")
 * @ORM\Table(name="user_contract")
 */
class UserContract
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="contracts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tipo;

    /**
     * @ORM\Column(type="date")
     */
    private $inicioContrato;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $vtoContrato;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cbu;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $concepto;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $observaciones;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getInicioContrato(): ?\DateTimeInterface
    {
        return $this->inicioContrato;
    }

    public function setInicioContrato(\DateTimeInterface $inicioContrato): self
    {
        $this->inicioContrato = $inicioContrato;
        return $this;
    }

    public function getVtoContrato(): ?\DateTimeInterface
    {
        return $this->vtoContrato;
    }

    public function setVtoContrato(?\DateTimeInterface $vtoContrato): self
    {
        $this->vtoContrato = $vtoContrato;
        return $this;
    }

    public function getCbu(): ?string
    {
        return $this->cbu;
    }

    public function setCbu(?string $cbu): self
    {
        $this->cbu = $cbu;
        return $this;
    }

    public function getConcepto(): ?string
    {
        return $this->concepto;
    }

    public function setConcepto(?string $concepto): self
    {
        $this->concepto = $concepto;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): self
    {
        $this->observaciones = $observaciones;
        return $this;
    }

    /**
     * Verifica si el contrato está vencido
     */
    public function isExpired(): bool
    {
        if (!$this->vtoContrato) {
            return false; // Sin fecha de vencimiento = indefinido
        }
        
        $now = new \DateTime();
        return $this->vtoContrato < $now;
    }

    /**
     * Verifica si el contrato vence en el mes actual
     */
    public function expiresThisMonth(): bool
    {
        if (!$this->vtoContrato) {
            return false;
        }
        
        $now = new \DateTime();
        return $this->vtoContrato->format('Y-m') === $now->format('Y-m');
    }

    /**
     * Obtiene el nombre legible del tipo de contrato
     */
    public function getTipoLabel(): string
    {
        $tipos = [
            '0' => 'Sin tipo',
            '1' => 'Empleado',
            '2' => 'Contrato Directo',
            '3' => 'Contrato por Prestación',
            '4' => 'Prestación Directa',
        ];

        return $tipos[$this->tipo] ?? 'Desconocido';
    }
}
