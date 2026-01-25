<?php

namespace App\DataFixtures;

use App\Entity\CodeROME;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les codes ROME (Répertoire Opérationnel des Métiers et des Emplois)
 * Source : France Travail (ex-Pôle Emploi)
 * Version : ROME 4.0 (mars 2023)
 */
class CodeROMEFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['reference', 'rome'];
    }

    public function load(ObjectManager $manager): void
    {
        $codesRome = $this->getCodesRomeData();

        foreach ($codesRome as $data) {
            $rome = new CodeROME();
            $rome->setCode($data['code']);
            $rome->setLibelle($data['libelle']);
            $rome->setDomaineCode($data['domaine_code']);
            $rome->setDomaineLibelle($data['domaine_libelle']);
            $rome->setSousDomaineCode($data['sous_domaine_code'] ?? null);
            $rome->setSousDomaineLibelle($data['sous_domaine_libelle'] ?? null);
            $rome->setDefinition($data['definition'] ?? null);
            $rome->setVersionRome('4.0');
            $rome->setActif(true);

            $manager->persist($rome);

            // Référence pour d'autres fixtures
            $this->addReference('rome-' . $data['code'], $rome);
        }

        $manager->flush();
    }

    private function getCodesRomeData(): array
    {
        return [
            // ============================================================
            // DOMAINE I - Installation et maintenance
            // ============================================================
            [
                'code' => 'I1401',
                'libelle' => 'Maintenance informatique et bureautique',
                'domaine_code' => 'I',
                'domaine_libelle' => 'Installation et maintenance',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Maintenance',
                'definition' => 'Effectue le dépannage, l\'entretien et l\'installation d\'équipements ou de parcs d\'équipements informatiques ou bureautiques, selon les règles de sécurité et la réglementation.',
            ],

            // ============================================================
            // DOMAINE M - Support à l'entreprise (INFORMATIQUE)
            // ============================================================
            [
                'code' => 'M1801',
                'libelle' => 'Administration de systèmes d\'information',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Administre et assure le fonctionnement et l\'exploitation d\'un ou plusieurs éléments matériels ou logiciels de l\'infrastructure d\'un système d\'information ou d\'un réseau de télécommunications.',
            ],
            [
                'code' => 'M1802',
                'libelle' => 'Expertise et support en systèmes d\'information',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Apporte un appui technique aux utilisateurs d\'un système d\'information. Identifie, diagnostique et résout les dysfonctionnements.',
            ],
            [
                'code' => 'M1803',
                'libelle' => 'Direction des systèmes d\'information',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Définit et met en œuvre la politique informatique de l\'entreprise en cohérence avec la stratégie générale.',
            ],
            [
                'code' => 'M1804',
                'libelle' => 'Études et développement de réseaux de télécoms',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Étudie, conçoit et développe des solutions techniques de réseaux de télécommunication.',
            ],
            [
                'code' => 'M1805',
                'libelle' => 'Études et développement informatique',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Conçoit, développe et met au point un projet d\'application informatique, de la phase d\'étude à son intégration, pour un client ou une entreprise.',
            ],
            [
                'code' => 'M1806',
                'libelle' => 'Conseil et maîtrise d\'ouvrage en systèmes d\'information',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Conseille la direction informatique, les directions fonctionnelles de l\'entreprise dans le cadre de l\'élaboration des orientations stratégiques des systèmes d\'information.',
            ],
            [
                'code' => 'M1810',
                'libelle' => 'Production et exploitation de systèmes d\'information',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Assure l\'exploitation d\'une ou plusieurs applications informatiques au sein d\'un centre de production informatique.',
            ],
            [
                'code' => 'M1811',
                'libelle' => 'Data science',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Collecte, traite, analyse et valorise des données massives (big data) pour en extraire des informations utiles à la prise de décision.',
            ],
            [
                'code' => 'M1812',
                'libelle' => 'Cybersécurité',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Systèmes d\'information et de télécommunication',
                'definition' => 'Analyse les risques et vulnérabilités des systèmes d\'information et met en œuvre les mesures de sécurité adaptées.',
            ],

            // === Autres métiers du support à l'entreprise (gestion, commerce) ===
            [
                'code' => 'M1203',
                'libelle' => 'Comptabilité',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Gestion et comptabilité',
                'definition' => 'Enregistre et centralise les données commerciales, industrielles ou financières d\'une structure pour établir des balances de comptes, comptes de résultat, bilans.',
            ],
            [
                'code' => 'M1204',
                'libelle' => 'Contrôle de gestion',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Gestion et comptabilité',
                'definition' => 'Contrôle et analyse la conformité des procédures de gestion de l\'entreprise, élabore des indicateurs et tableaux de bord.',
            ],
            [
                'code' => 'M1501',
                'libelle' => 'Assistanat en ressources humaines',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '15',
                'sous_domaine_libelle' => 'Ressources humaines',
                'definition' => 'Réalise le suivi administratif de la gestion du personnel selon la législation sociale, la réglementation du travail et la politique des ressources humaines.',
            ],
            [
                'code' => 'M1502',
                'libelle' => 'Développement des ressources humaines',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '15',
                'sous_domaine_libelle' => 'Ressources humaines',
                'definition' => 'Définit et met en œuvre la politique de management et de gestion des ressources humaines de la structure.',
            ],
            [
                'code' => 'M1602',
                'libelle' => 'Opérations administratives',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '16',
                'sous_domaine_libelle' => 'Secrétariat et assistanat',
                'definition' => 'Réalise des opérations de gestion administrative et comptable selon les procédures de l\'organisation.',
            ],
            [
                'code' => 'M1604',
                'libelle' => 'Assistanat de direction',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '16',
                'sous_domaine_libelle' => 'Secrétariat et assistanat',
                'definition' => 'Assiste un ou plusieurs responsables dans l\'organisation de leur travail quotidien.',
            ],
            [
                'code' => 'M1607',
                'libelle' => 'Secrétariat',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '16',
                'sous_domaine_libelle' => 'Secrétariat et assistanat',
                'definition' => 'Réalise les travaux courants de secrétariat selon les directives données.',
            ],
            [
                'code' => 'M1705',
                'libelle' => 'Marketing',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '17',
                'sous_domaine_libelle' => 'Marketing et communication',
                'definition' => 'Définit et met en œuvre la stratégie marketing de l\'entreprise.',
            ],
            [
                'code' => 'M1707',
                'libelle' => 'Stratégie commerciale',
                'domaine_code' => 'M',
                'domaine_libelle' => 'Support à l\'entreprise',
                'sous_domaine_code' => '17',
                'sous_domaine_libelle' => 'Marketing et communication',
                'definition' => 'Définit et met en œuvre la stratégie commerciale d\'une entreprise.',
            ],

            // ============================================================
            // DOMAINE D - Commerce, vente et grande distribution
            // ============================================================
            [
                'code' => 'D1214',
                'libelle' => 'Vente en habillement et accessoires de la personne',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Commerce de détail',
                'definition' => 'Réalise la vente d\'articles vestimentaires et d\'accessoires auprès d\'une clientèle de particuliers.',
            ],
            [
                'code' => 'D1401',
                'libelle' => 'Assistanat commercial',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Force de vente',
                'definition' => 'Réalise le traitement commercial et administratif des commandes des clients dans un objectif de qualité.',
            ],
            [
                'code' => 'D1402',
                'libelle' => 'Relation commerciale grands comptes et entreprises',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Force de vente',
                'definition' => 'Réalise l\'ensemble des activités de prospection, de vente et d\'accompagnement de grands comptes ou d\'entreprises.',
            ],
            [
                'code' => 'D1403',
                'libelle' => 'Relation commerciale auprès de particuliers',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Force de vente',
                'definition' => 'Réalise des activités de prospection, de conseil et de vente de produits ou services auprès de particuliers.',
            ],
            [
                'code' => 'D1406',
                'libelle' => 'Management en force de vente',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Force de vente',
                'definition' => 'Encadre une équipe de commerciaux et met en œuvre la politique commerciale de l\'entreprise.',
            ],
            [
                'code' => 'D1501',
                'libelle' => 'Animation de vente',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '15',
                'sous_domaine_libelle' => 'Promotion des ventes',
                'definition' => 'Réalise des opérations d\'animation commerciale de produits ou de services.',
            ],
            [
                'code' => 'D1506',
                'libelle' => 'Marchandisage',
                'domaine_code' => 'D',
                'domaine_libelle' => 'Commerce, vente et grande distribution',
                'sous_domaine_code' => '15',
                'sous_domaine_libelle' => 'Promotion des ventes',
                'definition' => 'Organise et met en œuvre les actions de présentation, de mise en valeur des produits.',
            ],

            // ============================================================
            // DOMAINE E - Communication, média et multimédia
            // ============================================================
            [
                'code' => 'E1101',
                'libelle' => 'Animation de site multimédia',
                'domaine_code' => 'E',
                'domaine_libelle' => 'Communication, média et multimédia',
                'sous_domaine_code' => '11',
                'sous_domaine_libelle' => 'Conception et gestion de contenu',
                'definition' => 'Anime un site internet ou multimédia et assure la mise en ligne des contenus.',
            ],
            [
                'code' => 'E1104',
                'libelle' => 'Conception de contenus multimédias',
                'domaine_code' => 'E',
                'domaine_libelle' => 'Communication, média et multimédia',
                'sous_domaine_code' => '11',
                'sous_domaine_libelle' => 'Conception et gestion de contenu',
                'definition' => 'Conçoit et réalise des contenus multimédias (texte, image, son, vidéo) pour différents supports.',
            ],
            [
                'code' => 'E1205',
                'libelle' => 'Réalisation de contenus multimédias',
                'domaine_code' => 'E',
                'domaine_libelle' => 'Communication, média et multimédia',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Production de contenu',
                'definition' => 'Réalise des contenus multimédias (audiovisuels, graphiques, interactifs) selon les besoins du client.',
            ],

            // ============================================================
            // DOMAINE H - Industrie
            // ============================================================
            [
                'code' => 'H1206',
                'libelle' => 'Management et ingénierie études, recherche et développement industriel',
                'domaine_code' => 'H',
                'domaine_libelle' => 'Industrie',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Conception et études',
                'definition' => 'Supervise et coordonne les activités d\'études et de développement de produits industriels.',
            ],
            [
                'code' => 'H1302',
                'libelle' => 'Management et ingénierie Hygiène Sécurité Environnement -HSE- industriels',
                'domaine_code' => 'H',
                'domaine_libelle' => 'Industrie',
                'sous_domaine_code' => '13',
                'sous_domaine_libelle' => 'Qualité et méthodes',
                'definition' => 'Définit et met en œuvre la politique de sécurité et d\'environnement de l\'entreprise.',
            ],
            [
                'code' => 'H1402',
                'libelle' => 'Management et ingénierie méthodes et industrialisation',
                'domaine_code' => 'H',
                'domaine_libelle' => 'Industrie',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Organisation industrielle',
                'definition' => 'Définit et optimise les moyens et méthodes de production industrielle.',
            ],

            // ============================================================
            // DOMAINE N - Transport et logistique
            // ============================================================
            [
                'code' => 'N1301',
                'libelle' => 'Conception et organisation de la chaîne logistique',
                'domaine_code' => 'N',
                'domaine_libelle' => 'Transport et logistique',
                'sous_domaine_code' => '13',
                'sous_domaine_libelle' => 'Logistique',
                'definition' => 'Conçoit et optimise les flux de marchandises et d\'informations de la chaîne logistique.',
            ],
            [
                'code' => 'N1302',
                'libelle' => 'Direction de site logistique',
                'domaine_code' => 'N',
                'domaine_libelle' => 'Transport et logistique',
                'sous_domaine_code' => '13',
                'sous_domaine_libelle' => 'Logistique',
                'definition' => 'Dirige un site logistique et coordonne les activités de réception, stockage et expédition.',
            ],
            [
                'code' => 'N1303',
                'libelle' => 'Intervention technique d\'exploitation logistique',
                'domaine_code' => 'N',
                'domaine_libelle' => 'Transport et logistique',
                'sous_domaine_code' => '13',
                'sous_domaine_libelle' => 'Logistique',
                'definition' => 'Réalise les opérations de réception, de stockage, de préparation de commandes et d\'expédition.',
            ],

            // ============================================================
            // DOMAINE K - Services à la personne et à la collectivité
            // ============================================================
            [
                'code' => 'K2111',
                'libelle' => 'Formation professionnelle',
                'domaine_code' => 'K',
                'domaine_libelle' => 'Services à la personne et à la collectivité',
                'sous_domaine_code' => '21',
                'sous_domaine_libelle' => 'Enseignement et formation',
                'definition' => 'Réalise des actions de formation auprès d\'un public d\'adultes ou de jeunes en insertion professionnelle.',
            ],
            [
                'code' => 'K2401',
                'libelle' => 'Recherche en sciences de l\'homme et de la société',
                'domaine_code' => 'K',
                'domaine_libelle' => 'Services à la personne et à la collectivité',
                'sous_domaine_code' => '24',
                'sous_domaine_libelle' => 'Recherche',
                'definition' => 'Conduit des recherches fondamentales ou appliquées dans le domaine des sciences humaines et sociales.',
            ],

            // ============================================================
            // DOMAINE A - Agriculture et espaces verts
            // ============================================================
            [
                'code' => 'A1201',
                'libelle' => 'Aménagement et entretien des espaces verts',
                'domaine_code' => 'A',
                'domaine_libelle' => 'Agriculture et pêche, espaces naturels et verts, soins aux animaux',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Espaces verts',
                'definition' => 'Réalise des opérations techniques d\'aménagement et d\'entretien des espaces verts.',
            ],

            // ============================================================
            // DOMAINE G - Hôtellerie-restauration, tourisme, loisirs
            // ============================================================
            [
                'code' => 'G1401',
                'libelle' => 'Assistance de direction d\'hôtel-Loss',
                'domaine_code' => 'G',
                'domaine_libelle' => 'Hôtellerie-restauration, tourisme, loisirs et animation',
                'sous_domaine_code' => '14',
                'sous_domaine_libelle' => 'Tourisme et hébergement',
                'definition' => 'Assiste le directeur d\'un établissement hôtelier dans la gestion quotidienne.',
            ],
            [
                'code' => 'G1501',
                'libelle' => 'Personnel d\'étage en hôtellerie',
                'domaine_code' => 'G',
                'domaine_libelle' => 'Hôtellerie-restauration, tourisme, loisirs et animation',
                'sous_domaine_code' => '15',
                'sous_domaine_libelle' => 'Hébergement',
                'definition' => 'Effectue les travaux de nettoyage et de mise en ordre des chambres et parties communes.',
            ],
            [
                'code' => 'G1601',
                'libelle' => 'Management du personnel de cuisine',
                'domaine_code' => 'G',
                'domaine_libelle' => 'Hôtellerie-restauration, tourisme, loisirs et animation',
                'sous_domaine_code' => '16',
                'sous_domaine_libelle' => 'Restauration',
                'definition' => 'Supervise et coordonne l\'activité d\'une cuisine et d\'une équipe de professionnels.',
            ],
            [
                'code' => 'G1602',
                'libelle' => 'Personnel de cuisine',
                'domaine_code' => 'G',
                'domaine_libelle' => 'Hôtellerie-restauration, tourisme, loisirs et animation',
                'sous_domaine_code' => '16',
                'sous_domaine_libelle' => 'Restauration',
                'definition' => 'Prépare et cuisine des mets selon un plan de production culinaire.',
            ],
            [
                'code' => 'G1803',
                'libelle' => 'Service en restauration',
                'domaine_code' => 'G',
                'domaine_libelle' => 'Hôtellerie-restauration, tourisme, loisirs et animation',
                'sous_domaine_code' => '18',
                'sous_domaine_libelle' => 'Service en restauration',
                'definition' => 'Effectue les opérations de service des plats et des boissons en salle de restaurant.',
            ],

            // ============================================================
            // DOMAINE F - Construction, bâtiment et travaux publics
            // ============================================================
            [
                'code' => 'F1104',
                'libelle' => 'Dessin BTP et paysage',
                'domaine_code' => 'F',
                'domaine_libelle' => 'Construction, bâtiment et travaux publics',
                'sous_domaine_code' => '11',
                'sous_domaine_libelle' => 'Études et conception',
                'definition' => 'Réalise des plans et dessins techniques pour des projets de construction.',
            ],
            [
                'code' => 'F1201',
                'libelle' => 'Conduite de travaux du BTP et de travaux paysagers',
                'domaine_code' => 'F',
                'domaine_libelle' => 'Construction, bâtiment et travaux publics',
                'sous_domaine_code' => '12',
                'sous_domaine_libelle' => 'Conduite de travaux',
                'definition' => 'Organise, planifie et supervise les travaux de construction ou d\'aménagement.',
            ],
        ];
    }
}
