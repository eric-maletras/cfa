<?php

namespace App\Repository;

use App\Entity\Droit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Droit>
 */
class DroitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Droit::class);
    }

    /**
     * Trouve un droit par son code
     */
    public function findByCode(string $code): ?Droit
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Trouve tous les droits d'un module
     */
    public function findByModule(string $module): array
    {
        return $this->findBy(['module' => $module], ['libelle' => 'ASC']);
    }

    /**
     * Trouve tous les droits triés par module puis par libellé
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.module', 'ASC')
            ->addOrderBy('d.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste tous les modules distincts
     */
    public function findAllModules(): array
    {
        return $this->createQueryBuilder('d')
            ->select('DISTINCT d.module')
            ->where('d.module IS NOT NULL')
            ->orderBy('d.module', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
