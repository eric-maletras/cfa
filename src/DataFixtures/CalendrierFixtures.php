<?php

namespace App\DataFixtures;

use App\Entity\CalendrierAnnee;
use App\Entity\JourFerme;
use App\Enum\TypeJourFerme;
use App\Service\JoursFeriesFranceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les calendriers annuels et jours fermés
 * 
 * Calcul dynamique de l'année scolaire :
 * - Si mois actuel >= septembre → année scolaire = année courante / année courante + 1
 * - Si mois actuel < septembre → année scolaire = année courante - 1 / année courante
 * 
 * L'année scolaire va du 1er septembre au 31 juillet
 */
class CalendrierFixtures extends Fixture
{
    public const CALENDRIER_REFERENCE = 'calendrier-actif';

    public function __construct(
        private JoursFeriesFranceService $joursFeriesService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Calcul dynamique de l'année scolaire
        $now = new \DateTime();
        $moisActuel = (int) $now->format('n');
        $anneeActuelle = (int) $now->format('Y');

        // Détermination de l'année scolaire
        if ($moisActuel >= 9) {
            // Après septembre : année courante / année suivante
            $anneeDebut = $anneeActuelle;
        } else {
            // Avant septembre : année précédente / année courante
            $anneeDebut = $anneeActuelle - 1;
        }
        $anneeFin = $anneeDebut + 1;

        // Création du calendrier (septembre à juillet)
        $calendrier = new CalendrierAnnee();
        $calendrier->setCode(sprintf('%d-%d', $anneeDebut, $anneeFin));
        $calendrier->setLibelle(sprintf('Année scolaire %d-%d', $anneeDebut, $anneeFin));
        $calendrier->setDateDebut(new \DateTime(sprintf('%d-09-01', $anneeDebut)));
        $calendrier->setDateFin(new \DateTime(sprintf('%d-07-31', $anneeFin))); // Fin en juillet !
        $calendrier->setHeureDebutDefaut(new \DateTime('08:30:00'));
        $calendrier->setHeureFinDefaut(new \DateTime('17:30:00'));
        $calendrier->setActif(true);

        $manager->persist($calendrier);

        // Suivi des dates déjà ajoutées pour éviter les doublons
        $datesAjoutees = [];

        // Import des jours fériés français pour les deux années civiles couvertes
        $annees = [$anneeDebut, $anneeFin];
        $joursFeries = $this->joursFeriesService->getJoursFeriesPourAnnees($annees);

        foreach ($joursFeries as $ferie) {
            // Vérifier que la date est dans la période du calendrier
            if ($calendrier->contientDate($ferie['date'])) {
                $dateStr = $ferie['date']->format('Y-m-d');
                
                $jourFerme = new JourFerme();
                $jourFerme->setCalendrier($calendrier);
                $jourFerme->setDate(\DateTime::createFromImmutable($ferie['date']));
                $jourFerme->setLibelle($ferie['libelle']);
                $jourFerme->setType(TypeJourFerme::FERIE);
                
                $manager->persist($jourFerme);
                $datesAjoutees[$dateStr] = true;
            }
        }

        // Ajout des vacances de Noël (21 décembre au 4 janvier)
        $debutVacancesNoel = new \DateTime(sprintf('%d-12-21', $anneeDebut));
        $finVacancesNoel = new \DateTime(sprintf('%d-01-04', $anneeFin));

        $current = clone $debutVacancesNoel;
        while ($current <= $finVacancesNoel) {
            $dateStr = $current->format('Y-m-d');
            $jourSemaine = (int) $current->format('N');
            
            // Ignorer les week-ends et les dates déjà ajoutées (fériés)
            if ($jourSemaine < 6 && !isset($datesAjoutees[$dateStr])) {
                if ($calendrier->contientDate($current)) {
                    $jourFerme = new JourFerme();
                    $jourFerme->setCalendrier($calendrier);
                    $jourFerme->setDate(clone $current);
                    $jourFerme->setLibelle('Vacances de Noël');
                    $jourFerme->setType(TypeJourFerme::VACANCES);
                    
                    $manager->persist($jourFerme);
                    $datesAjoutees[$dateStr] = true;
                }
            }
            
            $current->modify('+1 day');
        }

        // Ajout de ponts potentiels (sauf ceux déjà couverts par les vacances)
        $ponts = $this->calculerPonts($joursFeries, $calendrier, $datesAjoutees);
        foreach ($ponts as $pont) {
            $manager->persist($pont);
        }

        $manager->flush();

        // Référence pour d'autres fixtures
        $this->addReference(self::CALENDRIER_REFERENCE, $calendrier);
    }

    /**
     * Calcule les ponts possibles
     * Un pont est généralement le vendredi après un férié jeudi
     * 
     * @param array $joursFeries Liste des jours fériés
     * @param CalendrierAnnee $calendrier Le calendrier
     * @param array $datesAjoutees Dates déjà présentes (pour éviter doublons)
     * @return JourFerme[]
     */
    private function calculerPonts(
        array $joursFeries,
        CalendrierAnnee $calendrier,
        array $datesAjoutees
    ): array {
        $ponts = [];

        foreach ($joursFeries as $ferie) {
            $dateFerie = $ferie['date'];
            $jourSemaine = (int) $dateFerie->format('N');

            // Férié un jeudi → pont le vendredi
            if ($jourSemaine === 4) {
                $dateVendredi = $dateFerie->modify('+1 day');
                $dateVendrediStr = $dateVendredi->format('Y-m-d');

                // Vérifier que le vendredi n'est pas déjà ajouté (férié ou vacances)
                if (!isset($datesAjoutees[$dateVendrediStr]) && $calendrier->contientDate($dateVendredi)) {
                    $pont = new JourFerme();
                    $pont->setCalendrier($calendrier);
                    $pont->setDate(\DateTime::createFromImmutable($dateVendredi));
                    $pont->setLibelle(sprintf('Pont %s', $ferie['libelle']));
                    $pont->setType(TypeJourFerme::PONT);

                    $ponts[] = $pont;
                    $datesAjoutees[$dateVendrediStr] = true; // Éviter doublons entre ponts
                }
            }
        }

        return $ponts;
    }
}
