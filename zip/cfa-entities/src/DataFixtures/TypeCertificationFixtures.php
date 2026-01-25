<?php

namespace App\DataFixtures;

use App\Entity\TypeCertification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les types de certifications professionnelles
 * Source : France Compétences, Code du travail
 */
class TypeCertificationFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['reference', 'type_certification'];
    }

    public function load(ObjectManager $manager): void
    {
        $types = $this->getTypesData();

        foreach ($types as $data) {
            $type = new TypeCertification();
            $type->setCode($data['code']);
            $type->setLibelle($data['libelle']);
            $type->setLibelleAbrege($data['libelle_abrege']);
            $type->setCertificateurType($data['certificateur_type']);
            $type->setCertificateurNom($data['certificateur_nom']);
            $type->setEnregistrementRncp($data['enregistrement_rncp']);
            $type->setApprentissagePossible($data['apprentissage_possible']);
            $type->setVaePossible($data['vae_possible']);
            $type->setDescription($data['description']);
            $type->setOrdreAffichage($data['ordre_affichage']);
            $type->setActif(true);

            $manager->persist($type);

            // Référence pour d'autres fixtures
            $this->addReference('type-cert-' . $data['code'], $type);
        }

        $manager->flush();
    }

    private function getTypesData(): array
    {
        return [
            // === DIPLÔMES D'ÉTAT (Ministère Éducation nationale / Enseignement supérieur) ===
            [
                'code' => 'CAP',
                'libelle' => 'Certificat d\'aptitude professionnelle',
                'libelle_abrege' => 'CAP',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 3 qui donne une qualification d\'ouvrier qualifié ou d\'employé qualifié dans un métier déterminé.',
                'ordre_affichage' => 10,
            ],
            [
                'code' => 'BEP',
                'libelle' => 'Brevet d\'études professionnelles',
                'libelle_abrege' => 'BEP',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 3 intermédiaire dans le parcours du baccalauréat professionnel.',
                'ordre_affichage' => 11,
            ],
            [
                'code' => 'MC',
                'libelle' => 'Mention complémentaire',
                'libelle_abrege' => 'MC',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme qui permet d\'acquérir une spécialisation complémentaire à un CAP ou un baccalauréat professionnel.',
                'ordre_affichage' => 12,
            ],
            [
                'code' => 'BP',
                'libelle' => 'Brevet professionnel',
                'libelle_abrege' => 'BP',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 4 qui atteste l\'acquisition d\'une haute qualification dans l\'exercice d\'une activité professionnelle.',
                'ordre_affichage' => 20,
            ],
            [
                'code' => 'BAC_PRO',
                'libelle' => 'Baccalauréat professionnel',
                'libelle_abrege' => 'Bac Pro',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 4 qui atteste d\'une qualification professionnelle permettant l\'insertion professionnelle ou la poursuite d\'études.',
                'ordre_affichage' => 21,
            ],
            [
                'code' => 'BAC_TECHNO',
                'libelle' => 'Baccalauréat technologique',
                'libelle_abrege' => 'Bac Techno',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Éducation nationale',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => false,
                'vae_possible' => false,
                'description' => 'Diplôme national de niveau 4 à dominante technologique préparant à la poursuite d\'études supérieures.',
                'ordre_affichage' => 22,
            ],
            [
                'code' => 'BTS',
                'libelle' => 'Brevet de technicien supérieur',
                'libelle_abrege' => 'BTS',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 5 (Bac+2) qui permet d\'acquérir des compétences dans un domaine professionnel précis.',
                'ordre_affichage' => 30,
            ],
            [
                'code' => 'BTSA',
                'libelle' => 'Brevet de technicien supérieur agricole',
                'libelle_abrege' => 'BTSA',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Agriculture et de la Souveraineté alimentaire',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 5 (Bac+2) dans les domaines agricoles, agroalimentaires et environnementaux.',
                'ordre_affichage' => 31,
            ],
            [
                'code' => 'DUT',
                'libelle' => 'Diplôme universitaire de technologie',
                'libelle_abrege' => 'DUT',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 5 (Bac+2) préparé en IUT. Remplacé progressivement par le BUT.',
                'ordre_affichage' => 32,
            ],
            [
                'code' => 'BUT',
                'libelle' => 'Bachelor universitaire de technologie',
                'libelle_abrege' => 'BUT',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 6 (Bac+3) préparé en IUT, remplaçant le DUT depuis 2021.',
                'ordre_affichage' => 40,
            ],
            [
                'code' => 'LICENCE',
                'libelle' => 'Licence',
                'libelle_abrege' => 'Licence',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 6 (Bac+3) délivré par les universités.',
                'ordre_affichage' => 41,
            ],
            [
                'code' => 'LP',
                'libelle' => 'Licence professionnelle',
                'libelle_abrege' => 'LP',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 6 (Bac+3) à finalité professionnelle, préparé en un an après un Bac+2.',
                'ordre_affichage' => 42,
            ],
            [
                'code' => 'MASTER',
                'libelle' => 'Master',
                'libelle_abrege' => 'Master',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 7 (Bac+5) délivré par les universités.',
                'ordre_affichage' => 50,
            ],
            [
                'code' => 'INGENIEUR',
                'libelle' => 'Diplôme d\'ingénieur',
                'libelle_abrege' => 'Ingénieur',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Commission des Titres d\'Ingénieur (CTI)',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme de niveau 7 (Bac+5) délivré par les écoles d\'ingénieurs habilitées par la CTI.',
                'ordre_affichage' => 51,
            ],
            [
                'code' => 'DOCTORAT',
                'libelle' => 'Doctorat',
                'libelle_abrege' => 'Doctorat',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme national de niveau 8 (Bac+8), plus haut grade universitaire.',
                'ordre_affichage' => 60,
            ],
            
            // === DIPLÔMES D'ÉTAT AUTRES MINISTÈRES ===
            [
                'code' => 'DE',
                'libelle' => 'Diplôme d\'État',
                'libelle_abrege' => 'DE',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministères (Santé, Affaires sociales, Jeunesse et Sports...)',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme délivré par l\'État dans les domaines de la santé, du social, du sport et de l\'animation.',
                'ordre_affichage' => 70,
            ],
            [
                'code' => 'BPJEPS',
                'libelle' => 'Brevet professionnel de la jeunesse, de l\'éducation populaire et du sport',
                'libelle_abrege' => 'BPJEPS',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère des Sports',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme de niveau 4 délivré par le ministère des Sports pour l\'animation et l\'encadrement sportif.',
                'ordre_affichage' => 71,
            ],
            [
                'code' => 'DEJEPS',
                'libelle' => 'Diplôme d\'État de la jeunesse, de l\'éducation populaire et du sport',
                'libelle_abrege' => 'DEJEPS',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère des Sports',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme de niveau 5 pour le perfectionnement sportif et l\'animation socio-éducative.',
                'ordre_affichage' => 72,
            ],

            // === TITRES PROFESSIONNELS (Ministère du Travail) ===
            [
                'code' => 'TP',
                'libelle' => 'Titre professionnel',
                'libelle_abrege' => 'TP',
                'certificateur_type' => 'ministere',
                'certificateur_nom' => 'Ministère du Travail, du Plein emploi et de l\'Insertion',
                'enregistrement_rncp' => 'de_droit',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Certification professionnelle délivrée par le ministère du Travail, attestant de compétences professionnelles opérationnelles. Composé de blocs de compétences (CCP).',
                'ordre_affichage' => 80,
            ],

            // === TITRES À FINALITÉ PROFESSIONNELLE (Organismes privés/consulaires) ===
            [
                'code' => 'TFP',
                'libelle' => 'Titre à finalité professionnelle',
                'libelle_abrege' => 'TFP',
                'certificateur_type' => 'organisme_prive',
                'certificateur_nom' => 'Organismes de formation privés',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Certification professionnelle délivrée par des organismes privés, enregistrée au RNCP sur demande après instruction par France Compétences.',
                'ordre_affichage' => 90,
            ],
            [
                'code' => 'TFP_CCI',
                'libelle' => 'Titre à finalité professionnelle CCI',
                'libelle_abrege' => 'TFP CCI',
                'certificateur_type' => 'consulaire',
                'certificateur_nom' => 'CCI France (Chambres de Commerce et d\'Industrie)',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Certification délivrée par les Chambres de Commerce et d\'Industrie.',
                'ordre_affichage' => 91,
            ],
            [
                'code' => 'TFP_CMA',
                'libelle' => 'Titre à finalité professionnelle CMA',
                'libelle_abrege' => 'TFP CMA',
                'certificateur_type' => 'consulaire',
                'certificateur_nom' => 'CMA France (Chambres de Métiers et de l\'Artisanat)',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Certification délivrée par les Chambres de Métiers et de l\'Artisanat.',
                'ordre_affichage' => 92,
            ],
            [
                'code' => 'BM',
                'libelle' => 'Brevet de maîtrise',
                'libelle_abrege' => 'BM',
                'certificateur_type' => 'consulaire',
                'certificateur_nom' => 'CMA France (Chambres de Métiers et de l\'Artisanat)',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => true,
                'vae_possible' => true,
                'description' => 'Diplôme de niveau 5 délivré par les CMA, sanctionnant une double qualification : chef d\'entreprise et formateur.',
                'ordre_affichage' => 93,
            ],

            // === CERTIFICATS DE QUALIFICATION PROFESSIONNELLE (Branches) ===
            [
                'code' => 'CQP',
                'libelle' => 'Certificat de qualification professionnelle',
                'libelle_abrege' => 'CQP',
                'certificateur_type' => 'branche',
                'certificateur_nom' => 'Commissions Paritaires Nationales de l\'Emploi (CPNE)',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => false,
                'vae_possible' => true,
                'description' => 'Certification créée et délivrée par une branche professionnelle, attestant de compétences propres à un métier de la branche.',
                'ordre_affichage' => 100,
            ],
            [
                'code' => 'CQPI',
                'libelle' => 'Certificat de qualification professionnelle inter-branches',
                'libelle_abrege' => 'CQPI',
                'certificateur_type' => 'branche',
                'certificateur_nom' => 'Plusieurs Commissions Paritaires Nationales de l\'Emploi',
                'enregistrement_rncp' => 'sur_demande',
                'apprentissage_possible' => false,
                'vae_possible' => true,
                'description' => 'Certification inter-branches pour des métiers transversaux à plusieurs secteurs d\'activité.',
                'ordre_affichage' => 101,
            ],

            // === CERTIFICATIONS DU RÉPERTOIRE SPÉCIFIQUE ===
            [
                'code' => 'HABILITATION',
                'libelle' => 'Habilitation',
                'libelle_abrege' => 'Habilitation',
                'certificateur_type' => 'organisme_prive',
                'certificateur_nom' => 'Divers organismes certificateurs',
                'enregistrement_rncp' => 'non_applicable',
                'apprentissage_possible' => false,
                'vae_possible' => false,
                'description' => 'Certification obligatoire pour l\'exercice de certaines activités (ex: CACES, habilitation électrique). Enregistrée au Répertoire Spécifique.',
                'ordre_affichage' => 110,
            ],
            [
                'code' => 'CERT_COMP',
                'libelle' => 'Certification de compétences',
                'libelle_abrege' => 'Cert. Comp.',
                'certificateur_type' => 'organisme_prive',
                'certificateur_nom' => 'Divers organismes certificateurs',
                'enregistrement_rncp' => 'non_applicable',
                'apprentissage_possible' => false,
                'vae_possible' => false,
                'description' => 'Certification portant sur des compétences transversales ou complémentaires (ex: CléA, certifications linguistiques). Enregistrée au Répertoire Spécifique.',
                'ordre_affichage' => 111,
            ],
        ];
    }
}
