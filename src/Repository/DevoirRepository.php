<?php

namespace App\Repository;

use App\Entity\Devoir;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Devoir
 * 
 * @extends ServiceEntityRepository<Devoir>
 */
class DevoirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Devoir::class);
    }

    /**
     * Récupère les devoirs d'une session avec le formateur
     * 
     * @return Devoir[]
     */
    public function findBySession(Session $session): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.formateur', 'f')
            ->addSelect('f')
            ->andWhere('d.session = :session')
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs visibles d'une session (pour les apprenants)
     * 
     * @return Devoir[]
     */
    public function findVisiblesBySession(Session $session): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.session = :session')
            ->andWhere('d.visible = true')
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs créés par un formateur
     * 
     * @return Devoir[]
     */
    public function findByFormateur(User $formateur): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.session', 's')
            ->addSelect('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('d.formateur = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs d'un formateur pour une session
     * 
     * @return Devoir[]
     */
    public function findByFormateurAndSession(User $formateur, Session $session): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.formateur = :formateur')
            ->andWhere('d.session = :session')
            ->setParameter('formateur', $formateur)
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs avec leurs notes (évite N+1)
     * 
     * @return Devoir[]
     */
    public function findBySessionWithNotes(Session $session): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.formateur', 'f')
            ->addSelect('f')
            ->leftJoin('d.notes', 'n')
            ->addSelect('n')
            ->leftJoin('n.apprenant', 'a')
            ->addSelect('a')
            ->andWhere('d.session = :session')
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un devoir avec toutes ses relations
     */
    public function findOneWithRelations(int $id): ?Devoir
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.session', 's')
            ->addSelect('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->leftJoin('d.formateur', 'form')
            ->addSelect('form')
            ->leftJoin('d.notes', 'n')
            ->addSelect('n')
            ->leftJoin('n.apprenant', 'a')
            ->addSelect('a')
            ->andWhere('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les devoirs à venir pour une session
     * 
     * @return Devoir[]
     */
    public function findUpcomingBySession(Session $session, int $limit = 5): array
    {
        $now = new \DateTime('today');
        
        return $this->createQueryBuilder('d')
            ->andWhere('d.session = :session')
            ->andWhere('d.dateDevoir >= :now')
            ->andWhere('d.visible = true')
            ->setParameter('session', $session)
            ->setParameter('now', $now)
            ->orderBy('d.dateDevoir', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs récents pour un formateur
     * 
     * @return Devoir[]
     */
    public function findRecentByFormateur(User $formateur, int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.session', 's')
            ->addSelect('s')
            ->andWhere('d.formateur = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les devoirs par type pour une session
     * 
     * @return array<string, int>
     */
    public function countByTypeForSession(Session $session): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('d.type, COUNT(d.id) as total')
            ->andWhere('d.session = :session')
            ->setParameter('session', $session)
            ->groupBy('d.type')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach (Devoir::TYPES as $code => $libelle) {
            $counts[$code] = 0;
        }
        foreach ($results as $row) {
            $counts[$row['type']] = (int) $row['total'];
        }
        
        return $counts;
    }

    /**
     * Récupère les devoirs sans notes saisies pour une session
     * 
     * @return Devoir[]
     */
    public function findDevoirsSansNotes(Session $session): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.notes', 'n')
            ->andWhere('d.session = :session')
            ->andWhere('n.id IS NULL OR n.valeur IS NULL')
            ->setParameter('session', $session)
            ->orderBy('d.dateDevoir', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les devoirs d'un apprenant via ses inscriptions
     * 
     * @return Devoir[]
     */
    public function findByApprenant(User $apprenant): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.session', 's')
            ->addSelect('s')
            ->leftJoin('s.inscriptions', 'i')
            ->leftJoin('d.notes', 'n', 'WITH', 'n.apprenant = :apprenant')
            ->addSelect('n')
            ->andWhere('i.user = :apprenant')
            ->andWhere('i.statut = :statut')
            ->andWhere('d.visible = true')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('statut', 'validee')
            ->orderBy('d.dateDevoir', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des devoirs pour un formateur
     * 
     * @return array{total: int, notes_saisies: int, notes_manquantes: int}
     */
    public function getStatsByFormateur(User $formateur): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id) as total')
            ->andWhere('d.formateur = :formateur')
            ->setParameter('formateur', $formateur);
        
        $total = (int) $qb->getQuery()->getSingleScalarResult();
        
        // Cette requête est simplifiée, une version plus complexe pourrait
        // calculer le taux de complétion des notes
        return [
            'total' => $total,
        ];
    }
}
