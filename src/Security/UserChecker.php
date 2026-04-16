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
     * Si aquí falla, ni siquiera revisa si la contraseña es correcta.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        /**
         * EXPLICACIÓN PARA EL PROFE:
         * Usamos isIdEstadoUsuario() que es el getter de tu booleano.
         * Si el valor es false (Bloqueado), se lanza la excepción.
         */
        if ($user->isIdEstadoUsuario() === false) {
            throw new CustomUserMessageAccountStatusException('Tu cuenta ha sido bloqueada por el administrador.');
        }
    }

    /**
     * Se ejecuta DESPUÉS de que la contraseña es correcta.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        // Si por algo se nos pasó en el PreAuth, lo rematamos aquí
        if ($user->isIdEstadoUsuario() === false) {
            throw new CustomUserMessageAccountStatusException('Cuenta inactiva. Acceso denegado.');
        }
    }
}
