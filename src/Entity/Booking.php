<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BookingRepository::class)
 */
class Booking
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $beginAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="bookings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Doctor::class, inversedBy="bookings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $doctor;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class, inversedBy="bookings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cliente;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeginAt(): ?\DateTimeInterface
    {
        return $this->beginAt;
    }
    public function getBeginAtForEvent(): ?String
    {
        $beginAt = $this->beginAt;
        return $beginAt->format('Y-m-dTH:m:s');
    }

    public function setBeginAt(\DateTimeInterface $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function getEndAtForEvent(): ?String
    {
        $endAt = $this->endAt;
        return $endAt->format('Y-m-dTH:m:s');
    }

    public function setEndAt(?\DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->getClienteName() . " - " . $this->getDoctorName();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
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

    /**
     * @return mixed
     */
    public function getDoctor()
    {
        return $this->doctor;
    }

    /**
     * @param mixed $doctor
     */
    public function setDoctor($doctor): void
    {
        $this->doctor = $doctor;
    }

    /**
     * @return mixed
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    public function getClienteName(): ?string
    {
        $clienteName = !empty($this->cliente) ? $this->cliente->getNombre() : '';
        $doctoreName = !empty($this->cliente) ? $this->cliente->getApellido() : '';
        return $clienteName . ' ' . $doctoreName;
    }

    public function getDoctorName(): ?string
    {
        $doctorName = !empty($this->doctor) ? $this->doctor->getApellido() : '';
        return $doctorName;
    }

    /**
     * @param mixed $cliente
     */
    public function setCliente($cliente): void
    {
        $this->cliente = $cliente;
    }
}
