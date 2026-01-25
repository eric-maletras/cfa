<?php
// src/Repository/ModuleRepository.php

namespace App\Repository;

use App\Entity\Module;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * Récupère tous les modules actifs accessibles par un utilisateur
     * selon ses rôles, triés par ordre
     * 
     * @return Module[]
     */
     public function findAccessibleByUser(User $user): array
{
    // Récupérer les IDs des rôles de l'utilisateur
    $roleIds = [];
    foreach ($user->getRolesEntities() as $role) {
        $roleIds[] = $role->getId();
    }

    if (empty($roleIds)) {
        return [];
    }

    return $this->createQueryBuilder('m')
        ->join('m.roles', 'r')
        ->where('m.actif = :actif')
        ->andWhere('m.parent IS NULL')
        ->andWhere('r.id IN (:roleIds)')
        ->setParameter('actif', true)
        ->setParameter('roleIds', $roleIds)
        ->groupBy('m.id')
        ->orderBy('m.ordre', 'ASC')
        ->addOrderBy('m.nom', 'ASC')
        ->getQuery()
        ->getResult();
}
    /**
     * Récupère tous les modules actifs (pour l'administration)
     * 
     * @return Module[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les modules racines (sans parent) triés
     * 
     * @return Module[]
     */
    public function findRootModules(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.parent IS NULL')
            ->orderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les modules par rôle
     * 
     * @return Module[]
     */
    public function findByRole(int $roleId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.roles', 'r')
            ->where('r.id = :roleId')
            ->andWhere('m.actif = :actif')
            ->setParameter('roleId', $roleId)
            ->setParameter('actif', true)
            ->orderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
