<?php

namespace App\Service;

use App\Entity\CreneauRecurrent;
use App\Entity\SeancePlanifiee;
use App\Entity\JourFerme;
use App\Enum\SemaineType;
use App\Enum\StatutSeance;
use App\Repository\CalendrierAnneeRepository;
use App\Repository\JourFermeRepository;
use App\Repository\SeancePlanifieeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de génération des séances planifiées
 * 
 * Transforme les créneaux récurrents en séances concrètes
 * en tenant compte du calendrier, des jours fermés et du type de semaine.
 */
class GenerateurSeancesService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CalendrierAnneeRepository $calendrierRepository,
        private JourFermeRepository $jourFermeRepository,
        private SeancePlanifieeRepository $seanceRepository,
    ) {
    }

    /**
     * Génère la prévisualisation des séances pour un créneau
     * 
     * @return array{dates: array, joursFermes: array, seancesExistantes: array, stats: array}
     */
    public function previsualiser(CreneauRecurrent $creneau): array
    {
        $dates = $this->calculerDates($creneau);
        $joursFermesParDate = $this->getJoursFermes($creneau);
        $seancesExistantes = $this->getSeancesExistantes($creneau);
        
        $result = [
            'dates' => [],
            'joursFermes' => [],
            'seancesExistantes' => [],
            'stats' => [
                'total' => 0,
                'aCreer' => 0,
                'existantes' => 0,
                'joursFermes' => 0,
            ],
        ];
        
        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            $info = [
                'date' => $date,
                'dateStr' => $dateStr,
                'jourSemaine' => $this->getNomJour($date),
                'estJourFerme' => false,
                'jourFerme' => null,
                'seanceExistante' => null,
                'action' => 'creer', // creer, existante, jour_ferme
            ];
            
            // Vérifier si jour fermé
            if (isset($joursFermesParDate[$dateStr])) {
                $info['estJourFerme'] = true;
                $info['jourFerme'] = $joursFermesParDate[$dateStr];
                $info['action'] = 'jour_ferme';
                $result['stats']['joursFermes']++;
            }
            // Vérifier si séance existante
            elseif (isset($seancesExistantes[$dateStr])) {
                $info['seanceExistante'] = $seancesExistantes[$dateStr];
                $info['action'] = 'existante';
                $result['stats']['existantes']++;
            }
            else {
                $result['stats']['aCreer']++;
            }
            
            $result['dates'][] = $info;
            $result['stats']['total']++;
        }
        
        return $result;
    }

    /**
     * Génère les séances planifiées pour un créneau
     * 
     * @param bool $regenerer Si true, supprime les séances non modifiées avant de régénérer
     * @return array{creees: int, ignorees: int, supprimees: int}
     */
    public function generer(CreneauRecurrent $creneau, bool $regenerer = false): array
    {
        $stats = [
            'creees' => 0,
            'ignorees' => 0,
            'supprimees' => 0,
        ];
        
        // Si régénération, supprimer les séances non modifiées manuellement
        if ($regenerer) {
            $stats['supprimees'] = $this->supprimerSeancesNonModifiees($creneau);
        }
        
        $dates = $this->calculerDates($creneau);
        $joursFermesParDate = $this->getJoursFermes($creneau);
        $seancesExistantes = $this->getSeancesExistantes($creneau);
        
        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            
            // Ignorer les jours fermés
            if (isset($joursFermesParDate[$dateStr])) {
                $stats['ignorees']++;
                continue;
            }
            
            // Ignorer si séance existante
            if (isset($seancesExistantes[$dateStr])) {
                $stats['ignorees']++;
                continue;
            }
            
            // Créer la séance
            $seance = $this->creerSeance($creneau, $date);
            $this->em->persist($seance);
            $stats['creees']++;
        }
        
        $this->em->flush();
        
        return $stats;
    }

    /**
     * Calcule toutes les dates correspondant au créneau
     * 
     * @return \DateTime[]
     */
    private function calculerDates(CreneauRecurrent $creneau): array
    {
        $dates = [];
        
        $dateDebut = clone $creneau->getDateDebut();
        $dateFin = clone $creneau->getDateFin();
        $jourCible = $creneau->getJourSemaine()->value; // 1=lundi, 7=dimanche
        $semaineType = $creneau->getSemaineType();
        
        // Trouver le premier jour correspondant
        $current = clone $dateDebut;
        $currentJour = (int) $current->format('N');
        
        if ($currentJour !== $jourCible) {
            $diff = $jourCible - $currentJour;
            if ($diff < 0) {
                $diff += 7;
            }
            $current->modify("+{$diff} days");
        }
        
        // Récupérer la semaine de référence pour déterminer A/B
        $semaineReference = $this->getSemaineReference($creneau);
        
        // Parcourir toutes les semaines
        while ($current <= $dateFin) {
            // Vérifier le type de semaine si défini
            if ($semaineType !== null) {
                $typeSemaineCourante = $this->getTypeSemaine($current, $semaineReference);
                if ($typeSemaineCourante !== $semaineType) {
                    $current->modify('+7 days');
                    continue;
                }
            }
            
            $dates[] = clone $current;
            $current->modify('+7 days');
        }
        
        return $dates;
    }

    /**
     * Détermine le type de semaine (A ou B) pour une date donnée
     */
    private function getTypeSemaine(\DateTime $date, ?\DateTime $semaineReference): ?SemaineType
    {
        if ($semaineReference === null) {
            // Par défaut, utiliser le numéro de semaine : pair = A, impair = B
            $weekNumber = (int) $date->format('W');
            return ($weekNumber % 2 === 0) ? SemaineType::A : SemaineType::B;
        }
        
        // Calculer le nombre de semaines depuis la référence
        $interval = $semaineReference->diff($date);
        $weeks = (int) floor($interval->days / 7);
        
        // Semaine de référence = A, donc alternance
        return ($weeks % 2 === 0) ? SemaineType::A : SemaineType::B;
    }

    /**
     * Récupère la semaine de référence depuis le calendrier actif
     * Retourne null si pas de référence définie (utilise alors le numéro de semaine)
     */
    private function getSemaineReference(CreneauRecurrent $creneau): ?\DateTime
    {
        $calendrier = $this->calendrierRepository->findActif();
        if (!$calendrier) {
            return null;
        }
        
        // Vérifier si le calendrier a une propriété semaineReferenceA
        // Sinon utiliser le calcul par défaut basé sur le numéro de semaine
        if (method_exists($calendrier, 'getSemaineReferenceA') && $calendrier->getSemaineReferenceA()) {
            return $calendrier->getSemaineReferenceA();
        }
        
        return null;
    }

    /**
     * Récupère les jours fermés sur la période du créneau
     * 
     * @return array<string, JourFerme> [Y-m-d => JourFerme]
     */
    private function getJoursFermes(CreneauRecurrent $creneau): array
    {
        $calendrier = $this->calendrierRepository->findActif();
        if (!$calendrier) {
            return [];
        }
        
        $joursFermes = $this->jourFermeRepository->findByCalendrierAndPeriode(
            $calendrier,
            $creneau->getDateDebut(),
            $creneau->getDateFin()
        );
        
        $result = [];
        foreach ($joursFermes as $jf) {
            $result[$jf->getDate()->format('Y-m-d')] = $jf;
        }
        
        return $result;
    }

    /**
     * Récupère les séances existantes pour ce créneau
     * 
     * @return array<string, SeancePlanifiee> [Y-m-d => SeancePlanifiee]
     */
    private function getSeancesExistantes(CreneauRecurrent $creneau): array
    {
        if (!$creneau->getId()) {
            return [];
        }
        
        $seances = $this->seanceRepository->findByCreneau($creneau);
        
        $result = [];
        foreach ($seances as $seance) {
            $result[$seance->getDate()->format('Y-m-d')] = $seance;
        }
        
        return $result;
    }

    /**
     * Crée une séance planifiée à partir d'un créneau et d'une date
     */
    private function creerSeance(CreneauRecurrent $creneau, \DateTime $date): SeancePlanifiee
    {
        $seance = new SeancePlanifiee();
        
        $seance->setSession($creneau->getSession());
        $seance->setCreneauRecurrent($creneau);
        $seance->setDate($date);
        $seance->setHeureDebut($creneau->getHeureDebut());
        $seance->setHeureFin($creneau->getHeureFin());
        $seance->setSalle($creneau->getSalle());
        $seance->setSessionMatiere($creneau->getSessionMatiere());
        $seance->setStatut(StatutSeance::PLANIFIEE);
        $seance->setModifieeDepuisCreneau(false);
        
        // Ajouter les formateurs
        foreach ($creneau->getFormateurs() as $formateur) {
            $seance->addFormateur($formateur);
        }
        
        return $seance;
    }

    /**
     * Supprime les séances non modifiées manuellement pour un créneau
     * 
     * @return int Nombre de séances supprimées
     */
    private function supprimerSeancesNonModifiees(CreneauRecurrent $creneau): int
    {
        return $this->seanceRepository->deleteNonModifieesForCreneau($creneau);
    }

    /**
     * Retourne le nom du jour en français
     */
    private function getNomJour(\DateTime $date): string
    {
        $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $jours[(int) $date->format('w')];
    }

    /**
     * Compte le nombre de séances générées pour un créneau
     */
    public function countSeances(CreneauRecurrent $creneau): int
    {
        if (!$creneau->getId()) {
            return 0;
        }
        
        return $this->seanceRepository->countByCreneau($creneau);
    }

    /**
     * Vérifie si des séances ont été modifiées manuellement
     */
    public function hasSeancesModifiees(CreneauRecurrent $creneau): bool
    {
        if (!$creneau->getId()) {
            return false;
        }
        
        $seances = $this->seanceRepository->findByCreneau($creneau);
        
        foreach ($seances as $seance) {
            if ($seance->isModifieeDepuisCreneau()) {
                return true;
            }
        }
        
        return false;
    }
}
