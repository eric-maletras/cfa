<?php

namespace App\DataFixtures;

use App\Entity\Formation;
use App\Entity\FormationMatiere;
use App\Entity\Matiere;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les matières et leurs liaisons avec les formations BTS SIO
 * 
 * Matières créées (référentiel BTS SIO) :
 * - MATH : Mathématiques pour l'informatique
 * - ANGL : Anglais
 * - CULT : Culture générale et expression
 * - CEJM : Culture économique, juridique et managériale
 * - SI : Support et mise à disposition de services informatiques
 * - SLAM : Solutions logicielles et applications métiers
 * - SISR : Administration des systèmes et des réseaux
 * - CYBER-SLAM : Cybersécurité (option SLAM)
 * - CYBER-SISR : Cybersécurité (option SISR)
 */
class MatiereFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Références pour les matières
    public const MATIERE_MATH_REF = 'matiere-math';
    public const MATIERE_ANGL_REF = 'matiere-angl';
    public const MATIERE_CULT_REF = 'matiere-cult';
    public const MATIERE_CEJM_REF = 'matiere-cejm';
    public const MATIERE_SI_REF = 'matiere-si';
    public const MATIERE_SLAM_REF = 'matiere-slam';
    public const MATIERE_SISR_REF = 'matiere-sisr';
    public const MATIERE_CYBER_SLAM_REF = 'matiere-cyber-slam';
    public const MATIERE_CYBER_SISR_REF = 'matiere-cyber-sisr';

    /**
     * Définition des matières
     */
    private array $matieres = [
        [
            'code' => 'MATH',
            'libelle' => 'Mathématiques pour l\'informatique',
            'description' => 'Mathématiques appliquées à l\'informatique : algèbre, arithmétique, logique, analyse.',
            'ref' => self::MATIERE_MATH_REF,
        ],
        [
            'code' => 'ANGL',
            'libelle' => 'Anglais',
            'description' => 'Anglais professionnel et technique appliqué au domaine informatique.',
            'ref' => self::MATIERE_ANGL_REF,
        ],
        [
            'code' => 'CULT',
            'libelle' => 'Culture générale et expression',
            'description' => 'Expression écrite et orale, synthèse de documents, argumentation.',
            'ref' => self::MATIERE_CULT_REF,
        ],
        [
            'code' => 'CEJM',
            'libelle' => 'Culture économique, juridique et managériale',
            'description' => 'Environnement économique et juridique de l\'entreprise, management.',
            'ref' => self::MATIERE_CEJM_REF,
        ],
        [
            'code' => 'SI',
            'libelle' => 'Support et mise à disposition de services informatiques',
            'description' => 'Bloc 1 commun aux deux options : support utilisateurs, mise à disposition de services.',
            'ref' => self::MATIERE_SI_REF,
        ],
        [
            'code' => 'SLAM',
            'libelle' => 'Solutions logicielles et applications métiers',
            'description' => 'Bloc 2 option SLAM : conception, développement et maintenance d\'applications.',
            'ref' => self::MATIERE_SLAM_REF,
        ],
        [
            'code' => 'SISR',
            'libelle' => 'Administration des systèmes et des réseaux',
            'description' => 'Bloc 2 option SISR : administration systèmes, réseaux et services d\'infrastructure.',
            'ref' => self::MATIERE_SISR_REF,
        ],
        [
            'code' => 'CYBER-SLAM',
            'libelle' => 'Cybersécurité des services informatiques (SLAM)',
            'description' => 'Bloc 3 option SLAM : sécurisation des applications et des données.',
            'ref' => self::MATIERE_CYBER_SLAM_REF,
        ],
        [
            'code' => 'CYBER-SISR',
            'libelle' => 'Cybersécurité des services informatiques (SISR)',
            'description' => 'Bloc 3 option SISR : sécurisation des infrastructures et des réseaux.',
            'ref' => self::MATIERE_CYBER_SISR_REF,
        ],
    ];

    /**
     * Volumes horaires par matière et par formation (sur 2 ans)
     * Format: [référence_matière => [référence_formation => [volume, coefficient, ordre]]]
     */
    private array $liaisons = [
        // BTS SIO SLAM
        FormationFixtures::FORMATION_SIO_SLAM_REF => [
            self::MATIERE_CULT_REF      => ['volume' => 120, 'coef' => 2.0, 'ordre' => 0],
            self::MATIERE_ANGL_REF      => ['volume' => 120, 'coef' => 2.0, 'ordre' => 1],
            self::MATIERE_MATH_REF      => ['volume' => 90,  'coef' => 2.0, 'ordre' => 2],
            self::MATIERE_CEJM_REF      => ['volume' => 120, 'coef' => 3.0, 'ordre' => 3],
            self::MATIERE_SI_REF        => ['volume' => 240, 'coef' => 4.0, 'ordre' => 4],
            self::MATIERE_SLAM_REF      => ['volume' => 280, 'coef' => 4.0, 'ordre' => 5],
            self::MATIERE_CYBER_SLAM_REF => ['volume' => 70, 'coef' => 4.0, 'ordre' => 6],
        ],
        // BTS SIO SISR
        FormationFixtures::FORMATION_SIO_SISR_REF => [
            self::MATIERE_CULT_REF      => ['volume' => 120, 'coef' => 2.0, 'ordre' => 0],
            self::MATIERE_ANGL_REF      => ['volume' => 120, 'coef' => 2.0, 'ordre' => 1],
            self::MATIERE_MATH_REF      => ['volume' => 90,  'coef' => 2.0, 'ordre' => 2],
            self::MATIERE_CEJM_REF      => ['volume' => 120, 'coef' => 3.0, 'ordre' => 3],
            self::MATIERE_SI_REF        => ['volume' => 240, 'coef' => 4.0, 'ordre' => 4],
            self::MATIERE_SISR_REF      => ['volume' => 280, 'coef' => 4.0, 'ordre' => 5],
            self::MATIERE_CYBER_SISR_REF => ['volume' => 70, 'coef' => 4.0, 'ordre' => 6],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        // 1. Créer les matières
        foreach ($this->matieres as $data) {
            $matiere = new Matiere();
            $matiere->setCode($data['code']);
            $matiere->setLibelle($data['libelle']);
            $matiere->setDescription($data['description']);
            $matiere->setActif(true);

            $manager->persist($matiere);
            $this->addReference($data['ref'], $matiere);
        }

        $manager->flush();

        // 2. Créer les liaisons Formation-Matière
        foreach ($this->liaisons as $formationRef => $matieresDeLaFormation) {
            /** @var Formation $formation */
            $formation = $this->getReference($formationRef, Formation::class);

            foreach ($matieresDeLaFormation as $matiereRef => $params) {
                /** @var Matiere $matiere */
                $matiere = $this->getReference($matiereRef, Matiere::class);

                $formationMatiere = new FormationMatiere();
                $formationMatiere->setFormation($formation);
                $formationMatiere->setMatiere($matiere);
                $formationMatiere->setVolumeHeuresReferentiel($params['volume']);
                $formationMatiere->setCoefficient((string) $params['coef']);
                $formationMatiere->setOrdre($params['ordre']);

                $manager->persist($formationMatiere);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FormationFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'matieres'];
    }
}
