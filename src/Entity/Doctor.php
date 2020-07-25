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
     * @ORM\Column(type="string", length=255)
     */
    private $matricula;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @ORM\Column(type="string", length=255)
     */
    private $modalidad;

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


    const ESPECIALIDADES = [
                            'Clínico' => 0,
                            'Fonoaudiología' => 1,
                            'Kinesiología Motora' => 2,
                            'Kinesiología Respiratoria' => 3,
                            'Psicología' => 4,
                            'Psicopedagogía' => 5,
                            'Terapia Ocupacional' => 6,
                            'Hidroterapia' => 7,
                            'Nutrición' => 8,
                            'Terapia Intensiva' => 9,
                            'Dirección' => 10,
                            'Sub Dirección' => 11,
                            'Clínica Médica' => 12,
                            'Psicriatía' => 13,
                            'Neurología' => 14,
                            'Fisiatría' => 15,
                            'Cardiología' => 16,
                            'Hematología' => 17,
                            'Cirugía' => 18,
                            'Urología' => 19,
                            'Infectología' => 20,
                            'Traumatología' => 21,
                            'Trabajo Social' => 22,
                            'Guardia Médica' => 23,
                            ];


    public function __construct()
    {
        $this->clientes = new ArrayCollection();
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
    //<td>{{ doctor.especialidad ? doctor.especialidad|json_encode(constant('JSON_PRETTY_PRINT'))|replace({'[': '', ']': ''}) : '' }}</td>
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
        // guarantee every user at least has ROLE_DOCTOR
        $roles[] = 'ROLE_DOCTOR';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = '[ROLE_DOCTOR]';

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
            $cliente->setDocReferente($this);
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

    public function getModalidad(): ?string
    {
        return $this->modalidad;
    }

    public function setModalidad(string $modalidad): self
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
}
