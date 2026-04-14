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
    private function getNombreModulo(ModuloRepository $moduloRepo): string
    {
        $modulo = $moduloRepo->findOneBy(['strRuta' => 'app_usuario_index'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'USUARIOS'])
            ?? $moduloRepo->findOneBy(['strNombre' => 'USUARIO']);

        return $modulo ? $modulo->getStrNombre() : 'USUARIOS';
    }

    #[Route('/', name: 'app_usuario_index', methods: ['GET'])]
    public function index(
        UsuarioRepository $usuarioRepository,
        PerfilRepository $perfilRepository,
        ModuloRepository $moduloRepository,
        Request $request
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

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
                        $this->addFlash('danger', 'Error físico al guardar la imagen en el servidor.');
                    }
                } else {
                    $usuario->setFoto('default.png');
                }

                // 2. Hasheo de Password (obtenido directamente del objeto mapeado)
                if ($usuario->getStrPwd()) {
                    $hashedPassword = $passwordHasher->hashPassword($usuario, $usuario->getStrPwd());
                    $usuario->setPassword($hashedPassword);
                }

                try {
                    $entityManager->persist($usuario);
                    $entityManager->flush();

                    $this->addFlash('success', '¡Usuario ' . $usuario->getStrNombreUsuario() . ' creado correctamente!');
                    return $this->redirectToRoute('app_usuario_index');
                } catch (\Exception $e) {
                    // Captura errores de base de datos (como duplicados si falló el UniqueEntity)
                    $this->addFlash('danger', 'Error al guardar en base de datos: ' . $e->getMessage());
                }
            } else {
                // Alerta para el nuevo Twig
                $this->addFlash('danger', 'Híjole, el formulario tiene errores. Revisa los campos marcados.');
            }
        }

        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

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

        if ($usuario->getId() === 7 && $this->getUser()->getId() !== 7) {
            $this->addFlash('danger', 'Acción restringida: El Administrador Principal está protegido.');
            return $this->redirectToRoute('app_usuario_index');
        }

        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($fotoFile = $form->get('foto')->getData()) {
                    $originalFilename = pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $slugger->slug($originalFilename).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    $fotoFile->move($this->getParameter('fotos_directory'), $newFilename);
                    $usuario->setFoto($newFilename);
                }

                if ($usuario->getStrPwd()) {
                    $usuario->setPassword($passwordHasher->hashPassword($usuario, $usuario->getStrPwd()));
                }

                try {
                    $entityManager->flush();
                    $this->addFlash('success', 'Los datos de ' . $usuario->getStrNombreUsuario() . ' se actualizaron correctamente.');
                    return $this->redirectToRoute('app_usuario_index');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Error al actualizar base de datos.');
                }
            } else {
                $this->addFlash('danger', 'Error al actualizar: Revisa los datos ingresados.');
            }
        }

        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_usuario_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Usuario $usuario,
        EntityManagerInterface $entityManager,
        ModuloRepository $moduloRepository
    ): Response {
        $nombreMod = $this->getNombreModulo($moduloRepository);

        if (!$this->isGranted(ModuloVoter::ELIMINAR, $nombreMod)) {
            return $request->isXmlHttpRequest() ? new Response('Acceso denegado', 403) : $this->redirectToRoute('app_usuario_index');
        }

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
