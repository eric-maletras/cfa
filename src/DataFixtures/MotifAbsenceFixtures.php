<?php

namespace App\DataFixtures;

use App\Entity\MotifAbsence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les motifs d'absence prédéfinis
 */
class MotifAbsenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $motifs = [
            [
                'libelle' => 'Maladie',
                'code' => 'MALADIE',
                'description' => 'Absence pour raison de santé (rhume, grippe, etc.)',
                'justificatifObligatoire' => true,
                'icone' => '🤒',
                'couleur' => 'warning',
                'ordre' => 1,
            ],
            [
                'libelle' => 'Rendez-vous médical',
                'code' => 'RDV_MEDICAL',
                'description' => 'Consultation médicale, spécialiste, examens',
                'justificatifObligatoire' => true,
                'icone' => '🏥',
                'couleur' => 'info',
                'ordre' => 2,
            ],
            [
                'libelle' => 'Hospitalisation',
                'code' => 'HOSPITALISATION',
                'description' => 'Séjour hospitalier',
                'justificatifObligatoire' => true,
                'icone' => '🏥',
                'couleur' => 'danger',
                'ordre' => 3,
            ],
            [
                'libelle' => 'Problème de transport',
                'code' => 'TRANSPORT',
                'description' => 'Grève, panne, accident sur le trajet',
                'justificatifObligatoire' => false,
                'icone' => '🚗',
                'couleur' => 'secondary',
                'ordre' => 4,
            ],
            [
                'libelle' => 'Événement familial',
                'code' => 'FAMILLE',
                'description' => 'Décès, naissance, mariage dans la famille proche',
                'justificatifObligatoire' => true,
                'icone' => '👨‍👩‍👧',
                'couleur' => 'info',
                'ordre' => 5,
            ],
            [
                'libelle' => 'Convocation officielle',
                'code' => 'CONVOCATION',
                'description' => 'Convocation tribunal, police, administration',
                'justificatifObligatoire' => true,
                'icone' => '⚖️',
                'couleur' => 'warning',
                'ordre' => 6,
            ],
            [
                'libelle' => 'Mission entreprise',
                'code' => 'MISSION_ENTREPRISE',
                'description' => 'Déplacement professionnel, salon, formation entreprise',
                'justificatifObligatoire' => true,
                'icone' => '💼',
                'couleur' => 'success',
                'ordre' => 7,
            ],
            [
                'libelle' => 'Examen / Concours',
                'code' => 'EXAMEN',
                'description' => 'Passage d\'examen ou concours externe',
                'justificatifObligatoire' => true,
                'icone' => '🎓',
                'couleur' => 'success',
                'ordre' => 8,
            ],
            [
                'libelle' => 'Intempéries',
                'code' => 'INTEMPERIES',
                'description' => 'Conditions météo empêchant le déplacement',
                'justificatifObligatoire' => false,
                'icone' => '🌧️',
                'couleur' => 'secondary',
                'ordre' => 9,
            ],
            [
                'libelle' => 'Autre motif justifié',
                'code' => 'AUTRE',
                'description' => 'Autre motif avec justification écrite',
                'justificatifObligatoire' => false,
                'icone' => '❓',
                'couleur' => 'secondary',
                'ordre' => 99,
            ],
        ];

        foreach ($motifs as $data) {
            $motif = new MotifAbsence();
            $motif->setLibelle($data['libelle'])
                  ->setCode($data['code'])
                  ->setDescription($data['description'])
                  ->setJustificatifObligatoire($data['justificatifObligatoire'])
                  ->setIcone($data['icone'])
                  ->setCouleur($data['couleur'])
                  ->setOrdre($data['ordre'])
                  ->setActif(true);

            $manager->persist($motif);
            
            // Référence pour d'autres fixtures si nécessaire
            $this->addReference('motif_' . strtolower($data['code']), $motif);
        }

        $manager->flush();
    }
}
