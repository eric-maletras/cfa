<?php

namespace App\Repository;

use App\Entity\CreneauRecurrent;
use App\Entity\Salle;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité CreneauRecurrent
 * 
 * Gère les requêtes de recherche, groupement et détection de conflits
 * pour les créneaux horaires récurrents.
 * 
 * @extends ServiceEntityRepository<CreneauRecurrent>
 */
class CreneauRecurrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreneauRecurrent::class);
    }

    /**
     * Trouve tous les créneaux d'une session
     * 
     * @param Session $session La session concernée
     * @param bool $actifsUniquement Ne retourner que les créneaux actifs
     * @return CreneauRecurrent[]
     */
    public function findBySession(Session $session, bool $actifsUniquement = true): array
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

        if ($actifsUniquement) {
            $qb->andWhere('c.actif = :actif')
               ->setParameter('actif', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les créneaux groupés par session
     * 
     * Retourne un tableau de tableaux avec la session et ses créneaux.
     * Utilisé pour l'affichage dans la liste principale.
     * 
     * @param bool $actifsUniquement Ne retourner que les créneaux actifs
     * @return array<int, array{session: Session, creneaux: CreneauRecurrent[]}>
     */
    public function findAllGroupedBySession(bool $actifsUniquement = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.session', 'sess')
            ->leftJoin('sess.formation', 'form')
            ->leftJoin('c.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->leftJoin('c.salle', 's')
            ->leftJoin('c.formateurs', 'f')
            ->addSelect('sess', 'form', 'sm', 'm', 's', 'f')
            ->orderBy('sess.dateDebut', 'DESC')
            ->addOrderBy('c.jourSemaine', 'ASC')
            ->addOrderBy('c.heureDebut', 'ASC');

        if ($actifsUniquement) {
            $qb->andWhere('c.actif = :actif')
               ->setParameter('actif', true);
        }

        $creneaux = $qb->getQuery()->getResult();

        // Grouper par session
        $grouped = [];
        foreach ($creneaux as $creneau) {
            $sessionId = $creneau->getSession()?->getId();
            if ($sessionId === null) {
                continue;
            }
            
            if (!isset($grouped[$sessionId])) {
                $grouped[$sessionId] = [
                    'session' => $creneau->getSession(),
                    'creneaux' => [],
                ];
            }
            $grouped[$sessionId]['creneaux'][] = $creneau;
        }

        return array_values($grouped);
    }

    /**
     * Trouve les créneaux d'un jour donné pour une session
     * 
     * @param Session $session La session concernée
     * @param JourSemaine $jour Le jour de la semaine
     * @return CreneauRecurrent[]
     */
    public function findBySessionAndJour(Session $session, JourSemaine $jour): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.session = :session')
            ->andWhere('c.jourSemaine = :jour')
            ->andWhere('c.actif = :actif')
            ->setParameter('session', $session)
            ->setParameter('jour', $jour)
            ->setParameter('actif', true)
            ->orderBy('c.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Détecte les conflits de salle avec d'autres créneaux
     * 
     * Vérifie si la salle est déjà occupée par un autre créneau
     * sur le même jour, aux mêmes horaires et pendant la même période.
     * 
     * @param Salle $salle La salle à vérifier
     * @param JourSemaine $jourSemaine Le jour de la semaine
     * @param \DateTimeInterface $heureDebut Heure de début du créneau
     * @param \DateTimeInterface $heureFin Heure de fin du créneau
     * @param \DateTimeInterface $dateDebut Date de début de la période
     * @param \DateTimeInterface $dateFin Date de fin de la période
     * @param SemaineType|null $semaineType Type de semaine (A, B ou null pour toutes)
     * @param int|null $excludeId ID du créneau à exclure (pour l'édition)
     * @return CreneauRecurrent[] Créneaux en conflit
     */
    public function findConflitsSalle(
        Salle $salle,
        JourSemaine $jourSemaine,
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
            ->andWhere('c.actif = :actif')
            // Chevauchement horaire : (debut1 < fin2) AND (fin1 > debut2)
            ->andWhere('c.heureDebut < :heureFin')
            ->andWhere('c.heureFin > :heureDebut')
            // Chevauchement de période
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            ->setParameter('salle', $salle)
            ->setParameter('jour', $jourSemaine)
            ->setParameter('actif', true)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        // Exclure l'ID pour l'édition
        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        // Gérer le type de semaine
        // Un conflit existe si : 
        // - l'un des deux est "toutes semaines" (null)
        // - ou les deux ont le même type de semaine
        if ($semaineType !== null) {
            $qb->andWhere('c.semaineType IS NULL OR c.semaineType = :semaineType')
               ->setParameter('semaineType', $semaineType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Détecte les conflits de formateur avec d'autres créneaux
     * 
     * Vérifie si le formateur est déjà affecté à un autre créneau
     * sur le même jour, aux mêmes horaires et pendant la même période.
     * 
     * @param User $formateur Le formateur à vérifier
     * @param JourSemaine $jourSemaine Le jour de la semaine
     * @param \DateTimeInterface $heureDebut Heure de début du créneau
     * @param \DateTimeInterface $heureFin Heure de fin du créneau
     * @param \DateTimeInterface $dateDebut Date de début de la période
     * @param \DateTimeInterface $dateFin Date de fin de la période
     * @param SemaineType|null $semaineType Type de semaine (A, B ou null pour toutes)
     * @param int|null $excludeId ID du créneau à exclure (pour l'édition)
     * @return CreneauRecurrent[] Créneaux en conflit
     */
    public function findConflitsFormateur(
        User $formateur,
        JourSemaine $jourSemaine,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?SemaineType $semaineType = null,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.formateurs', 'f')
            ->where('f = :formateur')
            ->andWhere('c.jourSemaine = :jour')
            ->andWhere('c.actif = :actif')
            // Chevauchement horaire
            ->andWhere('c.heureDebut < :heureFin')
            ->andWhere('c.heureFin > :heureDebut')
            // Chevauchement de période
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            ->setParameter('formateur', $formateur)
            ->setParameter('jour', $jourSemaine)
            ->setParameter('actif', true)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        // Gérer le type de semaine
        if ($semaineType !== null) {
            $qb->andWhere('c.semaineType IS NULL OR c.semaineType = :semaineType')
               ->setParameter('semaineType', $semaineType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les créneaux par session
     * 
     * @return array<int, int> [sessionId => count]
     */
    public function countBySession(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.session) as sessionId, COUNT(c.id) as total')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('c.session')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['sessionId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Trouve les créneaux actifs dont la période inclut aujourd'hui
     * 
     * @return CreneauRecurrent[]
     */
    public function findActifs(): array
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('c')
            ->leftJoin('c.session', 'sess')
            ->leftJoin('c.sessionMatiere', 'sm')
            ->leftJoin('sm.matiere', 'm')
            ->addSelect('sess', 'sm', 'm')
            ->where('c.actif = :actif')
            ->andWhere('c.dateDebut <= :today')
            ->andWhere('c.dateFin >= :today')
            ->setParameter('actif', true)
            ->setParameter('today', $today)
            ->orderBy('c.jourSemaine', 'ASC')
            ->addOrderBy('c.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales des créneaux
     * 
     * @return array{total: int, actifs: int, parJour: array<string, int>}
     */
    public function getStatistiques(): array
    {
        $total = $this->count([]);
        $actifs = $this->count(['actif' => true]);

        // Comptage par jour de la semaine
        $parJour = $this->createQueryBuilder('c')
            ->select('c.jourSemaine as jour, COUNT(c.id) as total')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('c.jourSemaine')
            ->getQuery()
            ->getResult();

        $joursCount = [];
        foreach ($parJour as $row) {
            // Le jour est stocké comme valeur de l'enum
            $joursCount[$row['jour']->value] = (int) $row['total'];
        }

        return [
            'total' => $total,
            'actifs' => $actifs,
            'parJour' => $joursCount,
        ];
    }

    /**
     * Persiste un créneau
     */
    public function save(CreneauRecurrent $creneau, bool $flush = false): void
    {
        $this->getEntityManager()->persist($creneau);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un créneau
     */
    public function remove(CreneauRecurrent $creneau, bool $flush = false): void
    {
        $this->getEntityManager()->remove($creneau);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
