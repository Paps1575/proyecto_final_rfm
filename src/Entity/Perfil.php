<?php

namespace App\Entity;

use App\Repository\PerfilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PerfilRepository::class)]
// TUNEADO: Validación para que no repitan nombres de perfiles
#[UniqueEntity(fields: ['strNombrePerfil'], message: 'Este nombre de perfil ya existe, compa.')]
class Perfil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    // TUNEADO: El nombre no puede ir vacío
    #[Assert\NotBlank(message: 'El nombre del perfil es obligatorio.')]
    private ?string $strNombrePerfil = null;

    #[ORM\Column]
    private ?bool $bitAdministrador = null;

    /**
     * @var Collection<int, Usuario>
     */
    #[ORM\OneToMany(targetEntity: Usuario::class, mappedBy: 'perfil')]
    private Collection $usuarios;

    /**
     * TUNEADO: Relación con permisos. Agregamos cascade para que al borrar
     * un perfil se borren sus permisos automáticamente.
     * @var Collection<int, PermisoPerfil>
     */
    #[ORM\OneToMany(targetEntity: PermisoPerfil::class, mappedBy: 'perfil', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $permisoPerfils;

    public function __construct()
    {
        $this->usuarios = new ArrayCollection();
        $this->permisoPerfils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->strNombrePerfil ?? 'Nuevo Perfil';
    }

    public function getStrNombrePerfil(): ?string
    {
        return $this->strNombrePerfil;
    }

    public function setStrNombrePerfil(string $strNombrePerfil): static
    {
        $this->strNombrePerfil = $strNombrePerfil;
        return $this;
    }

    public function isBitAdministrador(): ?bool
    {
        return $this->bitAdministrador;
    }

    public function setBitAdministrador(bool $bitAdministrador): static
    {
        $this->bitAdministrador = $bitAdministrador;
        return $this;
    }

    /**
     * @return Collection<int, Usuario>
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
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
            $permisoPerfil->setPerfil($this);
        }
        return $this;
    }

    public function removePermisoPerfil(PermisoPerfil $permisoPerfil): static
    {
        if ($this->permisoPerfils->removeElement($permisoPerfil)) {
            if ($permisoPerfil->getPerfil() === $this) {
                $permisoPerfil->setPerfil(null);
            }
        }
        return $this;
    }
}
