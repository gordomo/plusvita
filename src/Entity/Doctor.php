<?php

namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=DoctorRepository::class)
 */
class Doctor implements UserInterface
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
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apellido;

    /**
     * @ORM\Column(type="json")
     */
    private $especialidad = [];

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $firma;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $matricula;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\ManyToMany(targetEntity=Cliente::class, inversedBy="docReferente")
     */
    private $clientes;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $modalidad = [];

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $vtoContrato;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $inicioContrato;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tipo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dni;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $vtoMatricula;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $legajo;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fechaBaja;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivoBaja;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $concepto;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telefono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $libretaSanitaria;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $vtoLibretaSanitaria;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $emisionLibretaSanitaria;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $posicionEnArchivo;

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="doctor")
     */
    private $bookings;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $businessHours = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cbu;

    /**
     * @ORM\Column(type="integer", length=2, nullable=true)
     */
    private $max_cli_turno;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fNac;

    /**
     * @return mixed
     */
    public function getMaxCliTurno()
    {
        return $this->max_cli_turno;
    }

    /**
     * @param mixed $max_cli_turno
     */
    public function setMaxCliTurno($max_cli_turno): void
    {
        $this->max_cli_turno = $max_cli_turno;
    }

    public function __construct()
    {
        $this->clientes = new ArrayCollection();
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

    public function getEspecialidad(): ?array
    {
        return $this->especialidad;
    }

    public function setEspecialidad(array $especialidad): self
    {
        $this->especialidad = $especialidad;

        return $this;
    }

    public function getFirma(): ?string
    {
        return $this->firma;
    }

    public function setFirma(?string $firma): self
    {
        $this->firma = $firma;

        return $this;
    }

    public function getRoles(): ?array
    {
        $roles = $this->roles;

        //$roles[] = 'ROLE_STAFF';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $roles = ['ROLE_STAFF'];
        $this->roles = $roles;

        return $this;
    }

    public function getMatricula(): ?string
    {
        return $this->matricula;
    }

    public function setMatricula(string $matricula): self
    {
        $this->matricula = $matricula;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        global $kernel;
        if (method_exists($kernel, 'getKernel'))
            $kernel = $kernel->getKernel();

        $this->password = $kernel->getContainer()->get('security.password_encoder')->encodePassword($this, $password);
        return $this;
    }

    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }



    public function setClientes(?Cliente $clientes): self
    {
        $this->clientes = $clientes;

        return $this;
    }

    /**
     * @return Collection|Cliente[]
     */
    public function getClientes(): Collection
    {
        return $this->clientes;
    }

    public function addCliente(Cliente $cliente): self
    {
        if (!$this->clientes->contains($cliente)) {
            $this->clientes[] = $cliente;
            //$cliente->setDocReferente($this);
        }

        return $this;
    }

    public function removeCliente(Cliente $cliente): self
    {
        if ($this->clientes->contains($cliente)) {
            $this->clientes->removeElement($cliente);
            // set the owning side to null (unless already changed)
            if ($cliente->getDocReferente() === $this) {
                $cliente->setDocReferente(null);
            }
        }

        return $this;
    }

    public function getModalidad(): ?array
    {
        return $this->modalidad;
    }

    public function setModalidad(array $modalidad): self
    {
        $this->modalidad = $modalidad;

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

    public function getInicioContrato(): ?\DateTimeInterface
    {
        return $this->inicioContrato;
    }

    public function setInicioContrato(?\DateTimeInterface $inicioContrato): self
    {
        $this->inicioContrato = $inicioContrato;

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

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    public function getVtoMatricula(): ?\DateTimeInterface
    {
        return $this->vtoMatricula;
    }

    public function setVtoMatricula(?\DateTimeInterface $vtoMatricula): self
    {
        $this->vtoMatricula = $vtoMatricula;

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

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }

    public function setLegajo(string $legajo): self
    {
        $this->legajo = $legajo;

        return $this;
    }

    public function getFechaBaja(): ?\DateTimeInterface
    {
        return $this->fechaBaja;
    }

    public function setFechaBaja(?\DateTimeInterface $fechaBaja): self
    {
        $this->fechaBaja = $fechaBaja;

        return $this;
    }

    public function getMotivoBaja(): ?string
    {
        return $this->motivoBaja;
    }

    public function setMotivoBaja(?string $motivoBaja): self
    {
        $this->motivoBaja = $motivoBaja;

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

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getLibretaSanitaria(): ?string
    {
        return $this->libretaSanitaria;
    }

    public function setLibretaSanitaria(?string $libretaSanitaria): self
    {
        $this->libretaSanitaria = $libretaSanitaria;

        return $this;
    }

    public function getVtoLibretaSanitaria(): ?\DateTimeInterface
    {
        return $this->vtoLibretaSanitaria;
    }

    public function setVtoLibretaSanitaria(?\DateTimeInterface $vtoLibretaSanitaria): self
    {
        $this->vtoLibretaSanitaria = $vtoLibretaSanitaria;

        return $this;
    }

    public function getEmisionLibretaSanitaria(): ?\DateTimeInterface
    {
        return $this->emisionLibretaSanitaria;
    }

    public function setEmisionLibretaSanitaria(?\DateTimeInterface $emisionLibretaSanitaria): self
    {
        $this->emisionLibretaSanitaria = $emisionLibretaSanitaria;

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
     * @return Collection|Booking[]
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setDoctor($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->contains($booking)) {
            $this->bookings->removeElement($booking);
            // set the owning side to null (unless already changed)
            if ($booking->getDoctor() === $this) {
                $booking->setDoctor(null);
            }
        }

        return $this;
    }

    public function getBusinessHours(): ?array
    {
        return $this->businessHours;
    }

    public function setBusinessHours(?array $businessHours): self
    {
        $this->businessHours = $businessHours;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getFNac(): ?\DateTimeInterface
    {
        return $this->fNac;
    }

    public function setFNac(?\DateTimeInterface $fNac): self
    {
        $this->fNac = $fNac;

        return $this;
    }
}
