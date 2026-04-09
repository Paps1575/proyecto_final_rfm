<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // TUNEADO: Si el usuario ya inició sesión, no tiene sentido que vea el login.
        // Lo mandamos directo a la "puerta de entrada" del sistema.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Obtener el error de inicio de sesión si existe
        $error = $authenticationUtils->getLastAuthenticationError();

        // Último nombre de usuario introducido (para que no tenga que escribirlo de nuevo si falló la clave)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            // Opcional: pasar una variable para breadcrumbs si tu login los usa
            'breadcrumbs' => [
                ['label' => 'Inicio', 'path' => 'app_login'],
                ['label' => 'Acceso al Sistema', 'path' => null],
            ],
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Este método nunca se ejecuta realmente.
        // Symfony intercepta esta ruta y cierra la sesión automáticamente
        // según lo configurado en security.yaml
        throw new \LogicException('Este método puede estar vacío; será interceptado por la clave de logout en tu firewall.');
    }
}
