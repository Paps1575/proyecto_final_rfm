<?php

namespace App\Controller;

use App\Entity\Modulo;
use App\Entity\PermisoPerfil;
use App\Form\ModuloType;
use App\Repository\ModuloRepository;
use App\Repository\PerfilRepository;
use App\Security\Voter\ModuloVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vistas/modulos')]
#[IsGranted('ROLE_USER')]
final class ModuloController extends AbstractController
{
    /**
     * LISTADO DE MÓDULOS (Paginación 5x5)
     */
    #[Route('/', name: 'app_modulo_index', methods: ['GET'])]
    public function index(ModuloRepository $moduloRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted(ModuloVoter::CONSULTAR, 'Modulos');

        $limit = 5;
        $page = $request->query->getInt('page', 1);
        if ($page < 1) $page = 1;

        $totalModulos = $moduloRepository->count([]);
        $pagesCount = ceil($totalModulos / $limit);

        if ($page > $pagesCount && $pagesCount > 0) $page = $pagesCount;

        $modulos = $moduloRepository->findBy([], ['id' => 'ASC'], $limit, ($page - 1) * $limit);

        return $this->render('modulo/index.html.twig', [
            'modulos' => $modulos,
            'currentPage' => $page,
            'pagesCount' => $pagesCount,
            'totalModulos' => $totalModulos
        ]);
    }

    /**
     * NUEVO MÓDULO + GENERACIÓN AUTOMÁTICA DE DATOS
     */
    #[Route('/nuevo', name: 'app_modulo_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PerfilRepository $perfilRepository
    ): Response {
        $this->denyAccessUnlessGranted(ModuloVoter::AGREGAR, 'Modulos');

        $modulo = new Modulo();
        $form = $this->createForm(ModuloType::class, $modulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --- CORRECCIÓN CRÍTICA PARA EVITAR EL ERROR 500 ---
            // Si no viene la ruta (porque la ocultamos en el Twig), la generamos proactivamente
            if (!$modulo->getStrRuta()) {
                $rawName = $modulo->getStrNombre();
                // Limpiamos el nombre para que sea una ruta válida (ej: "Módulos Extra" -> "app_modulos_extra_index")
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $rawName)));
                $modulo->setStrRuta('app_' . $slug . '_index');
            }

            // Si el estado no está definido, por defecto es 1 (Activo)
            if ($modulo->getIntEstado() === null) {
                $modulo->setIntEstado(1);
            }

            $entityManager->persist($modulo);

            // Sincronización automática de permisos con todos los perfiles
            $perfiles = $perfilRepository->findAll();
            foreach ($perfiles as $perfil) {
                $permiso = new PermisoPerfil();
                $permiso->setPerfil($perfil);
                $permiso->setModulo($modulo);
                $permiso->setBoolConsultar(false);
                $permiso->setBoolAgregar(false);
                $permiso->setBoolEditar(false);
                $permiso->setBoolEliminar(false);
                $entityManager->persist($permiso);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Módulo "' . strtoupper($modulo->getStrNombre()) . '" creado y sincronizado.');
            return $this->redirectToRoute('app_modulo_index');
        }

        return $this->render('modulo/new.html.twig', [
            'modulo' => $modulo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * EDITAR
     */
    #[Route('/{id}/edit', name: 'app_modulo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Modulo $modulo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(ModuloVoter::EDITAR, 'Modulos');

        $form = $this->createForm(ModuloType::class, $modulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('info', 'Módulo actualizado correctamente.');
            return $this->redirectToRoute('app_modulo_index');
        }

        return $this->render('modulo/edit.html.twig', [
            'modulo' => $modulo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * ELIMINAR
     */
    #[Route('/{id}', name: 'app_modulo_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Modulo $modulo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(ModuloVoter::ELIMINAR, 'Modulos');

        if ($this->isCsrfTokenValid('delete'.$modulo->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($modulo);
                $entityManager->flush();
                $this->addFlash('warning', 'Módulo eliminado.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'No se puede eliminar: el módulo tiene datos asociados.');
            }
        }

        return $this->redirectToRoute('app_modulo_index');
    }
}
