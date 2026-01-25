<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Trouve un rôle par son code
     */
    public function findByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Trouve tous les rôles triés par libellé
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['libelle' => 'ASC']);
    }
}
