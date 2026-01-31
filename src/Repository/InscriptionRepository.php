<?php

namespace App\Repository;

use App\Entity\Inscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Inscription
 *
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Trouve les inscriptions actives d'un utilisateur
     * 
     * @return Inscription[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.session', 's')
            ->andWhere('i.user = :user')
            ->andWhere('i.statut = :statut')
            ->andWhere('s.actif = true')
            ->setParameter('user', $user)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les apprentis actifs d'une session
     * 
     * @return User[]
     */
    public function findApprentisActifsBySession(int $sessionId): array
    {
        $inscriptions = $this->createQueryBuilder('i')
            ->select('i', 'u')
            ->join('i.user', 'u')
            ->andWhere('i.session = :sessionId')
            ->andWhere('i.statut = :statut')
            ->andWhere('u.actif = true')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $apprentis = [];
        foreach ($inscriptions as $inscription) {
            $apprentis[] = $inscription->getUser();
        }

        return $apprentis;
    }

    /**
     * Trouve les apprentis actifs d'une formation (toutes sessions confondues)
     * 
     * @return User[]
     */
    public function findApprentisActifsByFormation(int $formationId): array
    {
        $inscriptions = $this->createQueryBuilder('i')
            ->select('DISTINCT u')
            ->join('i.user', 'u')
            ->join('i.session', 's')
            ->andWhere('s.formation = :formationId')
            ->andWhere('i.statut = :statut')
            ->andWhere('u.actif = true')
            ->andWhere('s.actif = true')
            ->setParameter('formationId', $formationId)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        return $inscriptions;
    }

    /**
     * Trouve les inscriptions par session
     * 
     * @return Inscription[]
     */
    public function findBySession(int $sessionId): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.user', 'u')
            ->andWhere('i.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les inscriptions par statut pour une session
     * 
     * @return array<string, int>
     */
    public function countByStatutForSession(int $sessionId): array
    {
        $results = $this->createQueryBuilder('i')
            ->select('i.statut, COUNT(i.id) as total')
            ->andWhere('i.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->groupBy('i.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach (Inscription::STATUTS as $code => $label) {
            $counts[$code] = 0;
        }
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Trouve les inscriptions validées d'une session
     * 
     * @return Inscription[]
     */
    public function findValidesBySession(int $sessionId): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.user', 'u')
            ->andWhere('i.session = :sessionId')
            ->andWhere('i.statut = :statut')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur est inscrit à une session
     */
    public function isUserInscrit(User $user, int $sessionId): bool
    {
        $count = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.user = :user')
            ->andWhere('i.session = :sessionId')
            ->andWhere('i.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('sessionId', $sessionId)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Trouve les apprentis avec des inscriptions actives
     * 
     * @return User[]
     */
    public function findAllApprentisActifs(): array
    {
        return $this->createQueryBuilder('i')
            ->select('DISTINCT u')
            ->join('i.user', 'u')
            ->join('i.session', 's')
            ->andWhere('i.statut = :statut')
            ->andWhere('u.actif = true')
            ->andWhere('s.actif = true')
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sauvegarde une inscription
     */
    public function save(Inscription $inscription, bool $flush = false): void
    {
        $this->getEntityManager()->persist($inscription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une inscription
     */
    public function remove(Inscription $inscription, bool $flush = false): void
    {
        $this->getEntityManager()->remove($inscription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
