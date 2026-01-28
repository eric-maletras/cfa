<?php

namespace App\Service;

use App\Entity\CreneauRecurrent;
use App\Entity\SeancePlanifiee;
use App\Entity\Salle;
use App\Entity\User;
use App\Enum\SemaineType;
use App\Repository\SeancePlanifieeRepository;
use App\Repository\CreneauRecurrentRepository;

/**
 * Service de détection des conflits de planning
 * 
 * Ce service permet de détecter les conflits potentiels :
 * - Conflit de salle : même salle réservée au même moment
 * - Conflit de formateur : même formateur assigné à deux séances simultanées
 * 
 * Les conflits sont vérifiés au niveau :
 * - Des séances planifiées (pour une date précise)
 * - Des créneaux récurrents (pour une période)
 */
class ConflitPlanningService
{
    public function __construct(
        private SeancePlanifieeRepository $seanceRepository,
        private CreneauRecurrentRepository $creneauRepository,
    ) {
    }

    /**
     * Détecte un conflit de salle pour une séance
     * 
     * @return SeancePlanifiee|null La séance en conflit ou null
     */
    public function detecterConflitSalle(
        Salle $salle,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeSeanceId = null
    ): ?SeancePlanifiee {
        // Les salles virtuelles n'ont pas de conflit de capacité
        if ($salle->isVirtuel()) {
            return null;
        }

        return $this->seanceRepository->findConflitSalle(
            $salle,
            $date,
            $heureDebut,
            $heureFin,
            $excludeSeanceId
        );
    }

    /**
     * Détecte un conflit de formateur pour une séance
     * 
     * @return SeancePlanifiee|null La séance en conflit ou null
     */
    public function detecterConflitFormateur(
        User $formateur,
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeSeanceId = null
    ): ?SeancePlanifiee {
        return $this->seanceRepository->findConflitFormateur(
            $formateur,
            $date,
            $heureDebut,
            $heureFin,
            $excludeSeanceId
        );
    }

    /**
     * Valide un créneau récurrent et retourne les conflits détectés
     * 
     * @return array{
     *     salles: array<array{creneau: CreneauRecurrent, message: string}>,
     *     formateurs: array<array{creneau: CreneauRecurrent, formateur: User, message: string}>
     * }
     */
    public function validerCreneau(CreneauRecurrent $creneau): array
    {
        $conflits = [
            'salles' => [],
            'formateurs' => [],
        ];

        $excludeId = $creneau->getId();
        $jourSemaine = $creneau->getJourSemaine();
        $heureDebut = $creneau->getHeureDebut();
        $heureFin = $creneau->getHeureFin();
        $dateDebut = $creneau->getDateDebut();
        $dateFin = $creneau->getDateFin();
        $semaineType = $creneau->getSemaineType();

        // Vérifier les conflits de salle
        if (!$creneau->getSalle()->isVirtuel()) {
            $creneauxConflitSalle = $this->creneauRepository->findConflitsSalle(
                $creneau->getSalle(),
                $jourSemaine,
                $heureDebut,
                $heureFin,
                $dateDebut,
                $dateFin,
                $semaineType,
                $excludeId
            );

            foreach ($creneauxConflitSalle as $creneauConflit) {
                // Vérifier si les semaines A/B se chevauchent
                if (!$this->semainesTypeCompatibles($semaineType, $creneauConflit->getSemaineType())) {
                    continue;
                }

                $conflits['salles'][] = [
                    'creneau' => $creneauConflit,
                    'message' => sprintf(
                        'Conflit de salle avec le créneau "%s" (%s)',
                        $creneauConflit,
                        $creneauConflit->getSession()?->getCode() ?? '?'
                    ),
                ];
            }
        }

        // Vérifier les conflits de formateurs
        foreach ($creneau->getFormateurs() as $formateur) {
            $creneauxConflitFormateur = $this->creneauRepository->findConflitsFormateur(
                $formateur,
                $jourSemaine,
                $heureDebut,
                $heureFin,
                $dateDebut,
                $dateFin,
                $semaineType,
                $excludeId
            );

            foreach ($creneauxConflitFormateur as $creneauConflit) {
                // Vérifier si les semaines A/B se chevauchent
                if (!$this->semainesTypeCompatibles($semaineType, $creneauConflit->getSemaineType())) {
                    continue;
                }

                $conflits['formateurs'][] = [
                    'creneau' => $creneauConflit,
                    'formateur' => $formateur,
                    'message' => sprintf(
                        'Conflit pour %s avec le créneau "%s" (%s)',
                        $formateur->getNomComplet(),
                        $creneauConflit,
                        $creneauConflit->getSession()?->getCode() ?? '?'
                    ),
                ];
            }
        }

        return $conflits;
    }

