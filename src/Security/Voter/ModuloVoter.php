<?php

namespace App\Security\Voter;

use App\Entity\Usuario;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class ModuloVoter extends Voter
{
    public const CONSULTAR = 'MODULO_CONSULTAR';
    public const AGREGAR   = 'MODULO_AGREGAR';
    public const EDITAR    = 'MODULO_EDITAR';
    public const ELIMINAR  = 'MODULO_ELIMINAR';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CONSULTAR, self::AGREGAR, self::EDITAR, self::ELIMINAR])
            && is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Usuario) return false;

        $perfil = $user->getPerfil();
        if (!$perfil) return false;

        // 🔥 PASO VIP: Si el perfil tiene marcado el bit de Administrador Total
        if ((bool)($perfil->isBitAdministrador() ?? false)) {
            return true;
        }

        // --- SI NO ES ADMIN, BUSCAMOS EN LA MATRIZ ---
        $nombreModuloSolicitado = strtoupper(trim((string)$subject));
        return $this->checkDb($perfil, $nombreModuloSolicitado, $attribute);
    }

    private function checkDb($perfil, string $moduloBuscado, string $accion): bool
    {
        $buscado = strtoupper(trim($moduloBuscado));
        $buscadoSinS = rtrim($buscado, 'S');
        $buscadoConS = $buscadoSinS . 'S';

        foreach ($perfil->getPermisoPerfils() as $p) {
            $modulo = $p->getModulo();
            if (!$modulo) continue;

            $nombreDb = strtoupper(trim($modulo->getStrNombre()));

            if ($nombreDb === $buscado || $nombreDb === $buscadoSinS || $nombreDb === $buscadoConS) {
                return match($accion) {
                    self::CONSULTAR => (bool)($p->isBoolConsultar() ?? false),
                    self::AGREGAR   => (bool)($p->isBoolAgregar() ?? false),
                    self::EDITAR    => (bool)($p->isBoolEditar() ?? false),
                    self::ELIMINAR  => (bool)($p->isBoolEliminar() ?? false),
                    default         => false,
                };
            }
        }
        return false;
    }
}
