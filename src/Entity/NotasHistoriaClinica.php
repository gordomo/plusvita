<?php

namespace App\Entity;

use App\Repository\NotasHistoriaClinicaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotasHistoriaClinicaRepository::class)
 */
class NotasHistoriaClinica
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Doctor::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user_id;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Cliente", inversedBy="notasHistoriaClinica")
     */
    private $cliente;

    /**
     * @ORM\Column(type="date")
     */
    private $fecha;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?Doctor
    {
        return $this->user_id;
    }

    public function setUserId(?Doctor $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getClient(): ?Cliente
    {
        return $this->client;
    }

    public function setClient(?Cliente $client): self
    {
        $this->client = $client;

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
