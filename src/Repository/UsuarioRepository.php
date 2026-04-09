<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    /**
     * Busca usuarios aplicando filtros dinámicos (Nombre, Perfil y Estado)
     * @return Usuario[]
     */
    public function buscarConFiltros(?string $nombre, ?string $perfilId, ?string $estado): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.perfil', 'p') // Unimos con la tabla Perfil
            ->addSelect('p');

        // Filtro por nombre de usuario (Buscador)
        if ($nombre) {
            $qb->andWhere('u.strNombreUsuario LIKE :nombre')
                ->setParameter('nombre', '%' . $nombre . '%');
        }

        // Filtro por ID de Perfil (Select)
        if ($perfilId) {
            $qb->andWhere('p.id = :perfilId')
                ->setParameter('perfilId', $perfilId);
        }

        // Filtro por Estado (Checkbox/Select)
        // Validamos que no sea nulo ni cadena vacía para que el 0 (Inactivo) pase
        if ($estado !== null && $estado !== '') {
            $qb->andWhere('u.idEstadoUsuario = :estado')
                ->setParameter('estado', $estado);
        }

        return $qb->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
