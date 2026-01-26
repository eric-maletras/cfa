<?php

namespace App\DataFixtures;

use App\Entity\Session;
use App\Entity\Formation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les sessions de formation
 * 
 * Crée des sessions pour chaque formation BTS et initialise automatiquement
 * les matières depuis le référentiel de chaque formation.
 * 
 * Références créées (utilisées par InscriptionFixtures) :
 * - session-SIO-SISR-active / session-SIO-SISR-inactive
 * - session-SIO-SLAM-active / session-SIO-SLAM-inactive
 * - session-CIEL-IR-active / session-CIEL-IR-inactive
 * - session-SAM-active / session-SAM-inactive
 */
class SessionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Préfixe pour les références (utilisé par InscriptionFixtures)
    public const SESSION_PREFIX = 'session-';

    // Mapping BTS → référence formation
    private const BTS_FORMATIONS = [
        'SIO-SISR' => FormationFixtures::FORMATION_SIO_SISR_REF,
        'SIO-SLAM' => FormationFixtures::FORMATION_SIO_SLAM_REF,
        'CIEL-IR'  => FormationFixtures::FORMATION_CIEL_IR_REF,
        'SAM'      => FormationFixtures::FORMATION_SAM_REF,
    ];

    // Couleurs par BTS
    private const BTS_COLORS = [
        'SIO-SISR' => '27ae60',
        'SIO-SLAM' => '3498db',
        'CIEL-IR'  => '9b59b6',
        'SAM'      => 'e67e22',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::BTS_FORMATIONS as $btsKey => $formationRef) {
            /** @var Formation $formation */
            $formation = $this->getReference($formationRef, Formation::class);
            $couleur = self::BTS_COLORS[$btsKey];
            $codeBase = 'BTS' . str_replace('-', '', $btsKey);

            // ============================================
            // Session ACTIVE (2024-2026) - En cours
            // ============================================
            $sessionActive = new Session();
            $sessionActive->setFormation($formation);
            $sessionActive->setCode($codeBase . '-2024');
            $sessionActive->setLibelle($formation->getIntituleCourt() . ' - Promotion 2024-2026');
            $sessionActive->setDateDebut(new \DateTime('2024-09-02'));
            $sessionActive->setDateFin(new \DateTime('2026-07-10'));
            $sessionActive->setStatut(Session::STATUT_EN_COURS);
            $sessionActive->setEffectifMin(12);
            $sessionActive->setEffectifMax(30);
            $sessionActive->setLieu('Campus principal');
            $sessionActive->setCouleur($couleur);
            $sessionActive->setActif(true);

            // Initialiser les matières depuis le référentiel
            $sessionActive->initMatieresFromFormation();

            // Simuler ~40% de réalisation pour les sessions en cours
            foreach ($sessionActive->getSessionMatieres() as $sm) {
                $planifie = $sm->getVolumeHeuresReferentiel();
                $realise = (int) ($planifie * 0.4 * (0.8 + (mt_rand(0, 40) / 100)));
                $sm->setVolumeHeuresRealise($realise);
            }

            $manager->persist($sessionActive);
            // Référence attendue par InscriptionFixtures : session-SIO-SISR-active
            $this->addReference(self::SESSION_PREFIX . $btsKey . '-active', $sessionActive);

            // ============================================
            // Session INACTIVE (2023-2025) - En cours aussi (2ème année)
            // ============================================
            $sessionInactive = new Session();
            $sessionInactive->setFormation($formation);
            $sessionInactive->setCode($codeBase . '-2023');
            $sessionInactive->setLibelle($formation->getIntituleCourt() . ' - Promotion 2023-2025');
            $sessionInactive->setDateDebut(new \DateTime('2023-09-04'));
            $sessionInactive->setDateFin(new \DateTime('2025-07-11'));
            $sessionInactive->setStatut(Session::STATUT_EN_COURS);
            $sessionInactive->setEffectifMin(12);
            $sessionInactive->setEffectifMax(30);
            $sessionInactive->setLieu('Campus principal');
            $sessionInactive->setCouleur($couleur);
            $sessionInactive->setActif(true);

            // Initialiser les matières depuis le référentiel
            $sessionInactive->initMatieresFromFormation();

            // Simuler ~80% de réalisation pour les sessions 2023 (2ème année)
            foreach ($sessionInactive->getSessionMatieres() as $sm) {
                $planifie = $sm->getVolumeHeuresReferentiel();
                $realise = (int) ($planifie * 0.8 * (0.9 + (mt_rand(0, 20) / 100)));
                $sm->setVolumeHeuresRealise($realise);
            }

            $manager->persist($sessionInactive);
            // Référence attendue par InscriptionFixtures : session-SIO-SISR-inactive
            $this->addReference(self::SESSION_PREFIX . $btsKey . '-inactive', $sessionInactive);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FormationFixtures::class,
            MatiereFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'sessions'];
    }
}
