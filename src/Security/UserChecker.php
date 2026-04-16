<?php

namespace App\Security;

use App\Entity\Usuario;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * Se ejecuta ANTES de verificar la contraseña.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        // Si el usuario no está activo, lo rebotamos de una
        if (!$user->isActivo()) {
            throw new CustomUserMessageAccountStatusException('Tu cuenta ha sido bloqueada por el administrador.');
        }
    }

    /**
     * Se ejecuta DESPUÉS de que la contraseña es correcta.
     * Aquí es donde faltaba el parámetro TokenInterface para ser compatible.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        // Aquí podrías poner validaciones extra después del login,
        // pero por ahora lo dejamos pasar.
    }
}
