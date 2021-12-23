<?php

namespace App\Entity;

use App\Repository\EvolucionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Validator as AcmeAssert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=EvolucionRepository::class)
 */
class Evolucion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cliente::class, inversedBy="evolucions")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    private $paciente;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @AcmeAssert\EvolutionType
     */
    private $tipo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fecha;

    /**
     * @ORM\Column(type="text")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $adjunto_url;

    public function __construct()
    {
        $this->adjunto_url = array();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaciente(): ?Cliente
    {
        return $this->paciente;
    }

    public function setPaciente(?Cliente $paciente): self
    {
        $this->paciente = $paciente;

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

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAdjuntoUrl()
    {
        if ($this->adjunto_url === null) {
            $this->adjunto_url = array();
        }
        return $this->adjunto_url;
    }

    public function setAdjuntoUrl(?array $adjunto_url): self
    {
        $this->adjunto_url = $adjunto_url;

        return $this;
    }

    public function addAdjuntoUrl(String $path): self
    {
        if (!array_search($path, $this->adjunto_url)) {
            $this->adjunto_url[] = $path;
        }

        return $this;
    }

    public function removeAdjuntoUrl(String $nombre, String $path): self
    {
        $clave = array_search($nombre, $this->adjunto_url);
        if ($clave !== false) {
            unset($this->adjunto_url[$clave]);
            $filePath = $path . $nombre;
            $filesystem = new Filesystem();
            if ($filesystem->exists($filePath)) {
                $filesystem->remove($filePath);
            }
        }

        return $this;
    }
}
