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
     * Inclut uniquement les appels clôturés
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
            ->andWhere('a.cloture = true')
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
        $presences = $this->findByAppel($appel);

        $counts = [];
        foreach (StatutPresence::cases() as $statut) {
            $counts[$statut->value] = 0;
        }
        
        foreach ($presences as $presence) {
            $statutValue = $presence->getStatut()->value;
            if (isset($counts[$statutValue])) {
                $counts[$statutValue]++;
            }
        }

        return $counts;
    }

    /**
     * Trouve les statistiques de présence pour un apprenti
     * 
     * Utilise le comptage PHP pour éviter les problèmes de comparaison d'enum en DQL
     */
    public function getStatistiquesApprenti(
        User $apprenti, 
        ?\DateTimeInterface $dateDebut = null, 
        ?\DateTimeInterface $dateFin = null
    ): array {
        // Récupérer les présences clôturées
        $qb = $this->createQueryBuilder('p')
            ->join('p.appel', 'a')
            ->join('a.seance', 's')
            ->andWhere('p.apprenti = :apprenti')
            ->andWhere('a.cloture = true')
            ->setParameter('apprenti', $apprenti);

        if ($dateDebut) {
            $qb->andWhere('s.date >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('s.date <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        $presences = $qb->getQuery()->getResult();
        
        // Comptage en PHP (plus fiable que les CASE WHEN DQL avec enums)
        $total = count($presences);
        $presents = 0;
        $absents = 0;
        $absentsJustifies = 0;
        $retards = 0;
        $nonSignes = 0;
        
        foreach ($presences as $presence) {
            $statut = $presence->getStatut();
            
            switch ($statut) {
                case StatutPresence::PRESENT:
                    $presents++;
                    break;
                case StatutPresence::RETARD:
                    $presents++; // Un retard compte comme présent
                    $retards++;
                    break;
                case StatutPresence::ABSENT:
                    $absents++;
                    break;
                case StatutPresence::ABSENT_JUSTIFIE:
                    $absentsJustifies++;
                    break;
                case StatutPresence::NON_SIGNE:
                    $nonSignes++;
                    break;
                case StatutPresence::EN_ATTENTE:
                    // En attente n'est pas compté dans les stats finales
                    break;
            }
        }
        
        // Calculer le taux de présence
        $tauxPresence = $total > 0 ? round(($presents / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'presents' => $presents,
            'absents' => $absents,
            'absentsJustifies' => $absentsJustifies,
            'retards' => $retards,
            'nonSignes' => $nonSignes,
            'tauxPresence' => $tauxPresence,
        ];
    }

    /**
     * Génère le rapport d'heures d'absence par apprenti
     * 
     * @param int|null $formationId Filtrer par formation
     * @param int|null $sessionId Filtrer par session
     * @param \DateTimeInterface|null $dateDebut Date de début de période
     * @param \DateTimeInterface|null $dateFin Date de fin de période
     * @param float|null $seuilMinimum Seuil minimum d'heures d'absence (non justifiées)
     * @return array Tableau de données par apprenti
     */
    public function getRapportHeuresAbsence(
        ?int $formationId = null,
        ?int $sessionId = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?float $seuilMinimum = null
    ): array {
        // Requête de base pour récupérer les présences avec leurs séances
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'a', 's', 'ap', 'sess', 'f')
            ->join('p.appel', 'a')
            ->join('a.seance', 's')
            ->join('p.apprenti', 'ap')
            ->join('s.session', 'sess')
            ->join('sess.formation', 'f')
            ->andWhere('a.cloture = true')
            ->orderBy('ap.nom', 'ASC')
            ->addOrderBy('ap.prenom', 'ASC');

        // Filtres optionnels
        if ($sessionId) {
            $qb->andWhere('sess.id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        } elseif ($formationId) {
            $qb->andWhere('f.id = :formationId')
               ->setParameter('formationId', $formationId);
        }

        if ($dateDebut) {
            $qb->andWhere('s.date >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('s.date <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        $presences = $qb->getQuery()->getResult();

        // Agréger par apprenti
        $rapportParApprenti = [];
        
        foreach ($presences as $presence) {
            $apprenti = $presence->getApprenti();
            $apprentiId = $apprenti->getId();
            $seance = $presence->getAppel()->getSeance();
            $session = $seance->getSession();
            $dureeMinutes = $seance->getDureeMinutes();
            $statut = $presence->getStatut();

            // Initialiser l'entrée si nécessaire
            if (!isset($rapportParApprenti[$apprentiId])) {
                $rapportParApprenti[$apprentiId] = [
                    'apprenti' => $apprenti,
                    'formation' => $session->getFormation()->getIntitule(),
                    'session' => $session->getCode(),
                    'sessionId' => $session->getId(),
                    'totalSeances' => 0,
                    'totalMinutes' => 0,
                    'presents' => 0,
                    'presentsMinutes' => 0,
                    'retards' => 0,
                    'retardsMinutes' => 0,
                    'minutesRetardCumul' => 0,
                    'absents' => 0,
                    'absentsMinutes' => 0,
                    'absentsJustifies' => 0,
                    'absentsJustifiesMinutes' => 0,
                ];
            }

            $entry = &$rapportParApprenti[$apprentiId];
            $entry['totalSeances']++;
            $entry['totalMinutes'] += $dureeMinutes;

            switch ($statut) {
                case StatutPresence::PRESENT:
                    $entry['presents']++;
                    $entry['presentsMinutes'] += $dureeMinutes;
                    break;
                case StatutPresence::RETARD:
                    $entry['retards']++;
                    $entry['retardsMinutes'] += $dureeMinutes;
                    $entry['minutesRetardCumul'] += $presence->getMinutesRetard() ?? 0;
                    break;
                case StatutPresence::ABSENT:
                    $entry['absents']++;
                    $entry['absentsMinutes'] += $dureeMinutes;
                    break;
                case StatutPresence::ABSENT_JUSTIFIE:
                    $entry['absentsJustifies']++;
                    $entry['absentsJustifiesMinutes'] += $dureeMinutes;
                    break;
            }
        }

        // Calculer les totaux et filtrer par seuil
        $rapport = [];
        foreach ($rapportParApprenti as $apprentiId => $data) {
            // Heures d'absence (non justifiées uniquement)
            $heuresAbsence = round($data['absentsMinutes'] / 60, 1);
            
            // Heures d'absence totales (justifiées + non justifiées)
            $heuresAbsenceTotales = round(($data['absentsMinutes'] + $data['absentsJustifiesMinutes']) / 60, 1);
            
            // Heures de retard cumulées
            $heuresRetard = round($data['minutesRetardCumul'] / 60, 1);
            
            // Taux de présence (présents + retards comptent comme présents)
            $tauxPresence = $data['totalSeances'] > 0 
                ? round((($data['presents'] + $data['retards']) / $data['totalSeances']) * 100, 1) 
                : 0;

            // Filtrer par seuil si demandé
            if ($seuilMinimum !== null && $heuresAbsence < $seuilMinimum) {
                continue;
            }

            $rapport[] = [
                'apprenti' => $data['apprenti'],
                'formation' => $data['formation'],
                'session' => $data['session'],
                'sessionId' => $data['sessionId'],
                'totalSeances' => $data['totalSeances'],
                'totalHeures' => round($data['totalMinutes'] / 60, 1),
                'presents' => $data['presents'],
                'retards' => $data['retards'],
                'absents' => $data['absents'],
                'absentsJustifies' => $data['absentsJustifies'],
                'heuresAbsence' => $heuresAbsence,
                'heuresAbsenceTotales' => $heuresAbsenceTotales,
                'heuresRetard' => $heuresRetard,
                'tauxPresence' => $tauxPresence,
            ];
        }

        // Trier par heures d'absence décroissant
        usort($rapport, fn($a, $b) => $b['heuresAbsence'] <=> $a['heuresAbsence']);

        return $rapport;
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
