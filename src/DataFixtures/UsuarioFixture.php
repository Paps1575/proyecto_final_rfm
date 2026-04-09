<?php

namespace App\DataFixtures;

use App\Entity\Perfil;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsuarioFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    // Inyectamos el servicio para encriptar
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Creamos el Perfil de Administrador
        $perfil = new Perfil();
        $perfil->setStrNombrePerfil('Administrador');
        $perfil->setBitAdministrador(true);
        $manager->persist($perfil);

        // 2. Creamos a Parito (Tu usuario de deidad)
        $usuario = new Usuario();
        $usuario->setStrNombreUsuario('parito_admin');
        $usuario->setPerfil($perfil);
        $usuario->setStrCorreo('admin@proyecto.com');
        $usuario->setStrNumeroCelular('1234567890');
        $usuario->setFoto('default.jpg');
        $usuario->setIdEstadoUsuario(true);
        $usuario->setRoles(['ROLE_ADMIN']);

        // AQUÍ ESTÁ EL TRUCO: Encriptamos el password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $usuario,
            'password123'
        );
        $usuario->setStrPwd($hashedPassword);

        $manager->persist($usuario);
        $manager->flush(); // ¡A Railway!
    }
}
