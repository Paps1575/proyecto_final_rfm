<?php

namespace App\DataFixtures;

use App\Entity\Modulo;
use App\Entity\PermisoPerfil;
use App\Entity\Perfil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ModuloFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 1. Instancia del Módulo de Seguridad
        $modulo = new Modulo();
        $modulo->setStrNombre('Seguridad');
        $modulo->setStrRuta('/seguridad');
        $modulo->setIntEstado(1);
        $manager->persist($modulo);

        // 2. Recuperación del Perfil Administrador
        $perfilRepo = $manager->getRepository(Perfil::class);
        $perfilAdmin = $perfilRepo->findOneBy(['strNombrePerfil' => 'Administrador']);

        // 3. Creación de la relación de permisos si el perfil existe
        if ($perfilAdmin) {
            $permiso = new PermisoPerfil();
            $permiso->setPerfil($perfilAdmin);
            $permiso->setModulo($modulo);
            $permiso->setBoolConsultar(true);
            $permiso->setBoolAgregar(true);
            $permiso->setBoolEditar(true);
            $permiso->setBoolEliminar(true);

            $manager->persist($permiso);
        }

        $manager->flush();
    }

    /**
     * Establece el orden de ejecución de los fixtures.
     * ModuloFixture se ejecutará después de UsuarioFixture.
     */
    public function getDependencies(): array
    {
        return [
            UsuarioFixture::class,
        ];
    }
}
