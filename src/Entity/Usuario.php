<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[UniqueEntity(fields: ['strNombreUsuario'], message: 'El nombre de usuario ya existe.')]
#[UniqueEntity(fields: ['strCorreo'], message: 'El correo electrónico ya está registrado.')]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'El nombre de usuario es obligatorio.')]
    #[Assert\Length(min: 4, minMessage: 'El nombre de usuario debe tener al menos 4 caracteres.')]
    private ?string $strNombreUsuario = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\ManyToOne(inversedBy: 'usuarios')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Debe asignar un perfil de acceso.')]
    private ?Perfil $perfil = null;

    #[ORM\Column(name: "str_pwd", length: 255)]
    private ?string $strPwd = null;

    #[ORM\Column]
    private ?bool $idEstadoUsuario = true;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'El correo electrónico es obligatorio.')]
    #[Assert\Email(message: 'Formato de correo electrónico no válido.')]
    private ?string $strCorreo = null;

    #[ORM\Column(length: 15)]
    #[Assert\NotBlank(message: 'El número telefónico es obligatorio.')]
    #[Assert\Regex(pattern: '/^[0-9]{10}$/', message: 'El teléfono debe contener exactamente 10 dígitos numéricos.')]
    private ?string $strNumeroCelular = null;

    /**
     * CORRECCIÓN: Quitamos los Asserts de aquí para evitar el error "File not found".
     * La validación se hará ahora exclusivamente en el FormType.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = 'default.png';

    public function getId(): ?int { return $this->id; }
    public function getUserIdentifier(): string { return (string) $this->strNombreUsuario; }
    public function getRoles(): array {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): ?string { return $this->strPwd; }
    public function setPassword(string $password): static { $this->strPwd = $password; return $this; }
    public function getStrPwd(): ?string { return $this->strPwd; }
    public function setStrPwd(?string $strPwd): static { $this->strPwd = $strPwd; return $this; }
    public function eraseCredentials(): void {}

    public function getStrNombreUsuario(): ?string { return $this->strNombreUsuario; }
    public function setStrNombreUsuario(string $u): static { $this->strNombreUsuario = $u; return $this; }
    public function getPerfil(): ?Perfil { return $this->perfil; }
    public function setPerfil(?Perfil $p): static { $this->perfil = $p; return $this; }
    public function isIdEstadoUsuario(): ?bool { return $this->idEstadoUsuario; }
    public function setIdEstadoUsuario(bool $s): static { $this->idEstadoUsuario = $s; return $this; }
    public function getStrCorreo(): ?string { return $this->strCorreo; }
    public function setStrCorreo(string $c): static { $this->strCorreo = $c; return $this; }
    public function getStrNumeroCelular(): ?string { return $this->strNumeroCelular; }
    public function setStrNumeroCelular(string $t): static { $this->strNumeroCelular = $t; return $this; }
    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $f): static { $this->foto = $f; return $this; }
}
