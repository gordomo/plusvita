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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $year;

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
}
