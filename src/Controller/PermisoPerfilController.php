<?php

namespace App\Controller;

use App\Entity\Perfil;
use App\Entity\PermisoPerfil;
use App\Repository\PerfilRepository;
use App\Repository\PermisoPerfilRepository;
use App\Security\Voter\ModuloVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vistas/permisos')]
#[IsGranted('ROLE_USER')]
class PermisoPerfilController extends AbstractController
{
    /**
     * VISTA PRINCIPAL
     */
    #[Route('', name: 'app_permiso_perfil_index', methods: ['GET'])]
    public function index(PerfilRepository $perfilRepo): Response
    {
        if (!$this->isGranted(ModuloVoter::CONSULTAR, 'PERMISOS PERFIL')) {
            $this->addFlash('warning', 'Acceso restringido a la configuración de seguridad.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('permiso_perfil/index.html.twig', [
            'perfiles' => $perfilRepo->findAll(),
        ]);
    }

    /**
     * CARGA AJAX DINÁMICA
     */
    #[Route('/cargar/{id}', name: 'app_permiso_perfil_cargar', methods: ['GET'])]
    public function cargar(Perfil $perfil): JsonResponse
    {
        if (!$this->isGranted(ModuloVoter::CONSULTAR, 'PERMISOS PERFIL')) {
            return new JsonResponse(['error' => 'No autorizado'], 403);
        }

        $datos = [];
        foreach ($perfil->getPermisoPerfils() as $p) {
            $modulo = $p->getModulo();
            if (!$modulo) continue;

            $datos[] = [
                'id' => $p->getId(),
                'modulo' => strtoupper(trim($modulo->getStrNombre())),
                'agregar' => (bool)$p->isBoolAgregar(),
                'editar' => (bool)$p->isBoolEditar(),
                'eliminar' => (bool)$p->isBoolEliminar(),
                'consultar' => (bool)$p->isBoolConsultar(),
            ];
        }

        usort($datos, fn($a, $b) => strcmp($a['modulo'], $b['modulo']));
        return new JsonResponse($datos);
    }

    /**
     * ACTUALIZACIÓN MASIVA (Corregida para detectar desmarcados)
     */
    #[Route('/actualizar-masivo', name: 'app_permiso_perfil_bulk_update', methods: ['POST'])]
    public function bulkUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        PermisoPerfilRepository $permisoRepo,
        PerfilRepository $perfilRepo
    ): Response {

        if (!$this->isGranted(ModuloVoter::EDITAR, 'PERMISOS PERFIL')) {
            $this->addFlash('danger', 'Solo el Administrador puede modificar estos permisos.');
            return $this->redirectToRoute('app_permiso_perfil_index');
        }

        if (!$this->isCsrfTokenValid('permisos_bulk_update', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de seguridad inválido.');
            return $this->redirectToRoute('app_perfil_index');
        }

        // Recuperamos el ID del perfil que estamos editando
        $perfilId = $request->request->get('perfil_id');
        $datosPermisos = $request->request->all('permisos'); // Lo que mandó el form

        if ($perfilId) {
            // 🛡️ LÓGICA DE ACTUALIZACIÓN SEGURA:
            // Obtenemos todos los permisos actuales de este perfil en la BD
            $permisosActuales = $permisoRepo->findBy(['perfil' => $perfilId]);

            foreach ($permisosActuales as $permiso) {
                $id = $permiso->getId();

                // Si el ID viene en el POST, significa que al menos un check está activo en esa fila
                if (isset($datosPermisos[$id])) {
                    $valores = $datosPermisos[$id];
                    $permiso->setBoolAgregar(isset($valores['boolAgregar']));
                    $permiso->setBoolEditar(isset($valores['boolEditar']));
                    $permiso->setBoolEliminar(isset($valores['boolEliminar']));
                    $permiso->setBoolConsultar(isset($valores['boolConsultar']));
                } else {
                    // 🚨 IMPORTANTE: Si el ID no viene, es porque el usuario desmarcó
                    // los 4 checks de esa fila. ¡Hay que ponerlos en false!
                    $permiso->setBoolAgregar(false);
                    $permiso->setBoolEditar(false);
                    $permiso->setBoolEliminar(false);
                    $permiso->setBoolConsultar(false);
                }
            }

            $entityManager->flush(); // 💎 ¡AQUÍ SE ESCRIBE EN RAILWAY!
            $this->addFlash('success', '¡Matriz actualizada con éxito!');
        } else {
            $this->addFlash('warning', 'No se especificó un perfil válido.');
        }

        return $this->redirectToRoute('app_permiso_perfil_index');
    }
}
