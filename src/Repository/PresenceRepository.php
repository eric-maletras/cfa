<?php

namespace App\Repository;

use App\Entity\Appel;
use App\Entity\Presence;
use App\Entity\SeancePlanifiee;
use App\Entity\User;
use App\Enum\StatutPresence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Presence
 *
 * @extends ServiceEntityRepository<Presence>
 */
class PresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presence::class);
    }

    /**
     * Trouve une présence par son token
     */
    public function findByToken(string $token): ?Presence
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les présences pour un appel
     */
    public function findByAppel(Appel $appel): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.apprenti', 'a')
            ->andWhere('p.appel = :appel')
            ->setParameter('appel', $appel)
            ->orderBy('a.nom', 'ASC')
            ->addOrderBy('a.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les présences d'un apprenti pour une période
     */
    public function findByApprentiAndPeriode(
        User $apprenti,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): array {
        return $this->createQueryBuilder('p')
            ->join('p.appel', 'a')
            ->join('a.seance', 's')
            ->andWhere('p.apprenti = :apprenti')
            ->andWhere('s.date >= :dateDebut')
            ->andWhere('s.date <= :dateFin')
            ->setParameter('apprenti', $apprenti)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('s.date', 'DESC')
            ->addOrderBy('s.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les présences en attente de signature pour un appel
     */
    public function findEnAttenteByAppel(Appel $appel): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.apprenti', 'a')
            ->andWhere('p.appel = :appel')
            ->andWhere('p.statut = :statut')
            ->setParameter('appel', $appel)
            ->setParameter('statut', StatutPresence::EN_ATTENTE)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les présences signées pour un appel
     */
    public function findSigneesByAppel(Appel $appel): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.apprenti', 'a')
            ->andWhere('p.appel = :appel')
            ->andWhere('p.dateSignature IS NOT NULL')
            ->setParameter('appel', $appel)
            ->orderBy('p.dateSignature', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les présences par statut pour un appel
     */
    public function countByStatutForAppel(Appel $appel): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.statut, COUNT(p.id) as count')
            ->andWhere('p.appel = :appel')
            ->setParameter('appel', $appel)
            ->groupBy('p.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach (StatutPresence::cases() as $statut) {
            $counts[$statut->value] = 0;
        }
        foreach ($results as $row) {
            $counts[$row['statut']->value] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Trouve les statistiques de présence pour un apprenti
     */
    public function getStatistiquesApprenti(User $apprenti, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as total')
            ->addSelect('SUM(CASE WHEN p.statut = :present OR p.statut = :retard THEN 1 ELSE 0 END) as presents')
            ->addSelect('SUM(CASE WHEN p.statut = :absent THEN 1 ELSE 0 END) as absents')
            ->addSelect('SUM(CASE WHEN p.statut = :absJustifie THEN 1 ELSE 0 END) as absentsJustifies')
            ->addSelect('SUM(CASE WHEN p.statut = :retard THEN 1 ELSE 0 END) as retards')
            ->addSelect('SUM(CASE WHEN p.statut = :nonSigne THEN 1 ELSE 0 END) as nonSignes')
            ->join('p.appel', 'a')
            ->join('a.seance', 's')
            ->andWhere('p.apprenti = :apprenti')
            ->andWhere('a.cloture = true')
            ->setParameter('apprenti', $apprenti)
            ->setParameter('present', StatutPresence::PRESENT)
            ->setParameter('retard', StatutPresence::RETARD)
            ->setParameter('absent', StatutPresence::ABSENT)
            ->setParameter('absJustifie', StatutPresence::ABSENT_JUSTIFIE)
            ->setParameter('nonSigne', StatutPresence::NON_SIGNE);

        if ($dateDebut) {
            $qb->andWhere('s.date >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('s.date <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        $result = $qb->getQuery()->getSingleResult();
        
        // Calculer le taux de présence
        $total = (int) $result['total'];
        $presents = (int) $result['presents'];
        $result['tauxPresence'] = $total > 0 ? round(($presents / $total) * 100, 1) : 0;

        return $result;
    }

    /**
     * Trouve les présences pour une séance (tous les appels confondus)
     */
    public function findBySeance(SeancePlanifiee $seance): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.appel', 'a')
            ->join('p.apprenti', 'ap')
            ->andWhere('a.seance = :seance')
            ->setParameter('seance', $seance)
            ->orderBy('ap.nom', 'ASC')
            ->addOrderBy('ap.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Met à jour les présences en attente expirées vers NON_SIGNE
     */
    public function marquerNonSignesExpires(): int
    {
        $qb = $this->createQueryBuilder('p')
            ->update()
            ->set('p.statut', ':nonSigne')
            ->set('p.updatedAt', ':now')
            ->where('p.statut = :enAttente')
            ->andWhere('p.appel IN (
                SELECT a.id FROM App\Entity\Appel a 
                WHERE a.dateExpiration < :now AND a.cloture = false
            )')
            ->setParameter('nonSigne', StatutPresence::NON_SIGNE)
            ->setParameter('enAttente', StatutPresence::EN_ATTENTE)
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->execute();
    }

    /**
     * Sauvegarde une présence
     */
    public function save(Presence $presence, bool $flush = false): void
    {
        $this->getEntityManager()->persist($presence);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une présence
     */
    public function remove(Presence $presence, bool $flush = false): void
    {
        $this->getEntityManager()->remove($presence);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
