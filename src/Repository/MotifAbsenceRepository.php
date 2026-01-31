<?php

namespace App\Repository;

use App\Entity\MotifAbsence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité MotifAbsence
 *
 * @extends ServiceEntityRepository<MotifAbsence>
 */
class MotifAbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MotifAbsence::class);
    }

    /**
     * Trouve tous les motifs actifs, triés par ordre
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.actif = true')
            ->orderBy('m.ordre', 'ASC')
            ->addOrderBy('m.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les motifs triés par ordre (actifs et inactifs)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.ordre', 'ASC')
            ->addOrderBy('m.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un motif par son code
     */
    public function findByCode(string $code): ?MotifAbsence
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre d'utilisations d'un motif
     */
    public function countUtilisations(MotifAbsence $motif): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(p.id)')
            ->join('m.presences', 'p')
            ->andWhere('m.id = :id')
            ->setParameter('id', $motif->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Statistiques d'utilisation des motifs
     */
    public function getStatistiquesUtilisation(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.id, m.libelle, m.code, COUNT(p.id) as nbUtilisations')
            ->leftJoin('m.presences', 'p')
            ->groupBy('m.id')
            ->orderBy('nbUtilisations', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sauvegarde un motif
     */
    public function save(MotifAbsence $motif, bool $flush = false): void
    {
        $this->getEntityManager()->persist($motif);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un motif
     */
    public function remove(MotifAbsence $motif, bool $flush = false): void
    {
        $this->getEntityManager()->remove($motif);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
