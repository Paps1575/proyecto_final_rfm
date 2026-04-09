<?php

namespace App\Controller;

use App\Repository\ModuloRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemoController extends AbstractController
{
    #[Route('/vistas/demo/{id}', name: 'app_demo_modulo')]
    public function demo(int $id, ModuloRepository $moduloRepo): Response
    {
        $modulo = $moduloRepo->find($id);

        if (!$modulo) {
            throw $this->createNotFoundException('Modulo no encontrado');
        }

        return $this->render('demo/modulo_espejo.html.twig', [
            'modulo' => $modulo,
        ]);
    }
}
