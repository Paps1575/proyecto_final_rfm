<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\UsuarioType;
use App\Repository\UsuarioRepository;
use App\Repository\PerfilRepository;
use App\Repository\ModuloRepository;
use App\Security\Voter\ModuloVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/vistas/usuarios')]
#[IsGranted('ROLE_USER')]
final class UsuarioController extends AbstractController
{
    /**
     * Recupera el nombre del módulo para la seguridad dinámica del Voter
     */
    private function getNombreModulo(ModuloRepository $moduloRepo): string
    {
        $modulo = $moduloRepo->findOneBy(['strRuta' => 'app_usuario_index'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'USUARIOS'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'USUARIO']);

        return $modulo ? $modulo->getStrNombre() : 'USUARIOS';
    }

    /**
     * LISTADO CON PAGINACIÓN Y FILTROS
     */
    #[Route('/', name: 'app_usuario_index', methods: ['GET'])]
    public function index(
        UsuarioRepository $usuarioRepository,
        PerfilRepository $perfilRepository,
        ModuloRepository $moduloRepository,
        Request $request
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        // 🛡️ SEGURIDAD DINÁMICA: ¿Puede consultar?
        if (!$this->isGranted(ModuloVoter::CONSULTAR, $nombreMod)) {
            $this->addFlash('warning', 'Acceso denegado: Tu perfil no tiene permiso para consultar este módulo.');
            return $this->redirectToRoute('app_dashboard');
        }

        $limit = 5;
        $page = $request->query->getInt('page', 1);
        if ($page < 1) $page = 1;

        $nombre = $request->query->get('usuario');
        $perfilId = $request->query->get('perfil');
        $estado = $request->query->get('estado');

        if ($nombre || $perfilId || ($estado !== null && $estado !== '')) {
            $usuarios = $usuarioRepository->buscarConFiltros($nombre, $perfilId, $estado);
            $totalUsers = count($usuarios);
        } else {
            $totalUsers = $usuarioRepository->count([]);
            $usuarios = $usuarioRepository->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);
        }

        $pagesCount = ceil($totalUsers / $limit);

        return $this->render('usuario/index.html.twig', [
            'usuarios'     => $usuarios,
            'perfiles'     => $perfilRepository->findAll(),
            'nombreModulo' => $nombreMod,
            'currentPage'  => $page,
            'pagesCount'   => $pagesCount,
            'totalUsers'   => $totalUsers
        ]);
    }

    /**
     * CREAR NUEVO USUARIO
     */
    #[Route('/nuevo', name: 'app_usuario_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::AGREGAR, $nombreMod)) {
            $this->addFlash('danger', 'Acceso denegado: No tienes permiso para agregar registros.');
            return $this->redirectToRoute('app_usuario_index');
        }

        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // 1. Manejo de Imagen
                $fotoFile = $form->get('foto')->getData();
                if ($fotoFile) {
                    $originalFilename = pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $slugger->slug($originalFilename).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    try {
                        $fotoFile->move($this->getParameter('fotos_directory'), $newFilename);
                        $usuario->setFoto($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Error al subir la imagen.');
                    }
                } else {
                    $usuario->setFoto('default.png');
                }

                // 2. Hasheo de Password (usando el campo strPwd del formulario)
                if ($plainPassword = $form->get('strPwd')->getData()) {
                    $usuario->setPassword($passwordHasher->hashPassword($usuario, $plainPassword));
                }

                $entityManager->persist($usuario);
                $entityManager->flush();

                $this->addFlash('success', '¡Usuario creado correctamente!');
                return $this->redirectToRoute('app_usuario_index');
            } else {
                // Si el formulario no es válido (ej. teléfono corto), mandamos alerta
                $this->addFlash('danger', 'Híjole, revisa los campos. Hay errores de validación.');
            }
        }

        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

    /**
     * EDITAR USUARIO
     */
    #[Route('/{id}/editar', name: 'app_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Usuario $usuario,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::EDITAR, $nombreMod)) {
            $this->addFlash('danger', 'Acceso denegado: Tu perfil no puede editar usuarios.');
            return $this->redirectToRoute('app_usuario_index');
        }

        // 🛡️ PROTECCIÓN ADMIN RAÍZ
        if ($usuario->getId() === 7 && $this->getUser()->getId() !== 7) {
            $this->addFlash('danger', 'Acción restringida: El Administrador Principal está protegido.');
            return $this->redirectToRoute('app_usuario_index');
        }

        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Actualizar foto si se sube una nueva
                if ($fotoFile = $form->get('foto')->getData()) {
                    $originalFilename = pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $slugger->slug($originalFilename).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    $fotoFile->move($this->getParameter('fotos_directory'), $newFilename);
                    $usuario->setFoto($newFilename);
                }

                // Cambiar password solo si el campo no está vacío
                if ($plainPassword = $form->get('strPwd')->getData()) {
                    $usuario->setPassword($passwordHasher->hashPassword($usuario, $plainPassword));
                }

                $entityManager->flush();
                $this->addFlash('success', 'Los datos de ' . $usuario->getStrNombreUsuario() . ' se actualizaron correctamente.');
                return $this->redirectToRoute('app_usuario_index');
            } else {
                $this->addFlash('danger', 'Error al actualizar: Revisa los datos ingresados.');
            }
        }

        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

    /**
     * ELIMINAR USUARIO
     */
    #[Route('/{id}', name: 'app_usuario_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Usuario $usuario,
        EntityManagerInterface $entityManager,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::ELIMINAR, $nombreMod)) {
            if ($request->isXmlHttpRequest()) {
                return new Response('Acceso denegado', 403);
            }
            $this->addFlash('danger', 'Acceso denegado.');
            return $this->redirectToRoute('app_usuario_index');
        }

        // Impedir que se borre a sí mismo o al admin principal
        if ($usuario->getId() === 7 || $this->getUser() === $usuario) {
            $this->addFlash('warning', 'No puedes eliminar esta cuenta.');
            return $this->redirectToRoute('app_usuario_index');
        }

        if ($this->isCsrfTokenValid('delete'.$usuario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($usuario);
            $entityManager->flush();

            $this->addFlash('info', 'Usuario eliminado satisfactoriamente.');

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
        }

        return $this->redirectToRoute('app_usuario_index');
    }
}
