<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use App\Repository\PerfilRepository;
use App\Repository\ModuloRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    /**
     * El "Cerebro" del Dashboard de Rogelio Funes Mori Productions
     */
    #[Route('/', name: 'app_dashboard', methods: ['GET'])] // Agregamos el método GET explícito
    public function index(
        UsuarioRepository $usuarioRepo,
        PerfilRepository $perfilRepo,
        ModuloRepository $moduloRepo
    ): Response {

        // 1. SEGURIDAD: Si no hay nadie logueado, Symfony lo manda al login automáticamente
        // si tienes bien configurado el security.yaml. Si no, esto lo fuerza:
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Mandamos toda la "carne al asador" a la vista de Twig
        return $this->render('dashboard/index.html.twig', [
            'usuarios' => $usuarioRepo->findAll(),
            'perfiles' => $perfilRepo->findAll(),
            'modulos'  => $moduloRepo->findAll(),
            'controller_name' => 'DashboardController',
        ]);
    }
}
