<?php

namespace App\DataFixtures;

use App\Entity\NiveauQualification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les niveaux de qualification du cadre national
 * Décret n° 2019-14 du 8 janvier 2019
 */
class NiveauQualificationFixtures extends Fixture implements FixtureGroupInterface
{
    public const NIVEAU_1_REF = 'niveau-1';
    public const NIVEAU_2_REF = 'niveau-2';
    public const NIVEAU_3_REF = 'niveau-3';
    public const NIVEAU_4_REF = 'niveau-4';
    public const NIVEAU_5_REF = 'niveau-5';
    public const NIVEAU_6_REF = 'niveau-6';
    public const NIVEAU_7_REF = 'niveau-7';
    public const NIVEAU_8_REF = 'niveau-8';

    public static function getGroups(): array
    {
        return ['reference', 'niveau'];
    }

    public function load(ObjectManager $manager): void
    {
        $niveaux = $this->getNiveauxData();

        foreach ($niveaux as $data) {
            $niveau = new NiveauQualification();
            $niveau->setCode($data['code']);
            $niveau->setLibelle($data['libelle']);
            $niveau->setEquivalentDiplome($data['equivalent_diplome']);
            $niveau->setDescription($data['description']);
            $niveau->setAncienNiveau($data['ancien_niveau']);
            $niveau->setNiveauCec($data['niveau_cec']);
            $niveau->setActif($data['actif']);

            $manager->persist($niveau);

            // Référence pour d'autres fixtures
            $this->addReference('niveau-' . $data['code'], $niveau);
        }

        $manager->flush();
    }

    private function getNiveauxData(): array
    {
        return [
            [
                'code' => 1,
                'libelle' => 'Niveau 1 - Savoirs de base',
                'equivalent_diplome' => 'Savoirs de base',
                'description' => 'Maîtrise des savoirs de base. Ce niveau correspond à la maîtrise de savoirs généraux de base pouvant contribuer à l\'exercice d\'une activité professionnelle.',
                'ancien_niveau' => null,
                'niveau_cec' => 1,
                'actif' => true,
            ],
            [
                'code' => 2,
                'libelle' => 'Niveau 2 - Infra CAP',
                'equivalent_diplome' => 'Infra CAP (aucune certification enregistrée à ce jour)',
                'description' => 'Maîtrise des savoirs de base et capacité à effectuer des activités simples et résoudre des problèmes courants à l\'aide de règles et d\'outils simples en mobilisant des savoir-faire professionnels dans un contexte structuré. Autonomie dans la réalisation de l\'activité.',
                'ancien_niveau' => null,
                'niveau_cec' => 2,
                'actif' => true,
            ],
            [
                'code' => 3,
                'libelle' => 'Niveau 3 - CAP/BEP',
                'equivalent_diplome' => 'CAP, BEP, Mention complémentaire niveau 3, Titre professionnel niveau 3',
                'description' => 'Maîtrise des savoirs dans un champ d\'activité. Capacité à effectuer des activités combinant des tâches simples et à résoudre des problèmes courants dans un contexte connu. Responsabilité d\'un travail et/ou participation aux décisions dans un groupe restreint.',
                'ancien_niveau' => 'V',
                'niveau_cec' => 3,
                'actif' => true,
            ],
            [
                'code' => 4,
                'libelle' => 'Niveau 4 - Baccalauréat',
                'equivalent_diplome' => 'Baccalauréat (général, technologique, professionnel), BP, BT, Titre professionnel niveau 4',
                'description' => 'Maîtrise de savoirs dans un domaine d\'activité élargi. Capacité à effectuer des activités nécessitant de mobiliser un large éventail de savoirs et savoir-faire dans un contexte changeant. Responsabilité pour la réalisation des activités et participation à l\'évaluation des activités.',
                'ancien_niveau' => 'IV',
                'niveau_cec' => 4,
                'actif' => true,
            ],
            [
                'code' => 5,
                'libelle' => 'Niveau 5 - Bac+2 (BTS/DUT)',
                'equivalent_diplome' => 'BTS, BTSA, DUT, DEUST, Titre professionnel niveau 5',
                'description' => 'Maîtrise des savoir-faire dans un champ d\'activité. Capacité à élaborer des solutions à des problèmes nouveaux, à analyser et interpréter des informations en mobilisant des concepts. Transmission du savoir-faire et des méthodes.',
                'ancien_niveau' => 'III',
                'niveau_cec' => 5,
                'actif' => true,
            ],
            [
                'code' => 6,
                'libelle' => 'Niveau 6 - Licence/BUT',
                'equivalent_diplome' => 'Licence, Licence professionnelle, BUT, Titre professionnel niveau 6',
                'description' => 'Maîtrise approfondie de savoirs hautement spécialisés. Capacité à analyser et résoudre des problèmes complexes imprévus dans un domaine spécifique, à formaliser des savoir-faire et des méthodes et à les capitaliser. Les diplômes conférant le grade de licence sont classés à ce niveau.',
                'ancien_niveau' => 'II',
                'niveau_cec' => 6,
                'actif' => true,
            ],
            [
                'code' => 7,
                'libelle' => 'Niveau 7 - Master/Ingénieur',
                'equivalent_diplome' => 'Master, Diplôme d\'ingénieur, Titre professionnel niveau 7',
                'description' => 'Maîtrise de savoirs très spécialisés, certains au stade le plus avancé des connaissances dans un domaine. Capacité à élaborer et mettre en œuvre des stratégies alternatives pour le développement de l\'activité professionnelle dans des contextes complexes, à évaluer les risques et les conséquences de son activité. Les diplômes conférant le grade de master sont classés à ce niveau.',
                'ancien_niveau' => 'I',
                'niveau_cec' => 7,
                'actif' => true,
            ],
            [
                'code' => 8,
                'libelle' => 'Niveau 8 - Doctorat',
                'equivalent_diplome' => 'Doctorat, Habilitation à diriger des recherches',
                'description' => 'Maîtrise de savoirs à la frontière la plus avancée d\'un domaine et à l\'interface de plusieurs domaines. Capacité à identifier et résoudre des problèmes complexes et nouveaux impliquant une pluralité de domaines, en mobilisant les connaissances et savoir-faire les plus avancés. Conception et pilotage de projets et processus de recherche et d\'innovation.',
                'ancien_niveau' => 'I',
                'niveau_cec' => 8,
                'actif' => true,
            ],
        ];
    }
}
