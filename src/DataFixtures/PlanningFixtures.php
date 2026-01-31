<?php

namespace App\DataFixtures;

use App\Entity\CreneauRecurrent;
use App\Entity\Salle;
use App\Entity\SeancePlanifiee;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use App\Enum\StatutSeance;
use App\Service\GenerateurSeancesService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour le module Planning
 * 
 * NOUVEAU : Crée une séance "TEST APPEL" à l'heure actuelle
 * pour permettre de tester le module d'appel à tout moment.
 * 
 * Formateur de test : Pierre Durail (pierre.durail@cfa.ericm.fr / Btssio75000!)
 */
class PlanningFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const SEANCE_TEST_APPEL_REF = 'seance-test-appel';

    private const FORMATEURS_SISR = [
        0,  // Sophie Martin - Culture générale
        1,  // Pierre Durail - Anglais
        2,  // Marie Dubois - Mathématiques
        3,  // Jean Laurent - Économie-Droit
        5,  // Antoine Garcia - Réseaux et cybersécurité
        6,  // Nathalie Roux - Développement web
        7,  // François Petit - Administration systèmes
    ];

    public function __construct(
        private GenerateurSeancesService $generateurService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $sessionRepo = $manager->getRepository(Session::class);
        $session = $sessionRepo->findOneBy([
            'code' => 'BTSSISR-2024',
            'actif' => true,
        ]);

        if (!$session) {
            $session = $sessionRepo->findOneBy(['actif' => true]);
        }

        if (!$session) {
            return;
        }

        // 1. Ajouter les formateurs à la session
        $formateurs = [];
        foreach (self::FORMATEURS_SISR as $index) {
            $refKey = UserFixtures::FORMATEUR_PREFIX . $index;
            try {
                /** @var User $formateur */
                $formateur = $this->getReference($refKey, User::class);
                $session->addFormateur($formateur);
                $formateurs[] = $formateur;
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($formateurs)) {
            return;
        }

        $manager->persist($session);
        $manager->flush();

        // 2. Récupérer les ressources
        /** @var Salle $laboIt1 */
        $laboIt1 = $this->getReference(SalleFixtures::SALLE_LABO_IT_1_REF, Salle::class);
        /** @var Salle $salleA101 */
        $salleA101 = $this->getReference(SalleFixtures::SALLE_A101_REF, Salle::class);
        /** @var Salle $salleA102 */
        $salleA102 = $this->getReference(SalleFixtures::SALLE_A102_REF, Salle::class);

        $sessionMatieres = $session->getSessionMatieres()->toArray();
        if (empty($sessionMatieres)) {
            return;
        }

        // 3. Période
        $now = new \DateTime();
        $moisActuel = (int) $now->format('n');
        $anneeActuelle = (int) $now->format('Y');

        $anneeDebut = ($moisActuel >= 9) ? $anneeActuelle : $anneeActuelle - 1;
        $anneeFin = $anneeDebut + 1;

        $dateDebutPeriode = new \DateTime(sprintf('%d-09-15', $anneeDebut));
        $dateFinPeriode = new \DateTime(sprintf('%d-06-30', $anneeFin));

        // 4. Créneaux récurrents
        $creneaux = [];

        $formateurReseaux = $formateurs[4] ?? $formateurs[0];
        $formateurMaths = $formateurs[2] ?? $formateurs[0];
        $formateurAnglais = $formateurs[1] ?? $formateurs[0];
        $formateurCGE = $formateurs[0];
        $formateurEcoDroit = $formateurs[3] ?? $formateurs[0];

        // Créneau 1 : TP Réseaux - Lundi 8h-10h
        $creneau1 = new CreneauRecurrent();
        $creneau1->setSession($session);
        $creneau1->setSessionMatiere($sessionMatieres[0]);
        $creneau1->setSalle($laboIt1);
        $creneau1->setJourSemaine(JourSemaine::LUNDI);
        $creneau1->setHeureDebut(new \DateTime('08:00:00'));
        $creneau1->setHeureFin(new \DateTime('10:00:00'));
        $creneau1->setDateDebut(clone $dateDebutPeriode);
        $creneau1->setDateFin(clone $dateFinPeriode);
        $creneau1->setSemaineType(null);
        $creneau1->setActif(true);
        $creneau1->setCommentaire('TP Réseaux et systèmes');
        $creneau1->addFormateur($formateurReseaux);
        $manager->persist($creneau1);
        $creneaux[] = $creneau1;

        // Créneau 2 : Mathématiques - Mardi 14h-16h
        if (count($sessionMatieres) > 1) {
            $creneau2 = new CreneauRecurrent();
            $creneau2->setSession($session);
            $creneau2->setSessionMatiere($sessionMatieres[1]);
            $creneau2->setSalle($salleA101);
            $creneau2->setJourSemaine(JourSemaine::MARDI);
            $creneau2->setHeureDebut(new \DateTime('14:00:00'));
            $creneau2->setHeureFin(new \DateTime('16:00:00'));
            $creneau2->setDateDebut(clone $dateDebutPeriode);
            $creneau2->setDateFin(clone $dateFinPeriode);
            $creneau2->setSemaineType(null);
            $creneau2->setActif(true);
            $creneau2->setCommentaire('Mathématiques appliquées');
            $creneau2->addFormateur($formateurMaths);
            $manager->persist($creneau2);
            $creneaux[] = $creneau2;
        }

        // Créneau 3 : Anglais - Mercredi 8h30-10h30
        if (count($sessionMatieres) > 2) {
            $creneau3 = new CreneauRecurrent();
            $creneau3->setSession($session);
            $creneau3->setSessionMatiere($sessionMatieres[2]);
            $creneau3->setSalle($salleA102);
            $creneau3->setJourSemaine(JourSemaine::MERCREDI);
            $creneau3->setHeureDebut(new \DateTime('08:30:00'));
            $creneau3->setHeureFin(new \DateTime('10:30:00'));
            $creneau3->setDateDebut(clone $dateDebutPeriode);
            $creneau3->setDateFin(clone $dateFinPeriode);
            $creneau3->setSemaineType(SemaineType::A);
            $creneau3->setActif(true);
            $creneau3->setCommentaire('Anglais technique');
            $creneau3->addFormateur($formateurAnglais);
            $manager->persist($creneau3);
            $creneaux[] = $creneau3;
        }

        // Créneau 4 : Culture générale - Jeudi 14h-17h
        if (count($sessionMatieres) > 3) {
            $creneau4 = new CreneauRecurrent();
            $creneau4->setSession($session);
            $creneau4->setSessionMatiere($sessionMatieres[3]);
            $creneau4->setSalle($salleA101);
            $creneau4->setJourSemaine(JourSemaine::JEUDI);
            $creneau4->setHeureDebut(new \DateTime('14:00:00'));
            $creneau4->setHeureFin(new \DateTime('17:00:00'));
            $creneau4->setDateDebut(clone $dateDebutPeriode);
            $creneau4->setDateFin(clone $dateFinPeriode);
            $creneau4->setSemaineType(null);
            $creneau4->setActif(true);
            $creneau4->setCommentaire('Co-intervention CGE + Eco-Droit');
            $creneau4->addFormateur($formateurCGE);
            $creneau4->addFormateur($formateurEcoDroit);
            $manager->persist($creneau4);
            $creneaux[] = $creneau4;
        }

        $manager->flush();

        // 5. Générer les séances planifiées
        foreach ($creneaux as $creneau) {
            try {
                $this->generateurService->generer($creneau);
            } catch (\Exception $e) {
                continue;
            }
        }

        // =====================================================
        // 6. NOUVEAU : Séance TEST APPEL à l'heure actuelle
        // =====================================================
        $this->creerSeanceTestMaintenant($manager, $session, $sessionMatieres, $salleA101, $formateurAnglais);
    }

    /**
     * Crée une séance "TEST APPEL" qui englobe l'heure actuelle
     * Début : maintenant - 30 min
     * Fin : maintenant + 90 min
     * 
     * Cette séance permet de tester le module d'appel à tout moment
     * du chargement des fixtures.
     */
    private function creerSeanceTestMaintenant(
        ObjectManager $manager,
        Session $session,
        array $sessionMatieres,
        Salle $salle,
        User $formateur
    ): void {
        $now = new \DateTime();
        
        // La séance englobe l'heure actuelle : début -30min, fin +90min
        $heureDebut = (clone $now)->modify('-30 minutes');
        $heureFin = (clone $now)->modify('+90 minutes');

        $seance = new SeancePlanifiee();
        $seance->setSession($session);
        $seance->setSessionMatiere($sessionMatieres[0] ?? null);
        $seance->setSalle($salle);
        $seance->setDate(new \DateTime('today'));
        $seance->setHeureDebut($heureDebut);
        $seance->setHeureFin($heureFin);
        $seance->setStatut(StatutSeance::PLANIFIEE);
        $seance->setCommentaire('⚠️ TEST APPEL - Connectez-vous : pierre.durail@cfa.ericm.fr / Btssio75000!');
        $seance->addFormateur($formateur);

        $manager->persist($seance);
        $manager->flush();

        $this->addReference(self::SEANCE_TEST_APPEL_REF, $seance);
    }

    public function getDependencies(): array
    {
        return [
            SessionFixtures::class,
            SalleFixtures::class,
            UserFixtures::class,
            CalendrierFixtures::class,
            SessionMatiereFormateurFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['planning'];
    }
}
