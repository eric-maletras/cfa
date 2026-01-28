<?php

namespace App\Service;

use App\Entity\CreneauRecurrent;
use App\Entity\SeancePlanifiee;
use App\Entity\CalendrierAnnee;
use App\Enum\JourSemaine;
use App\Repository\SeancePlanifieeRepository;
use App\Repository\JourFermeRepository;
use App\Repository\CalendrierAnneeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de génération des séances planifiées
 * 
 * Ce service génère automatiquement les SeancePlanifiee pour chaque
 * occurrence d'un CreneauRecurrent sur sa période de validité.
 * 
 * Fonctionnalités :
 * - Génère une séance pour chaque date correspondant au créneau
 * - Exclut automatiquement les jours fermés du calendrier
 * - Gère l'alternance semaine A/B
 * - Possibilité de régénérer (supprime les séances non modifiées avant)
 */
class GenerateurSeancesService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SeancePlanifieeRepository $seancePlanifieeRepository,
        private JourFermeRepository $jourFermeRepository,
        private CalendrierAnneeRepository $calendrierAnneeRepository,
    ) {
    }

    /**
     * Génère les séances pour un créneau récurrent
     * 
     * @param CreneauRecurrent $creneau Le créneau à générer
     * @param bool $regenerer Si true, supprime les séances non modifiées avant de régénérer
     * 
     * @return int Nombre de séances créées
     */
    public function generer(CreneauRecurrent $creneau, bool $regenerer = false): int
    {
        // Si régénération demandée, supprimer les séances non modifiées
        if ($regenerer) {
            $this->seancePlanifieeRepository->deleteNonModifieesForCreneau($creneau);
        }

        // Récupérer les jours fermés pour la période
        $joursFermes = $this->getJoursFermesPourPeriode(
            $creneau->getDateDebut(),
            $creneau->getDateFin()
        );

        // Générer toutes les dates d'occurrence
        $dates = $this->calculerDatesOccurrences($creneau);

        $nbCreees = 0;

        foreach ($dates as $date) {
            // Vérifier que la séance n'existe pas déjà
            if ($this->seancePlanifieeRepository->existsPourCreneauEtDate($creneau, $date)) {
                continue;
            }

            // Vérifier que ce n'est pas un jour fermé
            $dateStr = $date->format('Y-m-d');
            if (isset($joursFermes[$dateStr])) {
                continue;
            }

            // Créer la séance
            $seance = new SeancePlanifiee();
            $seance->initFromCreneau($creneau, $date);

            $this->entityManager->persist($seance);
            $nbCreees++;
        }

        if ($nbCreees > 0) {
            $this->entityManager->flush();
        }

        return $nbCreees;
    }

    /**
     * Régénère toutes les séances d'un créneau
     * (Alias pour generer avec regenerer=true)
     */
    public function regenerer(CreneauRecurrent $creneau): int
    {
        return $this->generer($creneau, true);
    }

    /**
     * Génère les séances pour tous les créneaux actifs d'une session
     * 
     * @param array<CreneauRecurrent> $creneaux
     * @return array<int, int> [creneauId => nbSeances]
     */
    public function genererPourCreneaux(array $creneaux): array
    {
        $resultats = [];

        foreach ($creneaux as $creneau) {
            if ($creneau->isActif()) {
                $resultats[$creneau->getId()] = $this->generer($creneau);
            }
        }

        return $resultats;
    }

    /**
     * Calcule toutes les dates d'occurrence d'un créneau
     * 
     * @return \DateTime[]
     */
    public function calculerDatesOccurrences(CreneauRecurrent $creneau): array
    {
        $dates = [];
        $jourSemaine = $creneau->getJourSemaine();
        $semaineType = $creneau->getSemaineType();

        if (!$jourSemaine || !$creneau->getDateDebut() || !$creneau->getDateFin()) {
            return $dates;
        }

        // Trouver la première occurrence
        $current = clone $creneau->getDateDebut();
        $jourIso = $jourSemaine->value;
        $jourActuel = (int) $current->format('N');

        // Avancer jusqu'au premier jour correspondant
        if ($jourActuel <= $jourIso) {
            $diff = $jourIso - $jourActuel;
        } else {
            $diff = 7 - $jourActuel + $jourIso;
        }
        $current->modify("+{$diff} days");

        // Parcourir toutes les occurrences
        $dateFin = $creneau->getDateFin();

        while ($current <= $dateFin) {
            // Vérifier le type de semaine si défini
            if ($semaineType === null || $semaineType->correspondA($current)) {
                $dates[] = clone $current;
            }

            // Passer à la semaine suivante
            $current->modify('+7 days');
        }

        return $dates;
    }

    /**
     * Récupère les jours fermés pour une période
     * 
     * @return array<string, true> [date Y-m-d => true]
     */
    private function getJoursFermesPourPeriode(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): array {
        // Récupérer le calendrier actif
        $calendrier = $this->calendrierAnneeRepository->findOneBy(['actif' => true]);

        if ($calendrier === null) {
            return [];
        }

        $joursFermes = $this->jourFermeRepository->findByCalendrierAndPeriode(
            $calendrier,
            $dateDebut,
            $dateFin
        );

        $result = [];
        foreach ($joursFermes as $jour) {
            $result[$jour->getDate()->format('Y-m-d')] = true;
        }

        return $result;
    }

    /**
     * Prévisualise les dates qui seraient générées pour un créneau
     * (sans créer les séances)
     * 
     * @return array{dates: \DateTime[], joursFermes: string[], existantes: string[]}
     */
    public function previsualiser(CreneauRecurrent $creneau): array
    {
        $toutesLesDates = $this->calculerDatesOccurrences($creneau);
        $joursFermes = $this->getJoursFermesPourPeriode(
            $creneau->getDateDebut(),
            $creneau->getDateFin()
        );

        $datesValides = [];
        $datesExclues = [];
        $datesExistantes = [];

        foreach ($toutesLesDates as $date) {
            $dateStr = $date->format('Y-m-d');
            
            if (isset($joursFermes[$dateStr])) {
                $datesExclues[] = $dateStr;
            } elseif ($creneau->getId() && $this->seancePlanifieeRepository->existsPourCreneauEtDate($creneau, $date)) {
                $datesExistantes[] = $dateStr;
            } else {
                $datesValides[] = $date;
            }
        }

        return [
            'dates' => $datesValides,
            'joursFermes' => $datesExclues,
            'existantes' => $datesExistantes,
        ];
    }

    /**
     * Estime le nombre de séances qui seraient générées
     */
    public function estimerNombreSeances(CreneauRecurrent $creneau): int
    {
        $preview = $this->previsualiser($creneau);
        return count($preview['dates']);
    }

    /**
     * Retourne le nombre total d'occurrences (sans exclure les jours fermés)
     */
    public function compterOccurrencesTotales(CreneauRecurrent $creneau): int
    {
        return count($this->calculerDatesOccurrences($creneau));
    }
}
