<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClienteRepository::class)
 */
class Cliente
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hClinica;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apellido;

    /**
     * @ORM\Column(type="integer")
     */
    private $dni;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telefono;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fIngreso;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fEgreso;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $motivoIng;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $motivoEgr;

    /**
     * @ORM\Column(type="boolean")
     */
    private $activo;

    /**
     * @ORM\ManyToMany(targetEntity=Doctor::class, mappedBy="clientes")
     */
    private $docReferente;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vieneDe;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $docDerivante;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $modalidad;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivoIngEspecifico;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $habitacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nCama;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $familiarResponsableNombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $familiarResponsableTel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $familiarResponsableMail;


    public function __construct()
    {
        $this->docReferente = new ArrayCollection();
    }

    public function getNombreApellido(): ?string
    {
        return $this->getNombre() . ' ' . $this->getApellido();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHClinica(): ?string
    {
        return $this->hClinica;
    }

    public function setHClinica(string $hClinica): self
    {
        $this->hClinica = $hClinica;

        return $this;
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

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): self
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getDni(): ?int
    {
        return $this->dni;
    }

    public function setDni(int $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getFIngreso(): ?\DateTimeInterface
    {
        return $this->fIngreso;
    }

    public function setFIngreso(?\DateTimeInterface $fIngreso): self
    {
        $this->fIngreso = $fIngreso;

        return $this;
    }

    public function getFEgreso(): ?\DateTimeInterface
    {
        return $this->fEgreso;
    }

    public function setFEgreso(?\DateTimeInterface $fEgreso): self
    {
        $this->fEgreso = $fEgreso;

        return $this;
    }

    public function getMotivoIng(): ?int
    {
        return $this->motivoIng;
    }

    public function setMotivoIng(?int $motivoIng): self
    {
        $this->motivoIng = $motivoIng;

        return $this;
    }

    public function getMotivoEgr(): ?int
    {
        return $this->motivoEgr;
    }

    public function setMotivoEgr(?int $motivoEgr): self
    {
        $this->motivoEgr = $motivoEgr;

        return $this;
    }

    public function getActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;

        return $this;
    }

    public function setDocReferente(?Doctor $docReferente): self
    {
        $this->docReferente = $docReferente;

        return $this;
    }

    /**
     * @return Collection|Doctor[]
     */
    public function getDocReferente(): Collection
    {
        return $this->docReferente;
    }

    public function addDocReferente(Doctor $docReferente): self
    {
        if (!$this->docReferente->contains($docReferente)) {
            $this->docReferente[] = $docReferente;
            $docReferente->setClientes($this);
        }

        return $this;
    }

    public function removeDocReferente(Doctor $docReferente): self
    {
        if ($this->docReferente->contains($docReferente)) {
            $this->docReferente->removeElement($docReferente);
            // set the owning side to null (unless already changed)
            if ($docReferente->getClientes() === $this) {
                $docReferente->setClientes(null);
            }
        }

        return $this;
    }

    public function getVieneDe(): ?string
    {
        return $this->vieneDe;
    }

    public function setVieneDe(?string $vieneDe): self
    {
        $this->vieneDe = $vieneDe;

        return $this;
    }

    public function getDocDerivante(): ?string
    {
        return $this->docDerivante;
    }

    public function setDocDerivante(?string $docDerivante): self
    {
        $this->docDerivante = $docDerivante;

        return $this;
    }

    public function getModalidad(): ?string
    {
        return $this->modalidad;
    }

    public function setModalidad(string $modalidad): self
    {
        $this->modalidad = $modalidad;

        return $this;
    }

    public function getMotivoIngEspecifico(): ?string
    {
        return $this->motivoIngEspecifico;
    }

    public function setMotivoIngEspecifico(?string $motivoIngEspecifico): self
    {
        $this->motivoIngEspecifico = $motivoIngEspecifico;

        return $this;
    }

    public function getHabitacion(): ?string
    {
        return $this->habitacion;
    }

    public function setHabitacion(?string $habitacion): self
    {
        $this->habitacion = $habitacion;

        return $this;
    }

    public function getNCama(): ?string
    {
        return $this->nCama;
    }

    public function setNCama(?string $nCama): self
    {
        $this->nCama = $nCama;

        return $this;
    }

    public function getFamiliarResponsableNombre(): ?string
    {
        return $this->familiarResponsableNombre;
    }

    public function setFamiliarResponsableNombre(?string $familiarResponsableNombre): self
    {
        $this->familiarResponsableNombre = $familiarResponsableNombre;

        return $this;
    }

    public function getFamiliarResponsableTel(): ?string
    {
        return $this->familiarResponsableTel;
    }

    public function setFamiliarResponsableTel(?string $familiarResponsableTel): self
    {
        $this->familiarResponsableTel = $familiarResponsableTel;

        return $this;
    }

    public function getFamiliarResponsableMail(): ?string
    {
        return $this->familiarResponsableMail;
    }

    public function setFamiliarResponsableMail(?string $familiarResponsableMail): self
    {
        $this->familiarResponsableMail = $familiarResponsableMail;

        return $this;
    }
}
