<?php

namespace App\DataFixtures;

use App\Entity\Inscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les inscriptions
 * 
 * Crée les inscriptions :
 * - 15 apprentis par session active (validées)
 * - 15 apprentis par session inactive (validées)
 * - Les 5 apprentis "volant" par BTS restent non inscrits
 */
class InscriptionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const INSCRIPTION_PREFIX = 'inscription-';

    // Options par BTS (pour le champ 'option' de l'inscription)
    private const BTS_OPTIONS = [
        'SIO-SISR' => 'SISR',
        'SIO-SLAM' => 'SLAM',
        'CIEL-IR' => 'IR',
        'SAM' => null,
    ];

    public function load(ObjectManager $manager): void
    {
        $btsKeys = ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM'];
        $admin = $this->getReference(UserFixtures::ADMIN_REF);

        foreach ($btsKeys as $btsKey) {
            $option = self::BTS_OPTIONS[$btsKey];
            
            // ============================================
            // Inscriptions session active (2024-2026)
            // ============================================
            $sessionActive = $this->getReference(SessionFixtures::SESSION_PREFIX . $btsKey . '-active');
            
            for ($i = 0; $i < 15; $i++) {
                $apprenti = $this->getReference(UserFixtures::APPRENTI_PREFIX . $btsKey . '-active-' . $i);
                
                $inscription = new Inscription();
                $inscription->setUser($apprenti);
                $inscription->setSession($sessionActive);
                $inscription->setDateInscription(new \DateTime('2024-07-' . str_pad(random_int(1, 31), 2, '0', STR_PAD_LEFT)));
                $inscription->setStatut(Inscription::STATUT_VALIDEE);
                $inscription->setOption($option);
                $inscription->setNumeroContrat('CONTRAT-' . date('Y') . '-' . strtoupper(substr(md5($apprenti->getEmail()), 0, 8)));
                $inscription->setDateDebutEffective(new \DateTime('2024-09-02'));
                $inscription->setCreatedBy($admin);
                
                $manager->persist($inscription);
                $this->addReference(self::INSCRIPTION_PREFIX . $btsKey . '-active-' . $i, $inscription);
            }

            // ============================================
            // Inscriptions session inactive (2023-2025)
            // ============================================
            $sessionInactive = $this->getReference(SessionFixtures::SESSION_PREFIX . $btsKey . '-inactive');
            
            for ($i = 0; $i < 15; $i++) {
                $apprenti = $this->getReference(UserFixtures::APPRENTI_PREFIX . $btsKey . '-inactive-' . $i);
                
                $inscription = new Inscription();
                $inscription->setUser($apprenti);
                $inscription->setSession($sessionInactive);
                $inscription->setDateInscription(new \DateTime('2023-07-' . str_pad(random_int(1, 31), 2, '0', STR_PAD_LEFT)));
                $inscription->setStatut(Inscription::STATUT_VALIDEE);
                $inscription->setOption($option);
                $inscription->setNumeroContrat('CONTRAT-2023-' . strtoupper(substr(md5($apprenti->getEmail()), 0, 8)));
                $inscription->setDateDebutEffective(new \DateTime('2023-09-04'));
                $inscription->setCreatedBy($admin);
                
                $manager->persist($inscription);
                $this->addReference(self::INSCRIPTION_PREFIX . $btsKey . '-inactive-' . $i, $inscription);
            }
        }

        // Note : Les 5 apprentis "non inscrits" par BTS (APPRENTI_NON_INSCRIT_PREFIX)
        // restent volontairement sans inscription pour tester les cas d'apprentis
        // pas encore affectés à une session.

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SessionFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'inscriptions'];
    }
}
