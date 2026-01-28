<?php

namespace App\Repository;

use App\Entity\CreneauRecurrent;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\Salle;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreneauRecurrent>
 */
class CreneauRecurrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreneauRecurrent::class);
    }

    /**
     * Trouve les créneaux actifs d'une session
     * 
     * @return CreneauRecurrent[]
     */
    public function findBySession(Session $session, bool $actifSeulement = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('c.salle', 's')
            ->leftJoin('c.formateurs', 'f')
            ->addSelect('sm', 'm', 's', 'f')
            ->where('c.session = :session')
            ->setParameter('session', $session)
            ->orderBy('c.jourSemaine', 'ASC')
            ->addOrderBy('c.heureDebut', 'ASC');

        if ($actifSeulement) {
            $qb->andWhere('c.actif = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les créneaux pour un jour et une période donnés
     * 
     * @return CreneauRecurrent[]
     */
    public function findByJourEtPeriode(
        JourSemaine $jour,
        \DateTimeInterface $date
    ): array {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.session', 's')
            ->leftJoin('c.salle', 'sal')
            ->addSelect('s', 'sal')
            ->where('c.jourSemaine = :jour')
            ->andWhere('c.dateDebut <= :date')
            ->andWhere('c.dateFin >= :date')
            ->andWhere('c.actif = true')
            ->setParameter('jour', $jour->value)
            ->setParameter('date', $date)
            ->orderBy('c.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les créneaux potentiellement en conflit avec une salle
     * 
     * @return CreneauRecurrent[]
     */
    public function findConflitsSalle(
        Salle $salle,
        JourSemaine $jour,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?SemaineType $semaineType = null,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->where('c.salle = :salle')
            ->andWhere('c.jourSemaine = :jour')
            ->andWhere('c.actif = true')
            // Chevauchement des périodes
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            // Chevauchement des horaires
            ->andWhere('c.heureDebut < :heureFin')
            ->andWhere('c.heureFin > :heureDebut')
            ->setParameter('salle', $salle)
            ->setParameter('jour', $jour->value)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les créneaux potentiellement en conflit pour un formateur
     * 
     * @return CreneauRecurrent[]
     */
    public function findConflitsFormateur(
        User $formateur,
        JourSemaine $jour,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?SemaineType $semaineType = null,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.formateurs', 'f')
            ->where(':formateur MEMBER OF c.formateurs')
            ->andWhere('c.jourSemaine = :jour')
            ->andWhere('c.actif = true')
            // Chevauchement des périodes
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            // Chevauchement des horaires
            ->andWhere('c.heureDebut < :heureFin')
            ->andWhere('c.heureFin > :heureDebut')
            ->setParameter('formateur', $formateur)
            ->setParameter('jour', $jour->value)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de créneaux par session
     * 
     * @return array<int, int> [sessionId => count]
     */
    public function countBySession(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.session) as sessionId, COUNT(c.id) as cnt')
            ->where('c.actif = true')
            ->groupBy('c.session')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['sessionId']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Compte le nombre de créneaux pour une session
     */
    public function countForSession(Session $session): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.session = :session')
            ->andWhere('c.actif = true')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sauvegarde un créneau
     */
    public function save(CreneauRecurrent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un créneau
     */
    public function remove(CreneauRecurrent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
