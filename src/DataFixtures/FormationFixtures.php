<?php

namespace App\DataFixtures;

use App\Entity\Formation;
use App\Entity\NiveauQualification;
use App\Entity\TypeCertification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les formations
 * 
 * 4 BTS créés :
 * - BTS SIO option SISR (Services Informatiques aux Organisations - Systèmes et Réseaux)
 * - BTS SIO option SLAM (Services Informatiques aux Organisations - Développement)
 * - BTS CIEL option IR (Cybersécurité, Informatique et réseaux Électroniques - Informatique et Réseaux)
 * - BTS SAM (Support à l'Action Managériale)
 */
class FormationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Références pour les formations
    public const FORMATION_SIO_SISR_REF = 'formation-sio-sisr';
    public const FORMATION_SIO_SLAM_REF = 'formation-sio-slam';
    public const FORMATION_CIEL_IR_REF = 'formation-ciel-ir';
    public const FORMATION_SAM_REF = 'formation-sam';

    public function load(ObjectManager $manager): void
    {
        // Récupérer les références avec le 2ème argument (classe)
        $niveau5 = $this->getReference('niveau-5', NiveauQualification::class);
        $typeBts = $this->getReference('type-cert-BTS', TypeCertification::class);

        $formations = [
            [
                'intitule' => 'BTS Services Informatiques aux Organisations option Solutions d\'Infrastructure, Systèmes et Réseaux',
                'court' => 'BTS SIO SISR',
                'rncp' => 'RNCP35340',
                'dureeHeures' => 1350,
                'dureeMois' => 24,
                'ects' => 120,
                'options' => ['SISR'],
                'description' => 'Le BTS SIO option SISR forme des professionnels capables de participer à la production et à la fourniture de services informatiques, en intégrant, administrant et sécurisant les infrastructures réseaux.',
                'objectifs' => "- Administrer les systèmes et les réseaux\n- Superviser et sécuriser les accès\n- Exploiter les services et les serveurs\n- Gérer le patrimoine informatique",
                'prerequis' => 'Baccalauréat général, technologique (STMG, STI2D) ou professionnel',
                'debouches' => "Administrateur systèmes et réseaux, Technicien d'infrastructure, Technicien support, Technicien réseaux-télécoms",
                'poursuiteEtudes' => 'Licence professionnelle (ASUR, CYBER), Bachelor informatique, École d\'ingénieurs',
                'dateRncp' => new \DateTime('2020-07-10'),
                'echeanceRncp' => new \DateTime('2025-12-31'),
                'ref' => self::FORMATION_SIO_SISR_REF,
            ],
            [
                'intitule' => 'BTS Services Informatiques aux Organisations option Solutions Logicielles et Applications Métiers',
                'court' => 'BTS SIO SLAM',
                'rncp' => 'RNCP35340',
                'dureeHeures' => 1350,
                'dureeMois' => 24,
                'ects' => 120,
                'options' => ['SLAM'],
                'description' => 'Le BTS SIO option SLAM forme des professionnels capables de définir, concevoir, développer et maintenir des solutions applicatives.',
                'objectifs' => "- Concevoir et développer des applications\n- Assurer la maintenance des applications\n- Gérer les données\n- Participer à la gestion de projet",
                'prerequis' => 'Baccalauréat général, technologique (STMG, STI2D) ou professionnel',
                'debouches' => "Développeur d'applications, Analyste programmeur, Développeur web, Intégrateur web",
                'poursuiteEtudes' => 'Licence professionnelle, Bachelor informatique, École d\'ingénieurs',
                'dateRncp' => new \DateTime('2020-07-10'),
                'echeanceRncp' => new \DateTime('2025-12-31'),
                'ref' => self::FORMATION_SIO_SLAM_REF,
            ],
            [
                'intitule' => 'BTS Cybersécurité, Informatique et réseaux, Électronique option Informatique et Réseaux',
                'court' => 'BTS CIEL IR',
                'rncp' => 'RNCP37391',
                'dureeHeures' => 1400,
                'dureeMois' => 24,
                'ects' => 120,
                'options' => ['IR', 'ER'],
                'description' => 'Le BTS CIEL option IR forme des techniciens supérieurs capables de maintenir et sécuriser les réseaux informatiques, participer à des projets cybersécurité et administrer des systèmes.',
                'objectifs' => "- Étudier et concevoir des réseaux informatiques\n- Exploiter et maintenir des réseaux\n- Valoriser les données et cybersécuriser\n- Organiser une intervention",
                'prerequis' => 'Baccalauréat STI2D, Baccalauréat général (spé NSI, Maths), Bac Pro SN',
                'debouches' => "Technicien cybersécurité, Technicien réseaux, Administrateur systèmes junior, Technicien helpdesk N2",
                'poursuiteEtudes' => 'Licence professionnelle cybersécurité, Bachelor, École d\'ingénieurs',
                'dateRncp' => new \DateTime('2023-03-10'),
                'echeanceRncp' => new \DateTime('2028-03-10'),
                'ref' => self::FORMATION_CIEL_IR_REF,
            ],
            [
                'intitule' => 'BTS Support à l\'Action Managériale',
                'court' => 'BTS SAM',
                'rncp' => 'RNCP34029',
                'dureeHeures' => 1350,
                'dureeMois' => 24,
                'ects' => 120,
                'options' => null,
                'description' => 'Le BTS SAM forme des collaborateurs directs de cadres ou de dirigeants, capables d\'apporter un appui à la conduite de l\'action managériale.',
                'objectifs' => "- Optimiser les processus administratifs\n- Gérer les projets\n- Collaborer à la gestion des ressources humaines\n- Assurer la communication",
                'prerequis' => 'Baccalauréat général, technologique (STMG) ou professionnel',
                'debouches' => "Assistant de direction, Assistant manager, Office manager, Assistant RH",
                'poursuiteEtudes' => 'Licence professionnelle GRH, Management, Bachelor',
                'dateRncp' => new \DateTime('2019-04-05'),
                'echeanceRncp' => new \DateTime('2024-12-31'),
                'ref' => self::FORMATION_SAM_REF,
            ],
        ];

        foreach ($formations as $data) {
            $formation = new Formation();
            $formation->setIntitule($data['intitule']);
            $formation->setIntituleCourt($data['court']);
            $formation->setCodeRncp($data['rncp']);
            $formation->setNiveauQualification($niveau5);
            $formation->setTypeCertification($typeBts);
            $formation->setDureeHeures($data['dureeHeures']);
            $formation->setDureeMois($data['dureeMois']);
            $formation->setEcts($data['ects']);
            $formation->setOptions($data['options']);
            $formation->setDescription($data['description']);
            $formation->setObjectifs($data['objectifs']);
            $formation->setPrerequis($data['prerequis']);
            $formation->setDebouches($data['debouches']);
            $formation->setPoursuiteEtudes($data['poursuiteEtudes']);
            $formation->setDateEnregistrementRncp($data['dateRncp']);
            $formation->setDateEcheanceRncp($data['echeanceRncp']);
            $formation->setActif(true);
            
            $manager->persist($formation);
            $this->addReference($data['ref'], $formation);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            NiveauQualificationFixtures::class,
            TypeCertificationFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'formations'];
    }
}
