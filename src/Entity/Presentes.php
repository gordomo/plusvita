<?php

namespace App\Entity;

use App\Repository\PresentesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PresentesRepository::class)
 */
class Presentes
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class, inversedBy="presentes")
     */
    private $paciente;

    /**
     * @ORM\Column(type="date")
     */
    private $fecha;

    /**
     * @ORM\Column(type="boolean")
     */
    private $valor;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaciente(): ?Cliente
    {
        return $this->paciente;
    }

    public function setPaciente(?Cliente $paciente): self
    {
        $this->paciente = $paciente;

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

    public function getValor(): ?bool
    {
        return $this->valor;
    }

    public function setValor(bool $valor): self
    {
        $this->valor = $valor;

        return $this;
    }
}
