<?php

namespace App\DataFixtures;

use App\Entity\Session;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les sessions de formation
 * 
 * Crée 8 sessions :
 * - 4 sessions actives (2024-2026) : en cours, année 1
 * - 4 sessions inactives (2023-2025) : terminées ou en année 2
 */
class SessionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Préfixes pour les références
    public const SESSION_PREFIX = 'session-';

    // Mapping entre clés BTS et références de formation
    private const BTS_FORMATION_MAP = [
        'SIO-SISR' => FormationFixtures::FORMATION_SIO_SISR_REF,
        'SIO-SLAM' => FormationFixtures::FORMATION_SIO_SLAM_REF,
        'CIEL-IR' => FormationFixtures::FORMATION_CIEL_IR_REF,
        'SAM' => FormationFixtures::FORMATION_SAM_REF,
    ];

    // Couleurs par BTS (hexadécimal sans #)
    private const BTS_COLORS = [
        'SIO-SISR' => '3498db', // Bleu
        'SIO-SLAM' => '9b59b6', // Violet
        'CIEL-IR' => 'e74c3c',  // Rouge
        'SAM' => '27ae60',      // Vert
    ];

    public function load(ObjectManager $manager): void
    {
        $btsKeys = ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM'];
        $formateurs = $this->getFormateursParBts();

        foreach ($btsKeys as $btsKey) {
            $formation = $this->getReference(self::BTS_FORMATION_MAP[$btsKey]);
            $formateursSession = $formateurs[$btsKey] ?? [];
            
            // ============================================
            // Session active (2024-2026) - En cours, année 1
            // ============================================
            $sessionActive = new Session();
            $sessionActive->setCode('BTS' . str_replace('-', '', $btsKey) . '-2024');
            $sessionActive->setLibelle($formation->getIntituleCourt() . ' - Promotion 2024-2026');
            $sessionActive->setFormation($formation);
            $sessionActive->setDateDebut(new \DateTime('2024-09-02'));
            $sessionActive->setDateFin(new \DateTime('2026-06-30'));
            $sessionActive->setDateDebutInscriptions(new \DateTime('2024-03-01'));
            $sessionActive->setDateFinInscriptions(new \DateTime('2024-09-15'));
            $sessionActive->setEffectifMin(10);
            $sessionActive->setEffectifMax(24);
            $sessionActive->setModalite(Session::MODALITE_PRESENTIEL);
            $sessionActive->setLieu('CFA Demo - Site principal');
            $sessionActive->setStatut(Session::STATUT_EN_COURS);
            $sessionActive->setCouleur(self::BTS_COLORS[$btsKey]);
            $sessionActive->setActif(true);
            
            // Ajouter les formateurs à la session
            foreach ($formateursSession as $formateurRef) {
                $formateur = $this->getReference($formateurRef);
                $sessionActive->addFormateur($formateur);
            }
            
            // Définir le premier formateur technique comme responsable
            if (!empty($formateursSession)) {
                $sessionActive->setResponsable($this->getReference($formateursSession[0]));
            }
            
            $manager->persist($sessionActive);
            $this->addReference(self::SESSION_PREFIX . $btsKey . '-active', $sessionActive);

            // ============================================
            // Session inactive (2023-2025) - Année 2 ou terminée
            // ============================================
            $sessionInactive = new Session();
            $sessionInactive->setCode('BTS' . str_replace('-', '', $btsKey) . '-2023');
            $sessionInactive->setLibelle($formation->getIntituleCourt() . ' - Promotion 2023-2025');
            $sessionInactive->setFormation($formation);
            $sessionInactive->setDateDebut(new \DateTime('2023-09-04'));
            $sessionInactive->setDateFin(new \DateTime('2025-06-30'));
            $sessionInactive->setDateDebutInscriptions(new \DateTime('2023-03-01'));
            $sessionInactive->setDateFinInscriptions(new \DateTime('2023-09-15'));
            $sessionInactive->setEffectifMin(10);
            $sessionInactive->setEffectifMax(24);
            $sessionInactive->setModalite(Session::MODALITE_PRESENTIEL);
            $sessionInactive->setLieu('CFA Demo - Site principal');
            // Cette session est en année 2, donc toujours en cours mais on la marque inactive pour le test
            $sessionInactive->setStatut(Session::STATUT_EN_COURS);
            $sessionInactive->setCouleur(self::BTS_COLORS[$btsKey]);
            $sessionInactive->setActif(false); // Inactive pour les tests
            
            // Mêmes formateurs
            foreach ($formateursSession as $formateurRef) {
                $formateur = $this->getReference($formateurRef);
                $sessionInactive->addFormateur($formateur);
            }
            
            if (!empty($formateursSession)) {
                $sessionInactive->setResponsable($this->getReference($formateursSession[0]));
            }
            
            $manager->persist($sessionInactive);
            $this->addReference(self::SESSION_PREFIX . $btsKey . '-inactive', $sessionInactive);
        }

        $manager->flush();
    }

    /**
     * Récupère les formateurs groupés par BTS
     */
    private function getFormateursParBts(): array
    {
        $formateursData = UserFixtures::getFormateursData();
        $result = [
            'SIO-SISR' => [],
            'SIO-SLAM' => [],
            'CIEL-IR' => [],
            'SAM' => [],
        ];

        foreach ($formateursData as $index => $data) {
            $formateurRef = UserFixtures::FORMATEUR_PREFIX . $index;
            foreach ($data['bts'] as $btsKey) {
                if (isset($result[$btsKey])) {
                    $result[$btsKey][] = $formateurRef;
                }
            }
        }

        return $result;
    }

    public function getDependencies(): array
    {
        return [
            FormationFixtures::class,
            UserFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'sessions'];
    }
}
