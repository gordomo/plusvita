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
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dni;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
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
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="cliente")
     */
    private $bookings;

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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $obraSocial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vinculoResponsable;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fNacimiento;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $edad;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sistemaDeEmergenciaNombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sistemaDeEmergenciaTel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sistemaDeEmergenciaAfiliado;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $obraSocialTelefono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $obraSocialAfiliado;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoDePago;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $posicionEnArchivo;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\HistoriaPaciente", mappedBy="cliente")
     */
    private $historia;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $habPrivada;

    public function __construct()
    {
        $this->docReferente = new ArrayCollection();
        $this->bookings = new ArrayCollection();
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

    public function getObraSocial(): ?string
    {
        return $this->obraSocial;
    }

    public function setObraSocial(?string $obraSocial): self
    {
        $this->obraSocial = $obraSocial;

        return $this;
    }

    public function getVinculoResponsable(): ?string
    {
        return $this->vinculoResponsable;
    }

    public function setVinculoResponsable(?string $vinculoResponsable): self
    {
        $this->vinculoResponsable = $vinculoResponsable;

        return $this;
    }

    public function getFNacimiento(): ?\DateTimeInterface
    {
        return $this->fNacimiento;
    }

    public function setFNacimiento(?\DateTimeInterface $fNacimiento): self
    {
        $this->fNacimiento = $fNacimiento;

        return $this;
    }

    public function getEdad(): ?string
    {
        return $this->edad;
    }

    public function setEdad($edad): self
    {
        $this->edad = $edad;
        return $this;
    }

    public function getSistemaDeEmergenciaNombre(): ?string
    {
        return $this->sistemaDeEmergenciaNombre;
    }

    public function setSistemaDeEmergenciaNombre(?string $sistemaDeEmergenciaNombre): self
    {
        $this->sistemaDeEmergenciaNombre = $sistemaDeEmergenciaNombre;

        return $this;
    }

    public function getSistemaDeEmergenciaTel(): ?string
    {
        return $this->sistemaDeEmergenciaTel;
    }

    public function setSistemaDeEmergenciaTel(?string $sistemaDeEmergenciaTel): self
    {
        $this->sistemaDeEmergenciaTel = $sistemaDeEmergenciaTel;

        return $this;
    }

    public function getSistemaDeEmergenciaAfiliado(): ?string
    {
        return $this->sistemaDeEmergenciaAfiliado;
    }

    public function setSistemaDeEmergenciaAfiliado(?string $sistemaDeEmergenciaAfiliado): self
    {
        $this->sistemaDeEmergenciaAfiliado = $sistemaDeEmergenciaAfiliado;

        return $this;
    }

    public function getObraSocialTelefono(): ?string
    {
        return $this->obraSocialTelefono;
    }

    public function setObraSocialTelefono(?string $obraSocialTelefono): self
    {
        $this->obraSocialTelefono = $obraSocialTelefono;

        return $this;
    }

    public function getObraSocialAfiliado(): ?string
    {
        return $this->obraSocialAfiliado;
    }

    public function setObraSocialAfiliado(?string $obraSocialAfiliado): self
    {
        $this->obraSocialAfiliado = $obraSocialAfiliado;

        return $this;
    }

    public function getTipoDePago(): ?string
    {
        return $this->tipoDePago;
    }

    public function setTipoDePago(?string $tipoDePago): self
    {
        $this->tipoDePago = $tipoDePago;

        return $this;
    }

    public function getPosicionEnArchivo(): ?string
    {
        return $this->posicionEnArchivo;
    }

    public function setPosicionEnArchivo(?string $posicionEnArchivo): self
    {
        $this->posicionEnArchivo = $posicionEnArchivo;

        return $this;
    }

    /**
     * @return HistoriaPaciente
     */
    public function getHistoria()
    {
        return $this->historia;
    }

    /**
     * @param HistoriaPaciente $historia
     */
    public function setHistoria(HistoriaPaciente $historia): void
    {
        $this->historia = $historia;
    }

    /**
     * @return mixed
     */
    public function getHabPrivada()
    {
        return $this->habPrivada;
    }

    /**
     * @param mixed $habPrivada
     */
    public function setHabPrivada($habPrivada): void
    {
        $this->habPrivada = $habPrivada;
    }


    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setCliente($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->contains($booking)) {
            $this->bookings->removeElement($booking);
            // set the owning side to null (unless already changed)
            if ($booking->getCliente() === $this) {
                $booking->setCliente(null);
            }
        }

        return $this;
    }


}
