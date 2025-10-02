<?php

namespace App\Entity;

use App\Repository\InformeMensualRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InformeMensualRepository::class)
 */
class InformeMensual
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $cliente;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $doctor;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaCreacion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lugar;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mesCorrespondiente;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $escaras;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoEscaras;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $requerimientosEspeciales;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $oxigeno;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $armBpap;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $traqueo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alimentacion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoAlimentacion;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $imcMayor40;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aislamientoContacto;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $derivacionesSegundoNivel;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $antecedentes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $estadoActual;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $pedidoMedico;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $evolucionMensual;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $medicacionActual;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $frecuenciaSesiones;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $estudiosComplementarios;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $proximasConsultas;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $observaciones;

    public function __construct()
    {
        $this->fechaCreacion = new \DateTime();
        $this->lugar = 'Funes';
        $this->mesCorrespondiente = $this->getMesEnEspanol(); // Mes actual en formato "Julio 2025"
    }

    private function getMesEnEspanol(): string
    {
        $mesesEspanol = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $mesNumero = (int)date('n');
        $año = date('Y');
        
        return $mesesEspanol[$mesNumero] . ' ' . $año;
    }

    private function convertirMesAEspanol(string $mesEnIngles): string
    {
        $traduccionMeses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];

        foreach ($traduccionMeses as $ingles => $español) {
            $mesEnIngles = str_replace($ingles, $español, $mesEnIngles);
        }

        return $mesEnIngles;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): self
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getDoctor(): ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor): self
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getFechaCreacion(): ?\DateTimeInterface
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTimeInterface $fechaCreacion): self
    {
        $this->fechaCreacion = $fechaCreacion;

        return $this;
    }

    public function getLugar(): ?string
    {
        return $this->lugar;
    }

    public function setLugar(string $lugar): self
    {
        $this->lugar = $lugar;

        return $this;
    }

    public function getMesCorrespondiente(): ?string
    {
        // Si el mes está en inglés, convertirlo a español
        if ($this->mesCorrespondiente) {
            return $this->convertirMesAEspanol($this->mesCorrespondiente);
        }
        return $this->mesCorrespondiente;
    }

    public function setMesCorrespondiente(?string $mesCorrespondiente): self
    {
        $this->mesCorrespondiente = $mesCorrespondiente;

        return $this;
    }

    public function getEscaras(): ?string
    {
        return $this->escaras;
    }

    public function setEscaras(?string $escaras): self
    {
        $this->escaras = $escaras;

        return $this;
    }

    public function getTipoEscaras(): ?string
    {
        return $this->tipoEscaras;
    }

    public function setTipoEscaras(?string $tipoEscaras): self
    {
        $this->tipoEscaras = $tipoEscaras;

        return $this;
    }

    public function getRequerimientosEspeciales(): ?string
    {
        return $this->requerimientosEspeciales;
    }

    public function setRequerimientosEspeciales(?string $requerimientosEspeciales): self
    {
        $this->requerimientosEspeciales = $requerimientosEspeciales;

        return $this;
    }

    public function getOxigeno(): ?string
    {
        return $this->oxigeno;
    }

    public function setOxigeno(?string $oxigeno): self
    {
        $this->oxigeno = $oxigeno;

        return $this;
    }

    public function getArmBpap(): ?string
    {
        return $this->armBpap;
    }

    public function setArmBpap(?string $armBpap): self
    {
        $this->armBpap = $armBpap;

        return $this;
    }

    public function getTraqueo(): ?string
    {
        return $this->traqueo;
    }

    public function setTraqueo(?string $traqueo): self
    {
        $this->traqueo = $traqueo;

        return $this;
    }

    public function getAlimentacion(): ?string
    {
        return $this->alimentacion;
    }

    public function setAlimentacion(?string $alimentacion): self
    {
        $this->alimentacion = $alimentacion;

        return $this;
    }

    public function getTipoAlimentacion(): ?string
    {
        return $this->tipoAlimentacion;
    }

    public function setTipoAlimentacion(?string $tipoAlimentacion): self
    {
        $this->tipoAlimentacion = $tipoAlimentacion;

        return $this;
    }

    public function isImcMayor40(): ?bool
    {
        return $this->imcMayor40;
    }

    public function setImcMayor40(?bool $imcMayor40): self
    {
        $this->imcMayor40 = $imcMayor40;

        return $this;
    }

    public function getAislamientoContacto(): ?string
    {
        return $this->aislamientoContacto;
    }

    public function setAislamientoContacto(?string $aislamientoContacto): self
    {
        $this->aislamientoContacto = $aislamientoContacto;

        return $this;
    }

    public function getDerivacionesSegundoNivel(): ?string
    {
        return $this->derivacionesSegundoNivel;
    }

    public function setDerivacionesSegundoNivel(?string $derivacionesSegundoNivel): self
    {
        $this->derivacionesSegundoNivel = $derivacionesSegundoNivel;

        return $this;
    }

    public function getAntecedentes(): ?string
    {
        return $this->antecedentes;
    }

    public function setAntecedentes(?string $antecedentes): self
    {
        $this->antecedentes = $antecedentes;

        return $this;
    }

    public function getEstadoActual(): ?string
    {
        return $this->estadoActual;
    }

    public function setEstadoActual(?string $estadoActual): self
    {
        $this->estadoActual = $estadoActual;

        return $this;
    }

    public function getPedidoMedico(): ?string
    {
        return $this->pedidoMedico;
    }

    public function setPedidoMedico(?string $pedidoMedico): self
    {
        $this->pedidoMedico = $pedidoMedico;

        return $this;
    }

    public function getEvolucionMensual(): ?string
    {
        return $this->evolucionMensual;
    }

    public function setEvolucionMensual(?string $evolucionMensual): self
    {
        $this->evolucionMensual = $evolucionMensual;

        return $this;
    }

    public function getMedicacionActual(): ?string
    {
        return $this->medicacionActual;
    }

    public function setMedicacionActual(?string $medicacionActual): self
    {
        $this->medicacionActual = $medicacionActual;

        return $this;
    }

    public function getFrecuenciaSesiones(): ?string
    {
        return $this->frecuenciaSesiones;
    }

    public function setFrecuenciaSesiones(?string $frecuenciaSesiones): self
    {
        $this->frecuenciaSesiones = $frecuenciaSesiones;

        return $this;
    }

    public function getEstudiosComplementarios(): ?string
    {
        return $this->estudiosComplementarios;
    }

    public function setEstudiosComplementarios(?string $estudiosComplementarios): self
    {
        $this->estudiosComplementarios = $estudiosComplementarios;

        return $this;
    }

    public function getProximasConsultas(): ?string
    {
        return $this->proximasConsultas;
    }

    public function setProximasConsultas(?string $proximasConsultas): self
    {
        $this->proximasConsultas = $proximasConsultas;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): self
    {
        $this->observaciones = $observaciones;

        return $this;
    }
}
