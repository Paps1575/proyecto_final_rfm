<?php

namespace App\Entity;

use App\Repository\ModuloRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuloRepository::class)]
class Modulo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $strNombre = null;

    #[ORM\Column(length: 255)]
    private ?string $strRuta = null;

    #[ORM\Column]
    private ?int $intEstado = 1; // Default 1 (Activo) para que no falle al crear

    /**
     * @var Collection<int, PermisoPerfil>
     * * TUNEADO: Se agregó cascade remove y orphanRemoval para limpieza automática.
     */
    #[ORM\OneToMany(
        targetEntity: PermisoPerfil::class,
        mappedBy: 'modulo',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $permisoPerfils;

    public function __construct()
    {
        $this->permisoPerfils = new ArrayCollection();
        $this->intEstado = 1; // Aseguramos estado activo al nacer
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStrNombre(): ?string
    {
        return $this->strNombre;
    }

    public function setStrNombre(string $strNombre): static
    {
        $this->strNombre = $strNombre;
        return $this;
    }

    public function getStrRuta(): ?string
    {
        return $this->strRuta;
    }

    public function setStrRuta(string $strRuta): static
    {
        $this->strRuta = $strRuta;
        return $this;
    }

    public function getIntEstado(): ?int
    {
        return $this->intEstado;
    }

    public function setIntEstado(int $intEstado): static
    {
        $this->intEstado = $intEstado;
        return $this;
    }

    /**
     * @return Collection<int, PermisoPerfil>
     */
    public function getPermisoPerfils(): Collection
    {
        return $this->permisoPerfils;
    }

    public function addPermisoPerfil(PermisoPerfil $permisoPerfil): static
    {
        if (!$this->permisoPerfils->contains($permisoPerfil)) {
            $this->permisoPerfils->add($permisoPerfil);
            $permisoPerfil->setModulo($this);
        }
        return $this;
    }

    public function removePermisoPerfil(PermisoPerfil $permisoPerfil): static
    {
        if ($this->permisoPerfils->removeElement($permisoPerfil)) {
            // set the owning side to null (unless already changed)
            if ($permisoPerfil->getModulo() === $this) {
                $permisoPerfil->setModulo(null);
            }
        }
        return $this;
    }

    /**
     * Agregamos __toString para evitar errores en formularios o selecciones
     */
    public function __toString(): string
    {
        return (string) $this->strNombre;
    }
}
