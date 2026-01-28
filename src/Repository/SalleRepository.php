<?php

namespace App\Repository;

use App\Entity\Salle;
use App\Entity\TypeSalle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Salle
 * 
 * @extends ServiceEntityRepository<Salle>
 */
class SalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salle::class);
    }

    /**
     * Retourne toutes les salles actives, triées par code
     * 
     * @return Salle[]
     */
    public function findActives(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('s.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les salles d'un type donné
     * 
     * @param TypeSalle $type Le type de salle recherché
     * @param bool $activeOnly Ne retourner que les salles actives
     * @return Salle[]
     */
    public function findByType(TypeSalle $type, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.type = :type')
            ->setParameter('type', $type)
            ->orderBy('s.code', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('s.actif = :actif')
               ->setParameter('actif', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les salles ayant une capacité minimale
     * Exclut les salles virtuelles (capacité illimitée)
     * 
     * @param int $capaciteMin Capacité minimale requise
     * @param bool $activeOnly Ne retourner que les salles actives
     * @return Salle[]
     */
    public function findByCapaciteMin(int $capaciteMin, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.capacite >= :capaciteMin')
            ->setParameter('capaciteMin', $capaciteMin)
            ->orderBy('s.capacite', 'ASC')
            ->addOrderBy('s.code', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('s.actif = :actif')
               ->setParameter('actif', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les salles correspondant aux critères de recherche
     * 
     * @param string|null $search Terme de recherche (code ou libellé)
     * @param TypeSalle|null $type Type de salle
     * @param bool|null $actif Statut actif (null = tous)
     * @return Salle[]
     */
    public function findByFilters(?string $search = null, ?TypeSalle $type = null, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.type', 'ASC')
            ->addOrderBy('s.code', 'ASC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('s.code LIKE :search OR s.libelle LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type !== null) {
            $qb->andWhere('s.type = :type')
               ->setParameter('type', $type);
        }

        if ($actif !== null) {
            $qb->andWhere('s.actif = :actif')
               ->setParameter('actif', $actif);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne la salle virtuelle (distanciel)
     */
    public function findSalleVirtuelle(): ?Salle
    {
        return $this->findOneBy([
            'type' => TypeSalle::VIRTUEL,
            'actif' => true,
        ]);
    }

    /**
     * Retourne les salles physiques (non virtuelles) actives
     * 
     * @return Salle[]
     */
    public function findSallesPhysiques(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.type != :typeVirtuel')
            ->andWhere('s.actif = :actif')
            ->setParameter('typeVirtuel', TypeSalle::VIRTUEL)
            ->setParameter('actif', true)
            ->orderBy('s.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de salles par type
     * 
     * @return array<string, int>
     */
    public function countByType(): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('s.type, COUNT(s.id) as total')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('s.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['type']->value] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Vérifie si un code existe déjà (hors entité actuelle)
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.code = :code')
            ->setParameter('code', strtoupper($code));

        if ($excludeId !== null) {
            $qb->andWhere('s.id != :id')
               ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
