<?php

namespace App\Entity;

use App\Repository\ConsumiblesClientesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsumiblesClientesRepository::class)
 */
class ConsumiblesClientes
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $consumibleId;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $clienteId;

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    private $fecha;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $mes;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $cantidad;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $accion;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notas;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $year;
    
    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     */
    private $activo = true;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"comment"="Tipo de indicación: medicamento, procedimiento, control"})
     */
    private $tipoIndicacion;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"comment"="Frecuencia de administración o realización"})
     */
    private $frecuencia;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"comment"="Duración del tratamiento o control"})
     */
    private $duracion;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"comment"="Vía de administración (para medicamentos)"})
     */
    private $viaAdministracion;
    
    /**
     * @ORM\Column(type="string", length=20, nullable=true, options={"comment"="Unidad de medida (g, ml, cc, etc)"})
     */
    private $unidadMedida;
    
    /**
     * @ORM\Column(type="date", nullable=true, options={"comment"="Fecha de inicio de la indicación"})
     */
    private $fechaInicio;
    
    /**
     * @ORM\Column(type="date", nullable=true, options={"comment"="Fecha de fin de la indicación (null para indefinidas)"})
     */
    private $fechaFin;
    
    /**
     * @ORM\Column(type="text", nullable=true, options={"comment"="Descripción personalizada para procedimientos o controles"})
     */
    private $procedimientoPersonalizado;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getConsumibleId()
    {
        return $this->consumibleId;
    }

    /**
     * @param mixed $consumibleId
     */
    public function setConsumibleId($consumibleId): void
    {
        $this->consumibleId = $consumibleId;
    }

    /**
     * @return mixed
     */
    public function getClienteId()
    {
        return $this->clienteId;
    }

    /**
     * @param mixed $clienteId
     */
    public function setClienteId($clienteId): void
    {
        $this->clienteId = $clienteId;
    }

    /**
     * @return mixed
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /**
     * @param mixed $fecha
     */
    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    /**
     * @return mixed
     */
    public function getCantidad()
    {
        return $this->cantidad;
    }

    /**
     * @param mixed $cantidad
     */
    public function setCantidad($cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    /**
     * @return mixed
     */
    public function getAccion()
    {
        return $this->accion;
    }

    /**
     * @param mixed $accion
     */
    public function setAccion($accion): void
    {
        $this->accion = $accion;
    }

    /**
     * @return mixed
     */
    public function getMes()
    {
        return $this->mes;
    }

    /**
     * @param mixed $desde
     */
    public function setMes($mes): void
    {
        $this->mes = $mes;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;
        
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getNotas(): ?string
    {
        return $this->notas;
    }
    
    /**
     * @param string|null $notas
     * @return $this
     */
    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActivo(): bool
    {
        return $this->activo;
    }

    /**
     * @param bool $activo
     * @return $this
     */
    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;

        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getTipoIndicacion(): ?string
    {
        return $this->tipoIndicacion;
    }
    
    /**
     * @param string|null $tipoIndicacion
     * @return $this
     */
    public function setTipoIndicacion(?string $tipoIndicacion): self
    {
        $this->tipoIndicacion = $tipoIndicacion;
        
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getFrecuencia(): ?string
    {
        return $this->frecuencia;
    }
    
    /**
     * @param string|null $frecuencia
     * @return $this
     */
    public function setFrecuencia(?string $frecuencia): self
    {
        $this->frecuencia = $frecuencia;
        
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getDuracion(): ?string
    {
        return $this->duracion;
    }
    
    /**
     * @param string|null $duracion
     * @return $this
     */
    public function setDuracion(?string $duracion): self
    {
        $this->duracion = $duracion;
        
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getViaAdministracion(): ?string
    {
        return $this->viaAdministracion;
    }
    
    /**
     * @param string|null $viaAdministracion
     * @return $this
     */
    public function setViaAdministracion(?string $viaAdministracion): self
    {
        $this->viaAdministracion = $viaAdministracion;

        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getUnidadMedida(): ?string
    {
        return $this->unidadMedida;
    }
    
    /**
     * @param string|null $unidadMedida
     * @return $this
     */
    public function setUnidadMedida(?string $unidadMedida): self
    {
        $this->unidadMedida = $unidadMedida;
        
        return $this;
    }
    
    /**
     * @return \DateTime|null
     */
    public function getFechaInicio(): ?\DateTime
    {
        return $this->fechaInicio;
    }
    
    /**
     * @param \DateTime|null $fechaInicio
     * @return $this
     */
    public function setFechaInicio(?\DateTime $fechaInicio): self
    {
        $this->fechaInicio = $fechaInicio;
        
        return $this;
    }
    
    /**
     * @return \DateTime|null
     */
    public function getFechaFin(): ?\DateTime
    {
        return $this->fechaFin;
    }
    
    /**
     * @param \DateTime|null $fechaFin
     * @return $this
     */
    public function setFechaFin(?\DateTime $fechaFin): self
    {
        $this->fechaFin = $fechaFin;
        
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getProcedimientoPersonalizado(): ?string
    {
        return $this->procedimientoPersonalizado;
    }
    
    /**
     * @param string|null $procedimientoPersonalizado
     * @return $this
     */
    public function setProcedimientoPersonalizado(?string $procedimientoPersonalizado): self
    {
        $this->procedimientoPersonalizado = $procedimientoPersonalizado;
        
        return $this;
    }
}
