<?php

namespace App\Controller;

use App\Entity\Perfil;
use App\Entity\PermisoPerfil;
use App\Form\PerfilType;
use App\Repository\ModuloRepository;
use App\Repository\PerfilRepository;
use App\Security\Voter\ModuloVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vistas/perfil')]
#[IsGranted('ROLE_USER')]
final class PerfilController extends AbstractController
{
    /**
     * Recupera el nombre real del módulo desde la BD para el Voter.
     */
    private function getNombreModulo(ModuloRepository $moduloRepo): string
    {
        $modulo = $moduloRepo->findOneBy(['strRuta' => 'app_perfil_index'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'PERFILES'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'PERFIL']);

        return $modulo ? $modulo->getStrNombre() : 'PERFILES';
    }

    /**
     * LISTADO DE PERFILES
     */
    #[Route('', name: 'app_perfil_index', methods: ['GET'])]
    public function index(PerfilRepository $perfilRepository, ModuloRepository $moduloRepository): Response
    {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::CONSULTAR, $nombreMod)) {
            $this->addFlash('warning', 'No tienes permiso para consultar el catálogo de perfiles.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('perfil/index.html.twig', [
            'perfiles' => $perfilRepository->findAll(),
            'nombreModulo' => $nombreMod,
        ]);
    }

    /**
     * CREAR NUEVO PERFIL
     */
    #[Route('/new', name: 'app_perfil_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::AGREGAR, $nombreMod)) {
            $this->addFlash('danger', 'No tienes autorización para crear nuevos perfiles.');
            return $this->redirectToRoute('app_perfil_index');
        }

        $perfil = new Perfil();
        $form = $this->createForm(PerfilType::class, $perfil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($perfil);

            // Inicializamos la matriz de permisos para el nuevo perfil en FALSE
            $modulos = $moduloRepository->findAll();
            foreach ($modulos as $modulo) {
                $permiso = new PermisoPerfil();
                $permiso->setModulo($modulo);
                $permiso->setPerfil($perfil);
                $permiso->setBoolConsultar(false);
                $permiso->setBoolAgregar(false);
                $permiso->setBoolEditar(false);
                $permiso->setBoolEliminar(false);
                $entityManager->persist($permiso);
            }

            $entityManager->flush();
            $this->addFlash('success', '¡Perfil creado y matriz inicializada!');
            return $this->redirectToRoute('app_perfil_index');
        }

        return $this->render('perfil/new.html.twig', [
            'perfil' => $perfil,
            'form' => $form,
        ]);
    }

    /**
     * EDITAR PERFIL
     */
    #[Route('/{id}/edit', name: 'app_perfil_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Perfil $perfil,
        EntityManagerInterface $entityManager,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        // 🛡️ PROTECCIÓN DE JERARQUÍA: Solo un Admin real toca perfiles Admin.
        $miPerfil = $this->getUser()->getPerfil();
        if ($perfil->isBitAdministrador() && !$miPerfil->isBitAdministrador()) {
            $this->addFlash('danger', 'Solo un Administrador puede editar perfiles de alto rango.');
            return $this->redirectToRoute('app_perfil_index');
        }

        if (!$this->isGranted(ModuloVoter::EDITAR, $nombreMod)) {
            $this->addFlash('danger', 'No tienes permiso para editar perfiles.');
            return $this->redirectToRoute('app_perfil_index');
        }

        $this->repararMatriz($perfil, $moduloRepository, $entityManager);

        $form = $this->createForm(PerfilType::class, $perfil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('info', 'Perfil actualizado correctamente.');
            return $this->redirectToRoute('app_perfil_index');
        }

        return $this->render('perfil/edit.html.twig', [
            'perfil' => $perfil,
            'form' => $form,
        ]);
    }

    /**
     * BORRADO SEGURO
     */
    #[Route('/{id}', name: 'app_perfil_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Perfil $perfil,
        EntityManagerInterface $entityManager,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::ELIMINAR, $nombreMod)) {
            $this->addFlash('danger', 'No tienes permiso para eliminar perfiles.');
            return $this->redirectToRoute('app_perfil_index');
        }

        // Blindaje contra borrado accidental del Admin Raíz (ID 7) o el propio perfil
        if ($perfil->getId() === 7 || $perfil === $this->getUser()->getPerfil()) {
            $this->addFlash('danger', 'Acción denegada: El perfil raíz o tu propio perfil están protegidos.');
            return $this->redirectToRoute('app_perfil_index');
        }

        if ($this->isCsrfTokenValid('delete'.$perfil->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($perfil);
                $entityManager->flush();
                $this->addFlash('warning', 'Perfil eliminado exitosamente.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: No se puede eliminar un perfil con usuarios vinculados.');
            }
        }

        return $this->redirectToRoute('app_perfil_index');
    }

    private function repararMatriz(Perfil $perfil, ModuloRepository $moduloRepo, EntityManagerInterface $em): void
    {
        $todosLosModulos = $moduloRepo->findAll();
        $modulosAsignadosIds = [];

        foreach ($perfil->getPermisoPerfils() as $p) {
            $modulosAsignadosIds[] = $p->getModulo()->getId();
        }

        foreach ($todosLosModulos as $modulo) {
            if (!in_array($modulo->getId(), $modulosAsignadosIds)) {
                $nuevoPermiso = new PermisoPerfil();
                $nuevoPermiso->setModulo($modulo);
                $nuevoPermiso->setPerfil($perfil);
                $nuevoPermiso->setBoolConsultar(false);
                $nuevoPermiso->setBoolAgregar(false);
                $nuevoPermiso->setBoolEditar(false);
                $nuevoPermiso->setBoolEliminar(false);
                $em->persist($nuevoPermiso);
            }
        }
        $em->flush();
    }
}
