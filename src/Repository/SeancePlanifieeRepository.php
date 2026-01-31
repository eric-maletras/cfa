<?php

namespace App\Repository;

use App\Entity\SeancePlanifiee;
use App\Entity\CreneauRecurrent;
use App\Entity\Session;
use App\Entity\Salle;
use App\Entity\User;
use App\Enum\StatutSeance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeancePlanifiee>
 */
class SeancePlanifieeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeancePlanifiee::class);
    }

    /**
     * Trouve les séances d'une session
     * 
     * @return SeancePlanifiee[]
     */
    public function findBySession(Session $session): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->leftJoin('s.formateurs', 'f')
            ->addSelect('sm', 'm', 'sal', 'f')
            ->where('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les séances d'une date précise
     * 
     * @return SeancePlanifiee[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->leftJoin('s.formateurs', 'f')
            ->addSelect('sess', 'sm', 'm', 'sal', 'f')
            ->where('s.date = :date')
            ->setParameter('date', $date)
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les séances d'une période
     * 
     * @return SeancePlanifiee[]
     */
    public function findByPeriode(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?Session $session = null
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->leftJoin('s.formateurs', 'f')
            ->addSelect('sess', 'sm', 'm', 'sal', 'f')
            ->where('s.date >= :dateDebut')
            ->andWhere('s.date <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.heureDebut', 'ASC');

        if ($session !== null) {
            $qb->andWhere('s.session = :session')
                ->setParameter('session', $session);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les séances générées depuis un créneau (non modifiées)
     * 
     * @return SeancePlanifiee[]
     */
    public function findByCreneauNonModifiees(CreneauRecurrent $creneau): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.creneauRecurrent = :creneau')
            ->andWhere('s.modifieeDepuisCreneau = false')
            ->setParameter('creneau', $creneau)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les séances d'un créneau
     * 
     * @return SeancePlanifiee[]
     */
    public function findByCreneau(CreneauRecurrent $creneau): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.creneauRecurrent = :creneau')
            ->setParameter('creneau', $creneau)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une séance existe déjà pour un créneau et une date
     */
    public function existsPourCreneauEtDate(
        CreneauRecurrent $creneau,
        \DateTimeInterface $date
    ): bool {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.creneauRecurrent = :creneau')
            ->andWhere('s.date = :date')
            ->setParameter('creneau', $creneau)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Détecte les conflits de salle pour une date et un horaire
     */
    public function findConflitSalle(
        Salle $salle,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeId = null
    ): ?SeancePlanifiee {
        $qb = $this->createQueryBuilder('s')
            ->where('s.salle = :salle')
            ->andWhere('s.date = :date')
            ->andWhere('s.statut != :statutAnnule')
            // Chevauchement des horaires
            ->andWhere('s.heureDebut < :heureFin')
            ->andWhere('s.heureFin > :heureDebut')
            ->setParameter('salle', $salle)
            ->setParameter('date', $date)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->setMaxResults(1);

        if ($excludeId !== null) {
            $qb->andWhere('s.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Détecte les conflits de formateur pour une date et un horaire
     */
    public function findConflitFormateur(
        User $formateur,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeId = null
    ): ?SeancePlanifiee {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.formateurs', 'f')
            ->where(':formateur MEMBER OF s.formateurs')
            ->andWhere('s.date = :date')
            ->andWhere('s.statut != :statutAnnule')
            // Chevauchement des horaires
            ->andWhere('s.heureDebut < :heureFin')
            ->andWhere('s.heureFin > :heureDebut')
            ->setParameter('formateur', $formateur)
            ->setParameter('date', $date)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->setMaxResults(1);

        if ($excludeId !== null) {
            $qb->andWhere('s.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Compte les séances par date pour un mois donné
     * 
     * @return array<string, int> [date => count]
     */
    public function countByDateForMonth(int $annee, int $mois): array
    {
        $debut = new \DateTime(sprintf('%d-%02d-01', $annee, $mois));
        $fin = (clone $debut)->modify('last day of this month');

        $result = $this->createQueryBuilder('s')
            ->select('s.date as dateSeance, COUNT(s.id) as cnt')
            ->where('s.date >= :debut')
            ->andWhere('s.date <= :fin')
            ->andWhere('s.statut != :statutAnnule')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->groupBy('s.date')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $dateStr = $row['dateSeance']->format('Y-m-d');
            $counts[$dateStr] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Supprime les séances non modifiées d'un créneau
     * 
     * @return int Nombre de séances supprimées
     */
    public function deleteNonModifieesForCreneau(CreneauRecurrent $creneau): int
    {
        // On doit d'abord supprimer les relations ManyToMany
        $seances = $this->findByCreneauNonModifiees($creneau);
        $count = 0;
        
        foreach ($seances as $seance) {
            $this->getEntityManager()->remove($seance);
            $count++;
        }
        
        if ($count > 0) {
            $this->getEntityManager()->flush();
        }
        
        return $count;
    }

    /**
     * Compte les séances d'un créneau
     */
    public function countByCreneau(CreneauRecurrent $creneau): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.creneauRecurrent = :creneau')
            ->setParameter('creneau', $creneau)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les séances d'une session par statut
     * 
     * @return array<string, int> [statut => count]
     */
    public function countByStatutForSession(Session $session): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('s.statut, COUNT(s.id) as cnt')
            ->where('s.session = :session')
            ->setParameter('session', $session)
            ->groupBy('s.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['statut']->value] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Trouve les prochaines séances (à partir d'aujourd'hui)
     * 
     * @return SeancePlanifiee[]
     */
    public function findProchaines(?Session $session = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->addSelect('sess', 'sm', 'm', 'sal')
            ->where('s.date >= :today')
            ->andWhere('s.statut != :statutAnnule')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.heureDebut', 'ASC')
            ->setMaxResults($limit);

        if ($session !== null) {
            $qb->andWhere('s.session = :session')
                ->setParameter('session', $session);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Sauvegarde une séance
     */
    public function save(SeancePlanifiee $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une séance
     */
    public function remove(SeancePlanifiee $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ========================================
    // MÉTHODES POUR LE PLANNING FORMATEUR
    // ========================================

    /**
     * Trouve les séances d'un formateur pour une période donnée
     * 
     * @return SeancePlanifiee[]
     */
    public function findByFormateurAndPeriode(
        User $formateur,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): array {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->leftJoin('s.formateurs', 'f')
            ->addSelect('sess', 'sm', 'm', 'sal', 'f')
            ->where(':formateur MEMBER OF s.formateurs')
            ->andWhere('s.date >= :dateDebut')
            ->andWhere('s.date <= :dateFin')
            ->setParameter('formateur', $formateur)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les séances d'un formateur pour une date précise
     * 
     * @return SeancePlanifiee[]
     */
    public function findByFormateurAndDate(User $formateur, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->leftJoin('s.formateurs', 'f')
            ->addSelect('sess', 'sm', 'm', 'sal', 'f')
            ->where(':formateur MEMBER OF s.formateurs')
            ->andWhere('s.date = :date')
            ->setParameter('formateur', $formateur)
            ->setParameter('date', $date)
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les séances par jour pour un formateur sur un mois
     * 
     * @return array<string, int> [date => count]
     */
    public function countByDateForFormateurAndMonth(User $formateur, int $annee, int $mois): array
    {
        $debut = new \DateTime(sprintf('%d-%02d-01', $annee, $mois));
        $fin = (clone $debut)->modify('last day of this month');

        $result = $this->createQueryBuilder('s')
            ->select('s.date as dateSeance, COUNT(s.id) as cnt')
            ->leftJoin('s.formateurs', 'f')
            ->where(':formateur MEMBER OF s.formateurs')
            ->andWhere('s.date >= :debut')
            ->andWhere('s.date <= :fin')
            ->andWhere('s.statut != :statutAnnule')
            ->setParameter('formateur', $formateur)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->groupBy('s.date')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $dateStr = $row['dateSeance']->format('Y-m-d');
            $counts[$dateStr] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Trouve les prochaines séances d'un formateur
     * 
     * @return SeancePlanifiee[]
     */
    public function findProchainesForFormateur(User $formateur, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.session', 'sess')
            ->leftJoin('s.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('s.salle', 'sal')
            ->addSelect('sess', 'sm', 'm', 'sal')
            ->where(':formateur MEMBER OF s.formateurs')
            ->andWhere('s.date >= :today')
            ->andWhere('s.statut != :statutAnnule')
            ->setParameter('formateur', $formateur)
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('statutAnnule', StatutSeance::ANNULEE)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.heureDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques mensuelles pour un formateur
     * 
     * @return array{total: int, heures: float, parStatut: array<string, int>}
     */
    public function getStatistiquesMensuelles(User $formateur, int $annee, int $mois): array
    {
        $debut = new \DateTime(sprintf('%d-%02d-01', $annee, $mois));
        $fin = (clone $debut)->modify('last day of this month');

        $seances = $this->findByFormateurAndPeriode($formateur, $debut, $fin);

        $stats = [
            'total' => count($seances),
            'heures' => 0,
            'parStatut' => [],
        ];

        foreach ($seances as $seance) {
            $statut = $seance->getStatut()->value;
            if (!isset($stats['parStatut'][$statut])) {
                $stats['parStatut'][$statut] = 0;
            }
            $stats['parStatut'][$statut]++;

            if ($seance->getStatut() !== StatutSeance::ANNULEE) {
                $stats['heures'] += $seance->getDureeMinutes() / 60;
            }
        }

        $stats['heures'] = round($stats['heures'], 1);

        return $stats;
    }
}
