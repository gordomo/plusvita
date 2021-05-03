<?php

namespace App\Entity;

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
     * @ORM\Column(type="date", nullable=true)
     */
    private $desde;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $hasta;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $cantidad;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $accion;

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
    public function getDesde()
    {
        return $this->desde;
    }

    /**
     * @param mixed $desde
     */
    public function setDesde($desde): void
    {
        $this->desde = $desde;
    }

    /**
     * @return mixed
     */
    public function getHasta()
    {
        return $this->hasta;
    }

    /**
     * @param mixed $hasta
     */
    public function setHasta($hasta): void
    {
        $this->hasta = $hasta;
    }
}
