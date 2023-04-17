<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
     * @ORM\Column(type="integer", nullable=true, unique=true)
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
     * @ORM\Column(type="string", length=255, nullable=true)
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
    #[Ignore]
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
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $familiarResponsableAcompanante = false;

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

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $disponibleParaTerapia = true;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $derivado = false;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $dePermiso = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $derivadoEn;

    /**
     * @ORM\Column(type="date", length=255, nullable=true)
     */
    private $fechaDerivacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivoDerivacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $empTrasladoDerivacion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaReingresoDerivacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivoReingresoDerivacion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaBajaPorPermiso;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaAltaPorPermiso;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $terapiasHabilitadas = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $terapiasNoHabilitadas = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sesionesDisp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $formNum;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $vtoSesiones;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $mediaSesion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dieta;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $ambulatorio;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fechaAmbulatorio;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fechaReingresoAmbulatorio;

    /**
     * @ORM\OneToMany(targetEntity=NotasHistoriaClinica::class, mappedBy="cliente", orphanRemoval=true)
     */
    private $notasHistoriaClinica;

    /**
     * @ORM\OneToOne(targetEntity=HistoriaIngreso::class, mappedBy="cliente", cascade={"persist", "remove"})
     */
    private $historiaIngreso;

    /**
     * @ORM\OneToMany(targetEntity=HistoriaHabitaciones::class, mappedBy="cliente")
     */
    private $historiaHabitaciones;

    /**
     * @ORM\OneToMany(targetEntity=Evolucion::class, mappedBy="paciente", orphanRemoval=true)
     * @ORM\OrderBy({"fecha" = "asc"})
     */
    private $evolucions;

    /**
     * @ORM\OneToMany(targetEntity=HistoriaEgreso::class, mappedBy="cliente", orphanRemoval=true)
     */
    private $historiaEgresos;

    /**
     * @ORM\OneToMany(targetEntity=Prescripcion::class, mappedBy="cliente")
     */
    private $prescripcion;

    public function __construct()
    {
        $this->docReferente = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->notasHistoriaClinica = new ArrayCollection();
        $this->historiaHabitaciones = new ArrayCollection();
        $this->evolucions = new ArrayCollection();
        $this->historiaEgresos = new ArrayCollection();
        $this->prescripcion = new ArrayCollection();
        $this->obraSocial = new ArrayCollection();
    }

    public function getNombreApellido(): ?string
    {
        return $this->getNombre() . ' ' . $this->getApellido();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHClinica(): ?int
    {
        return $this->hClinica;
    }

    public function setHClinica(int $hClinica): self
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

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
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

    public function setDocReferente(?ArrayCollection $docReferente): self
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
            $docReferente->addCliente($this);
        }

        return $this;
    }

    public function removeDocReferente(Doctor $docReferente): self
    {
        if ($this->docReferente->contains($docReferente)) {
            $this->docReferente->removeElement($docReferente);
            // set the owning side to null (unless already changed)
            if ($docReferente->getClientes()->contains($this)) {
                $docReferente->removeCliente($this);
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

    public function getLastHistoriaInternado($from, $to)
    {
        //modalidad 2 es internado
        $criteria = Criteria::create()->where(Criteria::expr()->eq("modalidad", '2'))->andWhere(Criteria::expr()->gte("fecha", $from))->andWhere(Criteria::expr()->lte("fecha", $to));
        return $this->getHistoria()->matching($criteria)->last();
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

    public function getBookings() {
        return $this->bookings;
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

    /**
     * @return bool
     */
    public function getFamiliarResponsableAcompanante(): bool
    {
        return $this->familiarResponsableAcompanante;
    }

    /**
     * @param bool $familiarResponsableAcompanante
     */
    public function setFamiliarResponsableAcompanante(bool $familiarResponsableAcompanante): void
    {
        $this->familiarResponsableAcompanante = $familiarResponsableAcompanante;
    }

    public function getDisponibleParaTerapia(): ?bool
    {
        return $this->disponibleParaTerapia;
    }

    public function setDisponibleParaTerapia(bool $disponibleParaTerapia): self
    {
        $this->disponibleParaTerapia = $disponibleParaTerapia;

        return $this;
    }

    public function getDerivado(): ?bool
    {
        return $this->derivado;
    }

    public function setDerivado(bool $derivado): self
    {
        $this->derivado = $derivado;

        return $this;
    }

    public function getDePermiso(): ?bool
    {
        return $this->dePermiso;
    }

    public function setDePermiso(bool $dePermiso): self
    {
        $this->dePermiso = $dePermiso;

        return $this;
    }

    public function getDerivadoEn(): ?string
    {
        return $this->derivadoEn;
    }

    public function setDerivadoEn(?string $derivadoEn): self
    {
        $this->derivadoEn = $derivadoEn;

        return $this;
    }

    public function getFechaDerivacion(): ?\DateTimeInterface
    {
        return $this->fechaDerivacion;
    }

    public function setFechaDerivacion(?\DateTimeInterface $fechaDerivacion): self
    {
        $this->fechaDerivacion = $fechaDerivacion;

        return $this;
    }

    public function getMotivoDerivacion(): ?string
    {
        return $this->motivoDerivacion;
    }

    public function setMotivoDerivacion(?string $motivoDerivacion): self
    {
        $this->motivoDerivacion = $motivoDerivacion;

        return $this;
    }

    public function getEmpTrasladoDerivacion(): ?string
    {
        return $this->empTrasladoDerivacion;
    }

    public function setEmpTrasladoDerivacion(?string $empTrasladoDerivacion): self
    {
        $this->empTrasladoDerivacion = $empTrasladoDerivacion;

        return $this;
    }

    public function getFechaReingresoDerivacion(): ?\DateTimeInterface
    {
        return $this->fechaReingresoDerivacion;
    }

    public function setFechaReingresoDerivacion(?\DateTimeInterface $fechaReingresoDerivacion): self
    {
        $this->fechaReingresoDerivacion = $fechaReingresoDerivacion;

        return $this;
    }

    public function getMotivoReingresoDerivacion(): ?string
    {
        return $this->motivoReingresoDerivacion;
    }

    public function setMotivoReingresoDerivacion(?string $motivoReingresoDerivacion): self
    {
        $this->motivoReingresoDerivacion = $motivoReingresoDerivacion;

        return $this;
    }

    public function getFechaBajaPorPermiso(): ?\DateTimeInterface
    {
        return $this->fechaBajaPorPermiso;
    }

    public function setFechaBajaPorPermiso(?\DateTimeInterface $fechaBajaPorPermiso): self
    {
        $this->fechaBajaPorPermiso = $fechaBajaPorPermiso;

        return $this;
    }

    public function getFechaAltaPorPermiso(): ?\DateTimeInterface
    {
        return $this->fechaAltaPorPermiso;
    }

    public function setFechaAltaPorPermiso(?\DateTimeInterface $fechaAltaPorPermiso): self
    {
        $this->fechaAltaPorPermiso = $fechaAltaPorPermiso;

        return $this;
    }

    public function getTerapiasHabilitadas(): ?array
    {
        return $this->terapiasHabilitadas;
    }

    public function setTerapiasHabilitadas(?array $terapiasHabilitadas): self
    {
        $this->terapiasHabilitadas = $terapiasHabilitadas;

        return $this;
    }

    public function getTerapiasNoHabilitadas(): ?array
    {
        return $this->terapiasNoHabilitadas;
    }

    public function setTerapiasNoHabilitadas(?array $terapiasNoHabilitadas): self
    {
        $this->terapiasNoHabilitadas = $terapiasNoHabilitadas;

        return $this;
    }

    public function getSesionesDisp(): ?int
    {
        return $this->sesionesDisp;
    }

    public function setSesionesDisp(?int $sesionesDisp): self
    {
        $this->sesionesDisp = $sesionesDisp;

        return $this;
    }

    public function getFormNum(): ?string
    {
        return $this->formNum;
    }

    public function setFormNum(?string $formNum): self
    {
        $this->formNum = $formNum;

        return $this;
    }

    public function getVtoSesiones(): ?\DateTimeInterface
    {
        return $this->vtoSesiones;
    }

    public function setVtoSesiones(?\DateTimeInterface $vtoSesiones): self
    {
        $this->vtoSesiones = $vtoSesiones;

        return $this;
    }

    public function getMediaSesion(): ?bool
    {
        return $this->mediaSesion;
    }

    public function setMediaSesion(?bool $mediaSesion): self
    {
        $this->mediaSesion = $mediaSesion;

        return $this;
    }

    public function getDieta(): ?string
    {
        return $this->dieta;
    }

    public function setDieta(?string $dieta): self
    {
        $this->dieta = $dieta;

        return $this;
    }

    public function getAmbulatorio(): ?bool
    {
        return $this->ambulatorio;
    }

    public function setAmbulatorio(bool $ambulatorio): self
    {
        $this->ambulatorio = $ambulatorio;

        return $this;
    }

    public function getFechaAmbulatorio(): ?\DateTimeInterface
    {
        return $this->fechaAmbulatorio;
    }

    public function setFechaAmbulatorio(?\DateTimeInterface $fechaAmbulatorio): self
    {
        $this->fechaAmbulatorio = $fechaAmbulatorio;

        return $this;
    }

    public function getFechaReingresoAmbulatorio(): ?\DateTimeInterface
    {
        return $this->fechaReingresoAmbulatorio;
    }

    public function setFechaReingresoAmbulatorio(?\DateTimeInterface $fechaReingresoAmbulatorio): self
    {
        $this->fechaReingresoAmbulatorio = $fechaReingresoAmbulatorio;

        return $this;
    }

    /**
     * @return Collection|NotasHistoriaClinica[]
     */
    public function getNotasHistoriaClinica(): Collection
    {
        return $this->notasHistoriaClinica;
    }

    public function addNotasHistoriaClinica(NotasHistoriaClinica $notasHistoriaClinica): self
    {
        if (!$this->notasHistoriaClinica->contains($notasHistoriaClinica)) {
            $this->notasHistoriaClinica[] = $notasHistoriaClinica;
            $notasHistoriaClinica->setClientId($this);
        }

        return $this;
    }

    public function removeNotasHistoriaClinica(NotasHistoriaClinica $notasHistoriaClinica): self
    {
        if ($this->notasHistoriaClinica->contains($notasHistoriaClinica)) {
            $this->notasHistoriaClinica->removeElement($notasHistoriaClinica);
            // set the owning side to null (unless already changed)
            if ($notasHistoriaClinica->getClientId() === $this) {
                $notasHistoriaClinica->setClientId(null);
            }
        }

        return $this;
    }

    public function getHistoriaIngreso(): ?HistoriaIngreso
    {
        return $this->historiaIngreso;
    }

    public function setHistoriaIngreso(HistoriaIngreso $historiaIngreso): self
    {
        // set the owning side of the relation if necessary
        if ($historiaIngreso->getCliente() !== $this) {
            $historiaIngreso->setCliente($this);
        }

        $this->historiaIngreso = $historiaIngreso;

        return $this;
    }

    /**
     * @return Collection|HistoriaHabitaciones[]
     */
    public function getHistoriaHabitaciones(): Collection
    {
        return $this->historiaHabitaciones;
    }

    public function addHistoriaHabitacione(HistoriaHabitaciones $historiaHabitacione): self
    {
        if (!$this->historiaHabitaciones->contains($historiaHabitacione)) {
            $this->historiaHabitaciones[] = $historiaHabitacione;
            $historiaHabitacione->setCliente($this);
        }

        return $this;
    }

    public function removeHistoriaHabitacione(HistoriaHabitaciones $historiaHabitacione): self
    {
        if ($this->historiaHabitaciones->removeElement($historiaHabitacione)) {
            // set the owning side to null (unless already changed)
            if ($historiaHabitacione->getCliente() === $this) {
                $historiaHabitacione->setCliente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Evolucion[]
     */
    public function getEvolucions(): Collection
    {
        return $this->evolucions;
    }

    public function addEvolucion(Evolucion $evolucion): self
    {
        if (!$this->evolucions->contains($evolucion)) {
            $this->evolucions[] = $evolucion;
            $evolucion->setPaciente($this);
        }

        return $this;
    }

    public function removeEvolucion(Evolucion $evolucion): self
    {
        if ($this->evolucions->removeElement($evolucion)) {
            // set the owning side to null (unless already changed)
            if ($evolucion->getPaciente() === $this) {
                $evolucion->setPaciente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|HistoriaEgreso[]
     */
    public function getHistoriaEgresos(): Collection
    {
        return $this->historiaEgresos;
    }

    public function addHistoriaEgreso(HistoriaEgreso $historiaEgreso): self
    {
        if (!$this->historiaEgresos->contains($historiaEgreso)) {
            $this->historiaEgresos[] = $historiaEgreso;
            $historiaEgreso->setCliente($this);
        }

        return $this;
    }

    public function removeHistoriaEgreso(HistoriaEgreso $historiaEgreso): self
    {
        if ($this->historiaEgresos->removeElement($historiaEgreso)) {
            // set the owning side to null (unless already changed)
            if ($historiaEgreso->getCliente() === $this) {
                $historiaEgreso->setCliente(null);
            }
        }

        return $this;
    }

    public function getLastEvolution() {
        $evoluciones = $this->getEvolucions();

        $array = [];

        for ($i = count($evoluciones); $i > 1; $i --) {
            $evo = $evoluciones[$i - 1];
            if ($evo and !array_key_exists($evo->getTipo(), $array)) {
                $array[$evo->getTipo()] = $evo->getFecha()->format('Y-m-d');
            }

        }

        return $array;

    }

    /**
     * @return Collection|Prescripcion[]
     */
    public function getPrescripcion(): Collection
    {
        return $this->prescripcion;
    }

    public function addPrescripcion(Prescripcion $prescripcion): self
    {
        if (!$this->prescripcion->contains($prescripcion)) {
            $this->prescripcion[] = $prescripcion;
            $prescripcion->setCliente($this);
        }

        return $this;
    }

    public function removePrescripcion(Prescripcion $prescripcion): self
    {
        if ($this->prescripcion->removeElement($prescripcion)) {
            // set the owning side to null (unless already changed)
            if ($prescripcion->getCliente() === $this) {
                $prescripcion->setCliente(null);
            }
        }

        return $this;
    }

}
