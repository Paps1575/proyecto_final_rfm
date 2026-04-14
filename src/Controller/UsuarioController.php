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
            $this->addFlash('warning', 'Acceso denegado.');
            return $this->redirectToRoute('app_dashboard');
        }

        $limit = 5;
        $page = $request->query->getInt('page', 1);
        $usuarios = $usuarioRepository->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);
        $totalUsers = $usuarioRepository->count([]);

        return $this->render('usuario/index.html.twig', [
            'usuarios'     => $usuarios,
            'perfiles'     => $perfilRepository->findAll(),
            'nombreModulo' => $nombreMod,
            'currentPage'  => $page,
            'pagesCount'   => ceil($totalUsers / $limit),
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
            $this->addFlash('danger', 'Sin permisos para agregar.');
            return $this->redirectToRoute('app_usuario_index');
        }

        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fotoFile = $form->get('foto')->getData();
            if ($fotoFile) {
                try {
                    $newFilename = $slugger->slug(pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    $fotoFile->move($this->getParameter('fotos_directory'), $newFilename);
                    $usuario->setFoto($newFilename);
                } catch (\Exception $e) {
                    $usuario->setFoto('default.png');
                }
            } else {
                $usuario->setFoto('default.png');
            }

            $plainPassword = $form->get('strPwd')->getData();
            if ($plainPassword) {
                $usuario->setPassword($passwordHasher->hashPassword($usuario, $plainPassword));
            }

            $entityManager->persist($usuario);
            $entityManager->flush();

            $this->addFlash('success', '¡Usuario creado!');
            return $this->redirectToRoute('app_usuario_index');
        }

        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
            'nombreModulo' => $nombreMod, // ¡ESTA VARIABLE FALTABA!
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
            $this->addFlash('danger', 'Sin permisos para editar.');
            return $this->redirectToRoute('app_usuario_index');
        }

        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fotoFile = $form->get('foto')->getData();
            if ($fotoFile) {
                try {
                    $newFilename = $slugger->slug(pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    $fotoFile->move($this->getParameter('fotos_directory'), $newFilename);
                    $usuario->setFoto($newFilename);
                } catch (\Exception $e) {}
            }

            $plainPassword = $form->get('strPwd')->getData();
            if ($plainPassword) {
                $usuario->setPassword($passwordHasher->hashPassword($usuario, $plainPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Usuario actualizado.');
            return $this->redirectToRoute('app_usuario_index');
        }

        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
            'nombreModulo' => $nombreMod, // ¡Y AQUÍ TAMBIÉN FALTABA!
        ]);
    }

    #[Route('/{id}', name: 'app_usuario_delete', methods: ['POST'])]
    public function delete(Request $request, Usuario $usuario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$usuario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($usuario);
            $entityManager->flush();
            $this->addFlash('info', 'Usuario eliminado.');
        }
        return $this->redirectToRoute('app_usuario_index');
    }
}
