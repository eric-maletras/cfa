<?php

namespace App\DataFixtures;

use App\Entity\Salle;
use App\Entity\TypeSalle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les salles de formation
 * 
 * Salles créées :
 * - A101, A102 : Salles de cours classiques
 * - LABO-IT-1, LABO-IT-2 : Laboratoires informatiques
 * - LABO-OPT-1 : Laboratoire optique (filière optique-lunetterie)
 * - AMPHI-A : Amphithéâtre pour les cours magistraux
 * - VIRTUEL : Salle virtuelle pour le distanciel
 */
class SalleFixtures extends Fixture implements FixtureGroupInterface
{
    // Références pour les salles (utilisables dans d'autres fixtures)
    public const SALLE_A101_REF = 'salle-a101';
    public const SALLE_A102_REF = 'salle-a102';
    public const SALLE_LABO_IT_1_REF = 'salle-labo-it-1';
    public const SALLE_LABO_IT_2_REF = 'salle-labo-it-2';
    public const SALLE_LABO_OPT_1_REF = 'salle-labo-opt-1';
    public const SALLE_AMPHI_A_REF = 'salle-amphi-a';
    public const SALLE_VIRTUEL_REF = 'salle-virtuel';

    /**
     * Définition des salles
     */
    private array $salles = [
        [
            'code' => 'A101',
            'libelle' => 'Salle de cours A101',
            'type' => TypeSalle::SALLE_COURS,
            'capacite' => 30,
            'description' => 'Salle de cours standard au 1er étage du bâtiment A. Équipée d\'un vidéoprojecteur et d\'un tableau blanc.',
            'ref' => self::SALLE_A101_REF,
        ],
        [
            'code' => 'A102',
            'libelle' => 'Salle de cours A102',
            'type' => TypeSalle::SALLE_COURS,
            'capacite' => 25,
            'description' => 'Salle de cours au 1er étage du bâtiment A. Vidéoprojecteur, tableau blanc interactif.',
            'ref' => self::SALLE_A102_REF,
        ],
        [
            'code' => 'LABO-IT-1',
            'libelle' => 'Laboratoire informatique 1',
            'type' => TypeSalle::LABO_INFO,
            'capacite' => 16,
            'description' => 'Laboratoire informatique principal. 16 postes Windows 11, switch Cisco, baie de brassage pédagogique. Idéal pour les TP réseaux et systèmes.',
            'ref' => self::SALLE_LABO_IT_1_REF,
        ],
        [
            'code' => 'LABO-IT-2',
            'libelle' => 'Laboratoire informatique 2',
            'type' => TypeSalle::LABO_INFO,
            'capacite' => 18,
            'description' => 'Laboratoire informatique secondaire. 18 postes dual-boot Windows/Linux. Serveur ESXi local pour la virtualisation.',
            'ref' => self::SALLE_LABO_IT_2_REF,
        ],
        [
            'code' => 'LABO-OPT-1',
            'libelle' => 'Laboratoire optique 1',
            'type' => TypeSalle::LABO_OPTIQUE,
            'capacite' => 12,
            'description' => 'Atelier d\'optique-lunetterie. Équipé de matériel de montage, meuleuses, frontofocomètre, réfractomètre.',
            'ref' => self::SALLE_LABO_OPT_1_REF,
        ],
        [
            'code' => 'AMPHI-A',
            'libelle' => 'Amphithéâtre A',
            'type' => TypeSalle::AMPHI,
            'capacite' => 80,
            'description' => 'Amphithéâtre principal pour les cours magistraux, conférences et réunions plénières. Système de sonorisation, double vidéoprojecteur.',
            'ref' => self::SALLE_AMPHI_A_REF,
        ],
        [
            'code' => 'VIRTUEL',
            'libelle' => 'Classe virtuelle (distanciel)',
            'type' => TypeSalle::VIRTUEL,
            'capacite' => null, // Capacité illimitée
            'description' => 'Salle virtuelle pour les formations à distance. Utilisée pour les cours en visioconférence (Teams, Zoom, BigBlueButton).',
            'ref' => self::SALLE_VIRTUEL_REF,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->salles as $data) {
            $salle = new Salle();
            $salle->setCode($data['code']);
            $salle->setLibelle($data['libelle']);
            $salle->setType($data['type']);
            $salle->setCapacite($data['capacite']);
            $salle->setDescription($data['description']);
            $salle->setActif(true);

            $manager->persist($salle);
            $this->addReference($data['ref'], $salle);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['base', 'salles', 'planning'];
    }
}
