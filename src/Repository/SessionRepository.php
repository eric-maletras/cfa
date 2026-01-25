<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Session
 * 
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * Récupère toutes les sessions actives triées par date de début décroissante
     * 
     * @return Session[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions avec leur formation (évite N+1)
     * 
     * @return Session[]
     */
    public function findAllWithFormation(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->leftJoin('s.responsable', 'r')
            ->addSelect('r')
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions actives avec leur formation
     * 
     * @return Session[]
     */
    public function findActiveWithFormation(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->leftJoin('s.responsable', 'r')
            ->addSelect('r')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions d'une formation donnée
     * 
     * @return Session[]
     */
    public function findByFormation(Formation $formation): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.formation = :formation')
            ->andWhere('s.actif = :actif')
            ->setParameter('formation', $formation)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions par statut
     * 
     * @return Session[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.actif = :actif')
            ->setParameter('statut', $statut)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions en cours
     * 
     * @return Session[]
     */
    public function findEnCours(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.dateDebut <= :now')
            ->andWhere('s.dateFin >= :now')
            ->andWhere('s.actif = :actif')
            ->setParameter('statut', Session::STATUT_EN_COURS)
            ->setParameter('now', $now)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions avec inscriptions ouvertes
     * 
     * @return Session[]
     */
    public function findInscriptionsOuvertes(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.statut = :statut')
            ->andWhere('(s.dateDebutInscriptions IS NULL OR s.dateDebutInscriptions <= :now)')
            ->andWhere('(s.dateFinInscriptions IS NULL OR s.dateFinInscriptions >= :now)')
            ->andWhere('s.actif = :actif')
            ->setParameter('statut', Session::STATUT_INSCRIPTIONS_OUVERTES)
            ->setParameter('now', $now)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions à venir (planifiées ou inscriptions ouvertes)
     * 
     * @return Session[]
     */
    public function findAVenir(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.dateDebut > :now')
            ->andWhere('s.statut IN (:statuts)')
            ->andWhere('s.actif = :actif')
            ->setParameter('now', $now)
            ->setParameter('statuts', [
                Session::STATUT_PLANIFIEE,
                Session::STATUT_INSCRIPTIONS_OUVERTES
            ])
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions terminées
     * 
     * @return Session[]
     */
    public function findTerminees(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.actif = :actif')
            ->setParameter('statut', Session::STATUT_TERMINEE)
            ->setParameter('actif', true)
            ->orderBy('s.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions dont un utilisateur est responsable
     * 
     * @return Session[]
     */
    public function findByResponsable(User $responsable): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.responsable = :responsable')
            ->andWhere('s.actif = :actif')
            ->setParameter('responsable', $responsable)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions où un utilisateur intervient comme formateur
     * 
     * @return Session[]
     */
    public function findByFormateur(User $formateur): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->leftJoin('s.formateurs', 'form')
            ->andWhere('form = :formateur')
            ->andWhere('s.actif = :actif')
            ->setParameter('formateur', $formateur)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions par année de début
     * 
     * @return Session[]
     */
    public function findByAnnee(int $annee): array
    {
        $debut = new \DateTime("$annee-01-01");
        $fin = new \DateTime("$annee-12-31");
        
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.dateDebut >= :debut')
            ->andWhere('s.dateDebut <= :fin')
            ->andWhere('s.actif = :actif')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de sessions par mot-clé (code, libellé, formation)
     * 
     * @return Session[]
     */
    public function search(string $terme): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.code LIKE :terme OR s.libelle LIKE :terme OR f.intitule LIKE :terme')
            ->andWhere('s.actif = :actif')
            ->setParameter('terme', '%' . $terme . '%')
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les sessions par statut
     * 
     * @return array<string, int>
     */
    public function countByStatut(): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.statut, COUNT(s.id) as total')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('s.statut')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }
        
        return $counts;
    }

    /**
     * Récupère les années distinctes de début de session
     * 
     * @return int[]
     */
    public function findDistinctAnnees(): array
    {
        // Utiliser une requête native car YEAR() n'est pas dispo en DQL
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = 'SELECT DISTINCT YEAR(date_debut) as annee 
                FROM session 
                WHERE actif = 1 
                ORDER BY annee DESC';
        
        $results = $conn->executeQuery($sql)->fetchAllAssociative();
        
        return array_map(fn($row) => (int) $row['annee'], $results);
    }

    /**
     * Récupère les sessions pour affichage calendrier (période donnée)
     * 
     * @return Session[]
     */
    public function findForCalendar(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->andWhere('s.dateDebut <= :fin')
            ->andWhere('s.dateFin >= :debut')
            ->andWhere('s.actif = :actif')
            ->andWhere('s.statut != :annulee')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('actif', true)
            ->setParameter('annulee', Session::STATUT_ANNULEE)
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie l'unicité du code
     */
    public function isCodeUnique(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.code = :code')
            ->setParameter('code', $code);
        
        if ($excludeId !== null) {
            $qb->andWhere('s.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    /**
     * Récupère les sessions pour export (avec toutes les relations)
     * 
     * @return Session[]
     */
    public function findForExport(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formation', 'f')
            ->addSelect('f')
            ->leftJoin('f.niveauQualification', 'nq')
            ->addSelect('nq')
            ->leftJoin('f.typeCertification', 'tc')
            ->addSelect('tc')
            ->leftJoin('s.responsable', 'r')
            ->addSelect('r')
            ->leftJoin('s.formateurs', 'form')
            ->addSelect('form')
            ->andWhere('s.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les choix pour un formulaire
     * Retourne un tableau [libellé => id] trié par date décroissante
     * 
     * @return array<string, int>
     */
    public function findChoicesForForm(): array
    {
        $sessions = $this->findActiveWithFormation();
        $choices = [];
        
        foreach ($sessions as $session) {
            $label = $session->getCode() . ' - ' . $session->getLibelle();
            $choices[$label] = $session->getId();
        }
        
        return $choices;
    }

}
