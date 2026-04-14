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
    public function index(UsuarioRepository $repo, PerfilRepository $perfilRepo, ModuloRepository $modRepo, Request $request): Response
    {
        $nombreMod = $this->getNombreModulo($modRepo);
        $limit = 5;
        $page = $request->query->getInt('page', 1);
        $usuarios = $repo->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);

        return $this->render('usuario/index.html.twig', [
            'usuarios' => $usuarios,
            'perfiles' => $perfilRepo->findAll(),
            'nombreModulo' => $nombreMod,
            'currentPage' => $page,
            'pagesCount' => ceil($repo->count([]) / $limit),
            'totalUsers' => $repo->count([])
        ]);
    }

    #[Route('/nuevo', name: 'app_usuario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, SluggerInterface $slugger, ModuloRepository $modRepo): Response
    {
        $nombreMod = $this->getNombreModulo($modRepo);
        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fotoFile = $form->get('foto')->getData();
            if ($fotoFile) {
                try {
                    $newFilename = $slugger->slug(pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    // Usamos kernel.project_dir que es nativo de Symfony
                    $dest = $this->getParameter('kernel.project_dir') . '/public/uploads/fotos';
                    $fotoFile->move($dest, $newFilename);
                    $usuario->setFoto($newFilename);
                } catch (\Exception $e) {
                    $usuario->setFoto('default.png');
                }
            } else {
                $usuario->setFoto('default.png');
            }

            $plainPassword = $form->get('strPwd')->getData();
            if ($plainPassword) {
                $usuario->setPassword($hasher->hashPassword($usuario, $plainPassword));
            }

            $em->persist($usuario);
            $em->flush();
            $this->addFlash('success', 'Usuario creado.');
            return $this->redirectToRoute('app_usuario_index');
        }

        // RUTA: usuario/new.html.twig (Basado en tu imagen)
        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
            'nombreModulo' => $nombreMod // Variable necesaria para el layout
        ]);
    }

    #[Route('/{id}/editar', name: 'app_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Usuario $usuario, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, SluggerInterface $slugger, ModuloRepository $modRepo): Response
    {
        $nombreMod = $this->getNombreModulo($modRepo);
        $form = $this->createForm(UsuarioType::class, $usuario, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fotoFile = $form->get('foto')->getData();
            if ($fotoFile) {
                try {
                    $newFilename = $slugger->slug(pathinfo($fotoFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$fotoFile->guessExtension();
                    $dest = $this->getParameter('kernel.project_dir') . '/public/uploads/fotos';
                    $fotoFile->move($dest, $newFilename);
                    $usuario->setFoto($newFilename);
                } catch (\Exception $e) {}
            }

            $plainPassword = $form->get('strPwd')->getData();
            if ($plainPassword) {
                $usuario->setPassword($hasher->hashPassword($usuario, $plainPassword));
            }

            $em->flush();
            $this->addFlash('success', 'Usuario actualizado.');
            return $this->redirectToRoute('app_usuario_index');
        }

        // RUTA: usuario/edit.html.twig (Basado en tu imagen)
        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form->createView(),
            'nombreModulo' => $nombreMod // Variable necesaria para el layout
        ]);
    }

    #[Route('/{id}', name: 'app_usuario_delete', methods: ['POST'])]
    public function delete(Request $request, Usuario $usuario, EntityManagerInterface $em): Response {
        if ($this->isCsrfTokenValid('delete'.$usuario->getId(), $request->request->get('_token'))) {
            $em->remove($usuario);
            $em->flush();
        }
        return $this->redirectToRoute('app_usuario_index');
    }
}
