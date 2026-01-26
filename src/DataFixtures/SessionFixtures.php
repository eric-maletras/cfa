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
 * Crée des sessions pour les formations BTS SIO et initialise automatiquement
 * les matières depuis le référentiel de chaque formation.
 */
class SessionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Références pour les sessions
    public const SESSION_SIO_SLAM_2024_REF = 'session-sio-slam-2024';
    public const SESSION_SIO_SISR_2024_REF = 'session-sio-sisr-2024';
    public const SESSION_SIO_SLAM_2025_REF = 'session-sio-slam-2025';
    public const SESSION_SIO_SISR_2025_REF = 'session-sio-sisr-2025';

    public function load(ObjectManager $manager): void
    {
        $sessions = [
            // Sessions 2024-2026
            [
                'formation_ref' => FormationFixtures::FORMATION_SIO_SLAM_REF,
                'code' => 'BTSSIO-SLAM-2024',
                'libelle' => 'BTS SIO option SLAM - Promotion 2024-2026',
                'dateDebut' => new \DateTime('2024-09-02'),
                'dateFin' => new \DateTime('2026-07-10'),
                'statut' => Session::STATUT_EN_COURS,
                'effectifMin' => 12,
                'effectifMax' => 30,
                'lieu' => 'Campus principal',
                'couleur' => '3498db',
                'ref' => self::SESSION_SIO_SLAM_2024_REF,
            ],
            [
                'formation_ref' => FormationFixtures::FORMATION_SIO_SISR_REF,
                'code' => 'BTSSIO-SISR-2024',
                'libelle' => 'BTS SIO option SISR - Promotion 2024-2026',
                'dateDebut' => new \DateTime('2024-09-02'),
                'dateFin' => new \DateTime('2026-07-10'),
                'statut' => Session::STATUT_EN_COURS,
                'effectifMin' => 12,
                'effectifMax' => 30,
                'lieu' => 'Campus principal',
                'couleur' => '27ae60',
                'ref' => self::SESSION_SIO_SISR_2024_REF,
            ],
            // Sessions 2025-2027 (planifiées)
            [
                'formation_ref' => FormationFixtures::FORMATION_SIO_SLAM_REF,
                'code' => 'BTSSIO-SLAM-2025',
                'libelle' => 'BTS SIO option SLAM - Promotion 2025-2027',
                'dateDebut' => new \DateTime('2025-09-01'),
                'dateFin' => new \DateTime('2027-07-09'),
                'statut' => Session::STATUT_INSCRIPTIONS_OUVERTES,
                'effectifMin' => 12,
                'effectifMax' => 30,
                'lieu' => 'Campus principal',
                'couleur' => '9b59b6',
                'ref' => self::SESSION_SIO_SLAM_2025_REF,
            ],
            [
                'formation_ref' => FormationFixtures::FORMATION_SIO_SISR_REF,
                'code' => 'BTSSIO-SISR-2025',
                'libelle' => 'BTS SIO option SISR - Promotion 2025-2027',
                'dateDebut' => new \DateTime('2025-09-01'),
                'dateFin' => new \DateTime('2027-07-09'),
                'statut' => Session::STATUT_INSCRIPTIONS_OUVERTES,
                'effectifMin' => 12,
                'effectifMax' => 30,
                'lieu' => 'Campus principal',
                'couleur' => 'e67e22',
                'ref' => self::SESSION_SIO_SISR_2025_REF,
            ],
        ];

        foreach ($sessions as $data) {
            /** @var Formation $formation */
            $formation = $this->getReference($data['formation_ref'], Formation::class);

            $session = new Session();
            $session->setFormation($formation);
            $session->setCode($data['code']);
            $session->setLibelle($data['libelle']);
            $session->setDateDebut($data['dateDebut']);
            $session->setDateFin($data['dateFin']);
            $session->setStatut($data['statut']);
            $session->setEffectifMin($data['effectifMin']);
            $session->setEffectifMax($data['effectifMax']);
            $session->setLieu($data['lieu']);
            $session->setCouleur($data['couleur']);
            $session->setActif(true);

            // Initialiser les matières depuis le référentiel de la formation
            $session->initMatieresFromFormation();

            // Simuler quelques heures réalisées pour les sessions en cours
            if ($data['statut'] === Session::STATUT_EN_COURS) {
                foreach ($session->getSessionMatieres() as $sm) {
                    // Simuler environ 40% de réalisation
                    $planifie = $sm->getVolumeHeuresReferentiel();
                    $realise = (int) ($planifie * 0.4 * (0.8 + (mt_rand(0, 40) / 100)));
                    $sm->setVolumeHeuresRealise($realise);
                }
            }

            $manager->persist($session);
            $this->addReference($data['ref'], $session);
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
