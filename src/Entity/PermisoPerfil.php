<?php

namespace App\Entity;

use App\Repository\PermisoPerfilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermisoPerfilRepository::class)]
class PermisoPerfil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'permisoPerfils')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Perfil $perfil = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Modulo $modulo = null;

    #[ORM\Column]
    private ?bool $boolConsultar = false;

    #[ORM\Column]
    private ?bool $boolAgregar = false;

    #[ORM\Column]
    private ?bool $boolEditar = false;

    #[ORM\Column]
    private ?bool $boolEliminar = false;

    public function getId(): ?int { return $this->id; }

    public function getPerfil(): ?Perfil { return $this->perfil; }

    public function setPerfil(?Perfil $perfil): static
    {
        $this->perfil = $perfil;
        return $this;
    }

    public function getModulo(): ?Modulo { return $this->modulo; }

    public function setModulo(?Modulo $modulo): static
    {
        $this->modulo = $modulo;
        return $this;
    }

    public function isBoolConsultar(): ?bool { return $this->boolConsultar; }
    public function setBoolConsultar(bool $boolConsultar): static { $this->boolConsultar = $boolConsultar; return $this; }

    public function isBoolAgregar(): ?bool { return $this->boolAgregar; }
    public function setBoolAgregar(bool $boolAgregar): static { $this->boolAgregar = $boolAgregar; return $this; }

    public function isBoolEditar(): ?bool { return $this->boolEditar; }
    public function setBoolEditar(bool $boolEditar): static { $this->boolEditar = $boolEditar; return $this; }

    public function isBoolEliminar(): ?bool { return $this->boolEliminar; }
    public function setBoolEliminar(bool $boolEliminar): static { $this->boolEliminar = $boolEliminar; return $this; }
}
