<?php

namespace App\Entity;

use App\Repository\HistoriaIngresoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @ORM\Entity(repositoryClass=HistoriaIngresoRepository::class)
 */
class HistoriaIngreso
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Cliente::class, inversedBy="historiaIngreso", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $cliente;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $antecedentesTexto;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $enfermedadActual;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $examenFisicoAlIngreso;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $examenComplementarioDesc;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $indicaciones;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $examenesComplementeriosFiles;

    public function __construct()
    {
        $this->examenesComplementeriosFiles = array();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(Cliente $cliente): self
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getAntecedentesTexto(): ?string
    {
        return $this->antecedentesTexto;
    }

    public function setAntecedentesTexto(?string $antecedentesTexto): self
    {
        $this->antecedentesTexto = $antecedentesTexto;

        return $this;
    }

    public function getEnfermedadActual(): ?string
    {
        return $this->enfermedadActual;
    }

    public function setEnfermedadActual(?string $enfermedadActual): self
    {
        $this->enfermedadActual = $enfermedadActual;

        return $this;
    }

    public function getExamenFisicoAlIngreso(): ?string
    {
        return $this->examenFisicoAlIngreso;
    }

    public function setExamenFisicoAlIngreso(?string $examenFisicoAlIngreso): self
    {
        $this->examenFisicoAlIngreso = $examenFisicoAlIngreso;

        return $this;
    }

    public function getExamenComplementarioDesc(): ?string
    {
        return $this->examenComplementarioDesc;
    }

    public function setExamenComplementarioDesc(?string $examenComplementarioDesc): self
    {
        $this->examenComplementarioDesc = $examenComplementarioDesc;

        return $this;
    }

    public function getIndicaciones(): ?string
    {
        return $this->indicaciones;
    }

    public function setIndicaciones(?string $indicaciones): self
    {
        $this->indicaciones = $indicaciones;

        return $this;
    }

    public function getExamenesComplementeriosFiles()
    {
        if ($this->examenesComplementeriosFiles === null) {
            $this->examenesComplementeriosFiles = array();
        }
        return $this->examenesComplementeriosFiles;
    }

    public function setExamenesComplementeriosFiles(?array $examenesComplementeriosFiles): self
    {
        $this->examenesComplementeriosFiles = $examenesComplementeriosFiles;

        return $this;
    }

    public function addExamenesComplementeriosFiles(String $path): self
    {
        if (!array_search($path, $this->examenesComplementeriosFiles)) {
            $this->examenesComplementeriosFiles[] = $path;
        }

        return $this;
    }

    public function removeExamenesComplementeriosFiles(String $nombre, String $path): self
    {
        $clave = array_search($nombre, $this->examenesComplementeriosFiles);
        if ($clave !== false) {
            unset($this->examenesComplementeriosFiles[$clave]);
            $filePath = $path . $nombre;
            $filesystem = new Filesystem();
            if ($filesystem->exists($filePath)) {
                $filesystem->remove($filePath);
            }
        }

        return $this;
    }
}
