<?php

namespace App\Service;

/**
 * Service de calcul des jours fériés français
 * 
 * Gère les jours fériés fixes et mobiles (basés sur Pâques)
 * 
 * Jours fériés fixes :
 * - 1er janvier (Jour de l'An)
 * - 1er mai (Fête du Travail)
 * - 8 mai (Victoire 1945)
 * - 14 juillet (Fête Nationale)
 * - 15 août (Assomption)
 * - 1er novembre (Toussaint)
 * - 11 novembre (Armistice 1918)
 * - 25 décembre (Noël)
 * 
 * Jours fériés mobiles (basés sur Pâques) :
 * - Lundi de Pâques (Pâques + 1 jour)
 * - Ascension (Pâques + 39 jours)
 * - Lundi de Pentecôte (Pâques + 50 jours)
 */
class JoursFeriesFranceService
{
    /**
     * Jours fériés fixes (mois => jour => libellé)
     */
    private const JOURS_FIXES = [
        1 => [1 => 'Jour de l\'An'],
        5 => [
            1 => 'Fête du Travail',
            8 => 'Victoire 1945',
        ],
        7 => [14 => 'Fête Nationale'],
        8 => [15 => 'Assomption'],
        11 => [
            1 => 'Toussaint',
            11 => 'Armistice 1918',
        ],
        12 => [25 => 'Noël'],
    ];

    /**
     * Calcule la date de Pâques pour une année donnée
     * 
     * Utilise l'algorithme de Butcher (valide pour le calendrier grégorien)
     */
    public function calculerPaques(int $annee): \DateTimeImmutable
    {
        // Algorithme de Meeus/Jones/Butcher
        $a = $annee % 19;
        $b = intdiv($annee, 100);
        $c = $annee % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mois = intdiv($h + $l - 7 * $m + 114, 31);
        $jour = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $annee, $mois, $jour));
    }

    /**
     * Retourne tous les jours fériés pour une année donnée
     * 
     * @return array<array{date: \DateTimeImmutable, libelle: string}>
     */
    public function getJoursFeries(int $annee): array
    {
        $joursFeries = [];

        // Ajout des jours fixes
        foreach (self::JOURS_FIXES as $mois => $jours) {
            foreach ($jours as $jour => $libelle) {
                $joursFeries[] = [
                    'date' => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $annee, $mois, $jour)),
                    'libelle' => $libelle,
                ];
            }
        }

        // Calcul de Pâques et des jours mobiles
        $paques = $this->calculerPaques($annee);

        // Lundi de Pâques (Pâques + 1 jour)
        $joursFeries[] = [
            'date' => $paques->modify('+1 day'),
            'libelle' => 'Lundi de Pâques',
        ];

        // Ascension (Pâques + 39 jours = jeudi)
        $joursFeries[] = [
            'date' => $paques->modify('+39 days'),
            'libelle' => 'Ascension',
        ];

        // Lundi de Pentecôte (Pâques + 50 jours)
        $joursFeries[] = [
            'date' => $paques->modify('+50 days'),
            'libelle' => 'Lundi de Pentecôte',
        ];

        // Tri par date
        usort($joursFeries, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $joursFeries;
    }

    /**
     * Retourne les jours fériés pour plusieurs années
     * 
     * @param int[] $annees
     * @return array<array{date: \DateTimeImmutable, libelle: string}>
     */
    public function getJoursFeriesPourAnnees(array $annees): array
    {
        $joursFeries = [];
        
        foreach ($annees as $annee) {
            $joursFeries = array_merge($joursFeries, $this->getJoursFeries($annee));
        }

        // Tri par date
        usort($joursFeries, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $joursFeries;
    }

    /**
     * Vérifie si une date est un jour férié
     */
    public function estJourFerie(\DateTimeInterface $date): bool
    {
        $annee = (int) $date->format('Y');
        $joursFeries = $this->getJoursFeries($annee);

        $dateStr = $date->format('Y-m-d');
        foreach ($joursFeries as $ferie) {
            if ($ferie['date']->format('Y-m-d') === $dateStr) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le libellé du jour férié si la date en est un
     */
    public function getLibelleJourFerie(\DateTimeInterface $date): ?string
    {
        $annee = (int) $date->format('Y');
        $joursFeries = $this->getJoursFeries($annee);

        $dateStr = $date->format('Y-m-d');
        foreach ($joursFeries as $ferie) {
            if ($ferie['date']->format('Y-m-d') === $dateStr) {
                return $ferie['libelle'];
            }
        }

        return null;
    }

    /**
     * Filtre les jours fériés dans une période donnée
     * 
     * @return array<array{date: \DateTimeImmutable, libelle: string}>
     */
    public function getJoursFeriesPeriode(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin
    ): array {
        $anneeDebut = (int) $dateDebut->format('Y');
        $anneeFin = (int) $dateFin->format('Y');

        $annees = range($anneeDebut, $anneeFin);
        $joursFeries = $this->getJoursFeriesPourAnnees($annees);

        // Filtrer les dates hors période
        return array_filter($joursFeries, function ($ferie) use ($dateDebut, $dateFin) {
            return $ferie['date'] >= $dateDebut && $ferie['date'] <= $dateFin;
        });
    }

    /**
     * Retourne les jours fériés avec leur jour de la semaine
     * Utile pour identifier les fériés tombant un jour ouvré
     * 
     * @return array<array{date: \DateTimeImmutable, libelle: string, jour_semaine: string, est_weekend: bool}>
     */
    public function getJoursFeriesDetailles(int $annee): array
    {
        $joursFeries = $this->getJoursFeries($annee);
        $joursSemaine = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return array_map(function ($ferie) use ($joursSemaine) {
            $numJour = (int) $ferie['date']->format('N');
            return [
                'date' => $ferie['date'],
                'libelle' => $ferie['libelle'],
                'jour_semaine' => $joursSemaine[$numJour],
                'est_weekend' => $numJour >= 6,
            ];
        }, $joursFeries);
    }
}
