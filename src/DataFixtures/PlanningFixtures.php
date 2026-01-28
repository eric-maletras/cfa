<?php

namespace App\DataFixtures;

use App\Entity\CreneauRecurrent;
use App\Entity\Salle;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\JourSemaine;
use App\Enum\SemaineType;
use App\Service\GenerateurSeancesService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour le module Planning
 * 
 * Crée des créneaux récurrents pour la session SIO-SISR active
 * et génère automatiquement les séances planifiées correspondantes.
 * 
 * Cette fixture :
 * 1. Ajoute les formateurs concernés à la session SIO-SISR
 * 2. Crée 4 créneaux récurrents avec différentes configurations
 * 3. Génère automatiquement les séances planifiées
 * 
 * Créneaux créés :
 * - SI7 (SISR) : Lundi 8h-10h en LABO-IT-1 (toutes les semaines) - Garcia
 * - Mathématiques : Mardi 14h-16h en A101 (toutes les semaines) - Dubois
 * - Anglais : Mercredi 8h30-10h30 en A102 (semaine A uniquement) - Durail
 * - Culture générale : Jeudi 14h-17h en A101 (toutes les semaines) - Martin + Laurent (co-intervention)
 */
class PlanningFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Indices des formateurs dans UserFixtures::FORMATEURS_DATA
    // qui interviennent en SIO-SISR
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
        // Récupérer la session SIO-SISR active
        $sessionRepo = $manager->getRepository(Session::class);
        $session = $sessionRepo->findOneBy([
            'code' => 'BTSSISR-2024',
            'actif' => true,
        ]);

        if (!$session) {
            // Fallback : prendre la première session active
            $session = $sessionRepo->findOneBy(['actif' => true]);
        }

        if (!$session) {
            return; // Pas de session, on ne peut rien faire
        }

        // ===================================================
        // 1. Ajouter les formateurs à la session
        // ===================================================
        $formateurs = [];
        foreach (self::FORMATEURS_SISR as $index) {
            $refKey = UserFixtures::FORMATEUR_PREFIX . $index;
            try {
                /** @var User $formateur */
                $formateur = $this->getReference($refKey, User::class);
                $session->addFormateur($formateur);
                $formateurs[] = $formateur;
            } catch (\Exception $e) {
                // Référence non trouvée, on continue
                continue;
            }
        }

        if (empty($formateurs)) {
            return; // Pas de formateurs disponibles
        }

        // Persister la session avec ses formateurs
        $manager->persist($session);
        $manager->flush();

        // ===================================================
        // 2. Récupérer les ressources nécessaires
        // ===================================================

        // Salles
        /** @var Salle $laboIt1 */
        $laboIt1 = $this->getReference(SalleFixtures::SALLE_LABO_IT_1_REF, Salle::class);
        /** @var Salle $salleA101 */
        $salleA101 = $this->getReference(SalleFixtures::SALLE_A101_REF, Salle::class);
        /** @var Salle $salleA102 */
        $salleA102 = $this->getReference(SalleFixtures::SALLE_A102_REF, Salle::class);

        // Matières de la session
        $sessionMatieres = $session->getSessionMatieres()->toArray();
        if (empty($sessionMatieres)) {
            return; // Pas de matières
        }

        // ===================================================
        // 3. Définir la période de récurrence
        // ===================================================
        $now = new \DateTime();
        $moisActuel = (int) $now->format('n');
        $anneeActuelle = (int) $now->format('Y');

        if ($moisActuel >= 9) {
            $anneeDebut = $anneeActuelle;
        } else {
            $anneeDebut = $anneeActuelle - 1;
        }
        $anneeFin = $anneeDebut + 1;

        // Période : 15 septembre au 30 juin
        $dateDebutPeriode = new \DateTime(sprintf('%d-09-15', $anneeDebut));
        $dateFinPeriode = new \DateTime(sprintf('%d-06-30', $anneeFin));

        // ===================================================
        // 4. Créer les créneaux récurrents
        // ===================================================
        $creneaux = [];

        // Formateurs par spécialité (indices dans $formateurs)
        // 0: Martin (CGE), 1: Durail (Anglais), 2: Dubois (Maths), 
        // 3: Laurent (Eco-Droit), 4: Garcia (Réseaux), 5: Roux (Dev), 6: Petit (Systèmes)
        
        $formateurReseaux = $formateurs[4] ?? $formateurs[0];  // Garcia ou fallback
        $formateurMaths = $formateurs[2] ?? $formateurs[0];    // Dubois ou fallback
        $formateurAnglais = $formateurs[1] ?? $formateurs[0];  // Durail ou fallback
        $formateurCGE = $formateurs[0];                        // Martin
        $formateurEcoDroit = $formateurs[3] ?? $formateurs[0]; // Laurent ou fallback

        // -------------------------------------------------
        // Créneau 1 : TP Réseaux - Lundi 8h-10h - LABO-IT-1
        // -------------------------------------------------
        $creneau1 = new CreneauRecurrent();
        $creneau1->setSession($session);
        $creneau1->setSessionMatiere($sessionMatieres[0]); // Première matière
        $creneau1->setSalle($laboIt1);
        $creneau1->setJourSemaine(JourSemaine::LUNDI);
        $creneau1->setHeureDebut(new \DateTime('08:00:00'));
        $creneau1->setHeureFin(new \DateTime('10:00:00'));
        $creneau1->setDateDebut(clone $dateDebutPeriode);
        $creneau1->setDateFin(clone $dateFinPeriode);
        $creneau1->setSemaineType(null); // Toutes les semaines
        $creneau1->setActif(true);
        $creneau1->setCommentaire('TP Réseaux et systèmes - Laboratoire informatique');
        $creneau1->addFormateur($formateurReseaux);
        
        $manager->persist($creneau1);
        $creneaux[] = $creneau1;

        // -------------------------------------------------
        // Créneau 2 : Mathématiques - Mardi 14h-16h - A101
        // -------------------------------------------------
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
            $creneau2->setCommentaire('Mathématiques appliquées à l\'informatique');
            $creneau2->addFormateur($formateurMaths);
            
            $manager->persist($creneau2);
            $creneaux[] = $creneau2;
        }

        // -------------------------------------------------
        // Créneau 3 : Anglais - Mercredi 8h30-10h30 - A102
        // Semaine A uniquement (alternance)
        // -------------------------------------------------
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
            $creneau3->setSemaineType(SemaineType::A); // Semaines impaires
            $creneau3->setActif(true);
            $creneau3->setCommentaire('Anglais technique - Semaine A uniquement');
            $creneau3->addFormateur($formateurAnglais);
            
            $manager->persist($creneau3);
            $creneaux[] = $creneau3;
        }

        // -------------------------------------------------
        // Créneau 4 : Culture générale - Jeudi 14h-17h - A101
        // Co-intervention : CGE + Économie-Droit
        // -------------------------------------------------
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
            $creneau4->setCommentaire('Co-intervention : Culture générale et Économie-Droit');
            
            // Co-intervention : 2 formateurs
            $creneau4->addFormateur($formateurCGE);
            $creneau4->addFormateur($formateurEcoDroit);
            
            $manager->persist($creneau4);
            $creneaux[] = $creneau4;
        }

        // Flush pour persister les créneaux
        $manager->flush();

        // ===================================================
        // 5. Générer les séances planifiées
        // ===================================================
        $totalSeances = 0;
        foreach ($creneaux as $creneau) {
            try {
                $nbSeances = $this->generateurService->generer($creneau);
                $totalSeances += $nbSeances;
            } catch (\Exception $e) {
                // Si erreur de génération, on continue
                continue;
            }
        }
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
