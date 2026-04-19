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
     * Si el usuario está bloqueado, Symfony ni siquiera gastará recursos en checar el password.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        // Si no es un objeto de nuestra entidad Usuario, no hacemos nada
        if (!$user instanceof Usuario) {
            return;
        }

        /**
         * BLINDAJE TOTAL:
         * Usamos el operador !== para que CUALQUIER cosa que no sea TRUE (booleano) lo rebote.
         * Si es false, null, 0 o "0", Sopitas va para afuera.
         */
        if ($user->isIdEstadoUsuario() !== true) {
            throw new CustomUserMessageAccountStatusException('Tu cuenta ha sido bloqueada o está inactiva. Contacta al administrador.');
        }
    }

    /**
     * Se ejecuta DESPUÉS de que la contraseña fue validada.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        // Doble verificación por si el estado cambió durante el proceso
        if ($user->isIdEstadoUsuario() !== true) {
            throw new CustomUserMessageAccountStatusException('Acceso denegado: Esta cuenta no se encuentra activa.');
        }
    }
}
