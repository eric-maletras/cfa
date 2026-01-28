<?php

namespace App\Repository;

use App\Entity\CalendrierAnnee;
use App\Entity\JourFerme;
use App\Enum\TypeJourFerme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité JourFerme
 *
 * @extends ServiceEntityRepository<JourFerme>
 */
class JourFermeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JourFerme::class);
    }

    /**
     * Retourne les jours fermés d'un calendrier dans une période donnée
     * 
     * @return JourFerme[]
     */
    public function findByCalendrierAndPeriode(
        CalendrierAnnee $calendrier,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.calendrier = :calendrier')
            ->setParameter('calendrier', $calendrier)
            ->orderBy('j.date', 'ASC');

        if ($dateDebut !== null) {
            $qb->andWhere('j.date >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin !== null) {
            $qb->andWhere('j.date <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche un jour fermé par date (tous calendriers confondus)
     */
    public function findByDate(\DateTimeInterface $date): ?JourFerme
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche un jour fermé par date dans un calendrier spécifique
     */
    public function findOneByCalendrierAndDate(
        CalendrierAnnee $calendrier,
        \DateTimeInterface $date
    ): ?JourFerme {
        return $this->createQueryBuilder('j')
            ->andWhere('j.calendrier = :calendrier')
            ->andWhere('j.date = :date')
            ->setParameter('calendrier', $calendrier)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les jours fermés d'un calendrier groupés par mois
     * 
     * @return array<string, JourFerme[]> Clé au format 'YYYY-MM'
     */
    public function findByCalendrierGroupedByMonth(CalendrierAnnee $calendrier): array
    {
        $joursFermes = $this->findByCalendrierAndPeriode($calendrier);
        $grouped = [];

        foreach ($joursFermes as $jour) {
            $key = $jour->getDate()->format('Y-m');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $jour;
        }

        return $grouped;
    }

    /**
     * Retourne les jours fermés d'un type donné pour un calendrier
     * 
     * @return JourFerme[]
     */
    public function findByCalendrierAndType(
        CalendrierAnnee $calendrier,
        TypeJourFerme $type
    ): array {
        return $this->createQueryBuilder('j')
            ->andWhere('j.calendrier = :calendrier')
            ->andWhere('j.type = :type')
            ->setParameter('calendrier', $calendrier)
            ->setParameter('type', $type)
            ->orderBy('j.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les jours fermés par type pour un calendrier
     * 
     * @return array<string, int>
     */
    public function countByTypeForCalendrier(CalendrierAnnee $calendrier): array
    {
        $result = $this->createQueryBuilder('j')
            ->select('j.type, COUNT(j.id) as count')
            ->andWhere('j.calendrier = :calendrier')
            ->setParameter('calendrier', $calendrier)
            ->groupBy('j.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach (TypeJourFerme::cases() as $type) {
            $counts[$type->value] = 0;
        }

        foreach ($result as $row) {
            $counts[$row['type']->value] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Vérifie si une date existe déjà dans le calendrier (hors entité courante)
     */
    public function existsForCalendrierAndDate(
        CalendrierAnnee $calendrier,
        \DateTimeInterface $date,
        ?int $excludeId = null
    ): bool {
        $qb = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.calendrier = :calendrier')
            ->andWhere('j.date = :date')
            ->setParameter('calendrier', $calendrier)
            ->setParameter('date', $date->format('Y-m-d'));

        if ($excludeId !== null) {
            $qb->andWhere('j.id != :id')
               ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Supprime tous les jours fériés d'un calendrier
     * Utile avant une réimportation
     */
    public function deleteJoursFeriesByCalendrier(CalendrierAnnee $calendrier): int
    {
        return $this->createQueryBuilder('j')
            ->delete()
            ->where('j.calendrier = :calendrier')
            ->andWhere('j.type = :type')
            ->setParameter('calendrier', $calendrier)
            ->setParameter('type', TypeJourFerme::FERIE)
            ->getQuery()
            ->execute();
    }

    /**
     * Retourne les prochains jours fermés à partir d'aujourd'hui
     * 
     * @return JourFerme[]
     */
    public function findUpcoming(CalendrierAnnee $calendrier, int $limit = 5): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.calendrier = :calendrier')
            ->andWhere('j.date >= :today')
            ->setParameter('calendrier', $calendrier)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('j.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les jours fermés pour un mois donné
     * 
     * @return JourFerme[]
     */
    public function findByCalendrierAndMonth(
        CalendrierAnnee $calendrier,
        int $annee,
        int $mois
    ): array {
        $dateDebut = new \DateTimeImmutable(sprintf('%04d-%02d-01', $annee, $mois));
        $dateFin = $dateDebut->modify('last day of this month');

        return $this->findByCalendrierAndPeriode($calendrier, $dateDebut, $dateFin);
    }
}
