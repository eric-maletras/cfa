<?php

namespace App\Repository;

use App\Entity\Inscription;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Récupère les inscriptions d'une session avec filtres
     * 
     * @return Inscription[]
     */
    public function findBySessionWithFilters(
        Session $session,
        ?string $statut = null,
        ?string $recherche = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->join('i.user', 'u')
            ->andWhere('i.session = :session')
            ->setParameter('session', $session)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');
        
        if ($statut !== null && $statut !== '') {
            $qb->andWhere('i.statut = :statut')
               ->setParameter('statut', $statut);
        }
        
        if ($recherche !== null && trim($recherche) !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.nom)', ':recherche'),
                    $qb->expr()->like('LOWER(u.prenom)', ':recherche'),
                    $qb->expr()->like('LOWER(u.email)', ':recherche')
                )
            )->setParameter('recherche', '%' . strtolower(trim($recherche)) . '%');
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les inscriptions d'un utilisateur
     * 
     * @return Inscription[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.session', 's')
            ->join('s.formation', 'f')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les inscriptions par statut pour une session
     * 
     * @return array<string, int>
     */
    public function countByStatutForSession(Session $session): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.statut, COUNT(i.id) as total')
            ->andWhere('i.session = :session')
            ->setParameter('session', $session)
            ->groupBy('i.statut')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach (Inscription::STATUTS as $code => $libelle) {
            $counts[$code] = 0;
        }
        foreach ($result as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }
        
        return $counts;
    }

    /**
     * Récupère le nombre total d'inscrits validés pour une session
     */
    public function countValidesForSession(Session $session): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.session = :session')
            ->andWhere('i.statut = :statut')
            ->setParameter('session', $session)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vérifie si un utilisateur est déjà inscrit à une session
     */
    public function isUserInscrit(User $user, Session $session): bool
    {
        $count = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.user = :user')
            ->andWhere('i.session = :session')
            ->setParameter('user', $user)
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }

    /**
     * Récupère les apprentis disponibles pour inscription à une session
     * (ceux qui ne sont pas déjà inscrits)
     * 
     * @return User[]
     */
    public function findApprentisDisponibles(Session $session, string $roleApprenti = 'ROLE_APPRENTI'): array
    {
        $em = $this->getEntityManager();
        
        // Sous-requête pour les IDs déjà inscrits
        $subQuery = $this->createQueryBuilder('i2')
            ->select('IDENTITY(i2.user)')
            ->andWhere('i2.session = :session');
        
        // Requête principale sur les utilisateurs apprentis
        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.rolesEntities', 'r')
            ->andWhere('r.code = :roleCode')
            ->andWhere('u.actif = true')
            ->andWhere($qb->expr()->notIn('u.id', $subQuery->getDQL()))
            ->setParameter('roleCode', $roleApprenti)
            ->setParameter('session', $session)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les inscriptions actives en cours pour un utilisateur
     * 
     * @return Inscription[]
     */
    public function findInscriptionsActivesEnCours(User $user): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('i')
            ->join('i.session', 's')
            ->andWhere('i.user = :user')
            ->andWhere('i.statut = :statut')
            ->andWhere('s.dateDebut <= :now')
            ->andWhere('s.dateFin >= :now')
            ->setParameter('user', $user)
            ->setParameter('statut', Inscription::STATUT_VALIDEE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