    /**
     * Vérifie si une séance a des conflits
     * 
     * @return array{salle: ?SeancePlanifiee, formateurs: array<array{formateur: User, seance: SeancePlanifiee}>}
     */
    public function validerSeance(SeancePlanifiee $seance): array
    {
        $conflits = [
            'salle' => null,
            'formateurs' => [],
        ];

        $excludeId = $seance->getId();
        $date = $seance->getDate();
        $heureDebut = $seance->getHeureDebut();
        $heureFin = $seance->getHeureFin();

        // Vérifier le conflit de salle
        $conflitSalle = $this->detecterConflitSalle(
            $seance->getSalle(),
            $date,
            $heureDebut,
            $heureFin,
            $excludeId
        );

        if ($conflitSalle !== null) {
            $conflits['salle'] = $conflitSalle;
        }

        // Vérifier les conflits de formateurs
        foreach ($seance->getFormateurs() as $formateur) {
            $conflitFormateur = $this->detecterConflitFormateur(
                $formateur,
                $date,
                $heureDebut,
                $heureFin,
                $excludeId
            );

            if ($conflitFormateur !== null) {
                $conflits['formateurs'][] = [
                    'formateur' => $formateur,
                    'seance' => $conflitFormateur,
                ];
            }
        }

        return $conflits;
    }

    /**
     * Vérifie si deux types de semaine sont compatibles (peuvent se chevaucher)
     * 
     * Les semaines se chevauchent si :
     * - Les deux sont null (toutes les semaines)
     * - L'un est null et l'autre défini
     * - Les deux sont identiques (A et A, B et B)
     * 
     * Les semaines ne se chevauchent pas si :
     * - A et B (alternance parfaite)
     */
    private function semainesTypeCompatibles(
        ?SemaineType $type1,
        ?SemaineType $type2
    ): bool {
        // Si l'un est null (toutes les semaines), il y a chevauchement
        if ($type1 === null || $type2 === null) {
            return true;
        }

        // Même type = chevauchement, types différents = pas de chevauchement
        return $type1 === $type2;
    }

    /**
     * Vérifie si un créneau a des conflits (retourne true/false)
     */
    public function aDesConflits(CreneauRecurrent $creneau): bool
    {
        $conflits = $this->validerCreneau($creneau);
        return !empty($conflits['salles']) || !empty($conflits['formateurs']);
    }

    /**
     * Compte le nombre de conflits pour un créneau
     */
    public function compterConflits(CreneauRecurrent $creneau): int
    {
        $conflits = $this->validerCreneau($creneau);
        return count($conflits['salles']) + count($conflits['formateurs']);
    }

    /**
     * Retourne un résumé des conflits pour affichage
     * 
     * @return array<string> Messages de conflits
     */
    public function getMessagesConflits(CreneauRecurrent $creneau): array
    {
        $conflits = $this->validerCreneau($creneau);
        $messages = [];

        foreach ($conflits['salles'] as $conflit) {
            $messages[] = $conflit['message'];
        }

        foreach ($conflits['formateurs'] as $conflit) {
            $messages[] = $conflit['message'];
        }

        return $messages;
    }
}
