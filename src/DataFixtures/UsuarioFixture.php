<?php

namespace App\DataFixtures;

use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Entity\CatEstadoUsuario; // Asegúrate de que esta sea la clase de tu entidad de estados
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsuarioFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Creamos el Estado ACTIVO (Fundamental para que el UserChecker lo deje pasar)
        // Si ya tienes estados en tu DB, puedes omitir esto y buscarlo,
        // pero para Fixtures limpias, lo creamos de una:
        $estadoActivo = new CatEstadoUsuario();
        $estadoActivo->setStrNombreEstado('ACTIVO'); // Ajusta según tus campos
        $manager->persist($estadoActivo);

        // 2. Creamos el Perfil de Administrador
        $perfil = new Perfil();
        $perfil->setStrNombrePerfil('Administrador');
        $perfil->setBitAdministrador(true);
        $manager->persist($perfil);

        // 3. Creamos al Administrador (Tu usuario de deidad)
        $usuario = new Usuario();
        $usuario->setStrNombreUsuario('parito_admin');
        $usuario->setPerfil($perfil);
        $usuario->setStrCorreo('admin@proyecto.com');
        $usuario->setStrNumeroCelular('5512345678');
        $usuario->setFoto('default.png');

        // LE ASIGNAMOS EL OBJETO DE ESTADO (Para que isActivo() sea true)
        $usuario->setIdEstadoUsuario($estadoActivo);

        $usuario->setRoles(['ROLE_ADMIN']);

        // Encriptamos el password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $usuario,
            'admin123' // Cámbiala por una más pro luego, bro
        );
        $usuario->setStrPwd($hashedPassword);

        $manager->persist($usuario);

        // 4. ¡A la de carga!
        $manager->flush();
    }
}
