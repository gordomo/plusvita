<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Ya existe un usuario con este email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $legacyRoles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telefono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $legajo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apellido;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dni;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $modalidad = [];

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="user")
     */
    private $bookings;

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="doctor")
     */
    private $doctorBookings;

    /**
     * @ORM\Column(type="boolean")
     */
    private $habilitado;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class, inversedBy="users")
     * @ORM\JoinTable(name="user_roles")
     */
    private $roles;

    /**
     * @ORM\OneToMany(targetEntity=UserFirma::class, mappedBy="user", cascade={"remove"})
     */
    private $firmas;

    /**
     * @ORM\ManyToMany(targetEntity=Cliente::class, mappedBy="docReferente")
     */
    private $clientes;

    /**
     * @ORM\OneToMany(targetEntity=Prescripcion::class, mappedBy="user")
     */
    private $prescripciones;

    /**
     * @ORM\OneToMany(targetEntity=PresentesDoctores::class, mappedBy="doctor")
     */
    private $presentesDoctores;

    /**
     * @ORM\OneToMany(targetEntity=UserContract::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $contracts;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->doctorBookings = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->firmas = new ArrayCollection();
        $this->clientes = new ArrayCollection();
        $this->prescripciones = new ArrayCollection();
        $this->presentesDoctores = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->habilitado = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getRoles(): array
    {
        // Para compatibilidad con el sistema existente, mantener el campo JSON
        $legacyRoles = $this->legacyRoles ?? [];
        
        // Agregar roles del nuevo sistema
        $newRoles = [];
        foreach ($this->roles as $role) {
            if ($role->getIsActive()) {
                $newRoles[] = 'ROLE_' . strtoupper($role->getName());
            }
        }
        
        // Combinar roles legacy y nuevos
        $allRoles = array_merge($legacyRoles, $newRoles);
        $allRoles[] = 'ROLE_USER'; // Siempre incluir ROLE_USER
        
        return array_unique($allRoles);
    }

    public function setRoles(array $roles): self
    {
        // Mantener compatibilidad con el sistema existente
        $this->legacyRoles = $roles;
        return $this;
    }

    public function getLegacyRoles(): array
    {
        return $this->legacyRoles ?? [];
    }

    /**
     * Get Role entities (new system)
     */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    /**
     * Add a role entity
     */
    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * Remove a role entity
     */
    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);

        return $this;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getName() === $roleName && $role->getIsActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getIsActive() && $role->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getSalt()
    {
        // No se necesita con bcrypt
    }

    public function eraseCredentials()
    {
        // Si almacenas datos temporales sensibles, límpialos aquí
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

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }

    public function setLegajo(?string $legajo): self
    {
        $this->legajo = $legajo;
        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): self
    {
        $this->apellido = $apellido;
        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(?string $dni): self
    {
        $this->dni = $dni;
        return $this;
    }

    public function getNombreApellido(): ?string
    {
        return $this->getNombre() . ' ' . $this->getApellido();
    }

    public function setModalidad(?array $modalidad): self
    {
        $this->modalidad = $modalidad;
        return $this;
    }

    public function getHabilitado(): ?bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): self
    {
        $this->habilitado = $habilitado;
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
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->contains($booking)) {
            $this->bookings->removeElement($booking);
            // set the owning side to null (unless already changed)
            if ($booking->getUser() === $this) {
                $booking->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Método para compatibilidad con el sistema existente
     * Retorna la modalidad del usuario o array vacío si no tiene
     */
    public function getModalidad(): ?array
    {
        return $this->modalidad ?? [];
    }

    /**
     * Método para compatibilidad con el sistema existente
     * Los usuarios administrativos no tienen presente
     */
    public function getPresente(): ?bool
    {
        return false;
    }

    /**
     * @return Collection|UserFirma[]
     */
    public function getFirmas(): Collection
    {
        return $this->firmas;
    }

    public function addFirma(UserFirma $firma): self
    {
        if (!$this->firmas->contains($firma)) {
            $this->firmas[] = $firma;
            $firma->setUser($this);
        }

        return $this;
    }

    public function removeFirma(UserFirma $firma): self
    {
        if ($this->firmas->removeElement($firma)) {
            // set the owning side to null (unless already changed)
            if ($firma->getUser() === $this) {
                $firma->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Get the active signature for this user
     */
    public function getActiveFirma(): ?UserFirma
    {
        foreach ($this->firmas as $firma) {
            if ($firma->getIsActive()) {
                return $firma;
            }
        }
        return null;
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
            $cliente->addDocReferente($this);
        }

        return $this;
    }

    public function removeCliente(Cliente $cliente): self
    {
        if ($this->clientes->contains($cliente)) {
            $this->clientes->removeElement($cliente);
            $cliente->removeDocReferente($this);
        }

        return $this;
    }

    /**
     * @return Collection|Booking[]
     */
    public function getDoctorBookings(): Collection
    {
        return $this->doctorBookings;
    }

    public function addDoctorBooking(Booking $booking): self
    {
        if (!$this->doctorBookings->contains($booking)) {
            $this->doctorBookings[] = $booking;
            $booking->setDoctor($this);
        }

        return $this;
    }

    public function removeDoctorBooking(Booking $booking): self
    {
        if ($this->doctorBookings->contains($booking)) {
            $this->doctorBookings->removeElement($booking);
        }

        return $this;
    }

    /**
     * Obtiene el tipo de profesional basado en el rol principal del usuario
     * Esto se usa para llenar automáticamente el campo "tipo" en evoluciones
     */
    public function getTipoProfesional(): string
    {
        // Mapeo de roles a tipos de profesional
        $rolesToTipo = [
            'nutricionista' => 'Nutricionista',
            'director_medico' => 'Director medico',
            'sub_director_medico' => 'Sub director medico',
            'trabajadora_social' => 'Trabajadora social',
            'psiquiatra' => 'Psiquiatra',
            'infectologo' => 'Infectologo',
            'kinesiologo' => 'Kinesiologo',
            'kinesiologo_respiratorio' => 'Kinesiologo respiratorio',
            'terapista_ocupacional' => 'Terapista ocupacional',
            'fonoaudiologo' => 'Fonoaudiologo',
            'psicologo' => 'Psicologo',
            'fisiatra' => 'Fisiatra',
            'neurologo' => 'Neurologo',
            'cardiologo' => 'Cardiologo',
            'urologo' => 'Urologo',
            'hematologo' => 'Hematologo',
            'neumonologo' => 'Neumonologo',
            'cirujano' => 'Cirujano',
            'traumatologo' => 'Traumatologo',
            'doctor' => 'Medico Clínico',
            'medico_guardia' => 'Medico de guardia',
        ];

        // Buscar el primer rol que coincida
        foreach ($this->roles as $role) {
            $roleName = $role->getName();
            if (isset($rolesToTipo[$roleName])) {
                return $rolesToTipo[$roleName];
            }
        }

        // Default si no se encuentra ningún rol específico
        return 'Profesional por prestacion';
    }

    /**
     * @return Collection|UserContract[]
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(UserContract $contract): self
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts[] = $contract;
            $contract->setUser($this);
        }

        return $this;
    }

    public function removeContract(UserContract $contract): self
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getUser() === $this) {
                $contract->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Obtiene el contrato activo del usuario
     */
    public function getActiveContract(): ?UserContract
    {
        foreach ($this->contracts as $contract) {
            if ($contract->getIsActive()) {
                return $contract;
            }
        }
        return null;
    }

    /**
     * Métodos de compatibilidad para acceso rápido a datos del contrato activo
     */
    public function getInicioContrato(): ?\DateTimeInterface
    {
        $contract = $this->getActiveContract();
        return $contract ? $contract->getInicioContrato() : null;
    }

    public function getVtoContrato(): ?\DateTimeInterface
    {
        $contract = $this->getActiveContract();
        return $contract ? $contract->getVtoContrato() : null;
    }

    public function getCbu(): ?string
    {
        $contract = $this->getActiveContract();
        return $contract ? $contract->getCbu() : null;
    }

    public function getConcepto(): ?string
    {
        $contract = $this->getActiveContract();
        return $contract ? $contract->getConcepto() : null;
    }

    public function getTipoContrato(): ?string
    {
        $contract = $this->getActiveContract();
        return $contract ? $contract->getTipo() : null;
    }
}

