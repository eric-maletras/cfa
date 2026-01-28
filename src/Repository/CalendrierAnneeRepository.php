<?php

namespace App\Repository;

use App\Entity\CalendrierAnnee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité CalendrierAnnee
 *
 * @extends ServiceEntityRepository<CalendrierAnnee>
 */
class CalendrierAnneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendrierAnnee::class);
    }

    /**
     * Retourne le calendrier actif
     * S'il y en a plusieurs, retourne le plus récent (dateDebut la plus récente)
     */
    public function findActif(): ?CalendrierAnnee
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si une date est un jour ouvré (non fermé) dans le calendrier
     * 
     * Un jour est ouvré si :
     * - Il est dans la période du calendrier
     * - Ce n'est pas un samedi ou dimanche
     * - Ce n'est pas un jour fermé
     */
    public function isJourOuvre(CalendrierAnnee $calendrier, \DateTimeInterface $date): bool
    {
        // Vérifier si la date est dans la période
        if (!$calendrier->contientDate($date)) {
            return false;
        }

        // Vérifier si c'est un week-end
        $jourSemaine = (int) $date->format('N');
        if ($jourSemaine >= 6) {
            return false;
        }

        // Vérifier si c'est un jour fermé
        $dateStr = $date->format('Y-m-d');
        foreach ($calendrier->getJoursFermes() as $jourFerme) {
            if ($jourFerme->getDate()->format('Y-m-d') === $dateStr) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compte le nombre de jours ouvrés dans une période
     */
    public function countJoursOuvres(
        CalendrierAnnee $calendrier,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): int {
        $count = 0;
        $current = \DateTimeImmutable::createFromInterface($dateDebut);
        $end = \DateTimeImmutable::createFromInterface($dateFin);

        while ($current <= $end) {
            if ($this->isJourOuvre($calendrier, $current)) {
                $count++;
            }
            $current = $current->modify('+1 day');
        }

        return $count;
    }

    /**
     * Retourne tous les calendriers triés par date de début (plus récent d'abord)
     * 
     * @return CalendrierAnnee[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le calendrier contenant une date donnée
     */
    public function findByDate(\DateTimeInterface $date): ?CalendrierAnnee
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateDebut <= :date')
            ->andWhere('c.dateFin >= :date')
            ->setParameter('date', $date)
            ->orderBy('c.actif', 'DESC') // Priorité au calendrier actif
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un code existe déjà (hors entité courante)
     */
    public function existsWithCode(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.code = :code')
            ->setParameter('code', $code);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :id')
               ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Retourne les années ayant des calendriers
     * 
     * @return int[]
     */
    public function getAnneesDisponibles(): array
    {
        $calendriers = $this->findAll();
        $annees = [];

        foreach ($calendriers as $calendrier) {
            $annees = array_merge($annees, $calendrier->getAnneesCouvertes());
        }

        return array_unique($annees);
    }

    /**
     * Désactive tous les calendriers sauf celui spécifié
     */
    public function desactiverAutres(CalendrierAnnee $calendrierActif): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.actif', ':actif')
            ->where('c.id != :id')
            ->setParameter('actif', false)
            ->setParameter('id', $calendrierActif->getId())
            ->getQuery()
            ->execute();
    }
}
