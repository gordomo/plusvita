<?php

namespace App\Entity;

use App\Repository\NotasTurnoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotasTurnoRepository::class)
 */
class NotasTurno
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity=Booking::class, inversedBy="notas")
     * @ORM\JoinColumn(nullable=false)
     */
    private $turno;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTurno(): ?Booking
    {
        return $this->turno;
    }

    public function setTurno(?Booking $turno): self
    {
        $this->turno = $turno;

        return $this;
    }
}
