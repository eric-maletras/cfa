<?php

namespace App\DataFixtures;

use App\Entity\CodeNSF;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les codes NSF (Nomenclature des Spécialités de Formation)
 * Source : INSEE, Décret n° 94-522 du 21 juin 1994
 * Structure hiérarchique : Niveau 1 > Niveau 2 > Niveau 3 > Niveau 4
 */
class CodeNSFFixtures extends Fixture implements FixtureGroupInterface
{
    private ObjectManager $manager;

    public static function getGroups(): array
    {
        return ['reference', 'nsf'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        // Niveau 1 : Domaines (4 postes)
        $this->loadNiveau1();
        
        // Niveau 2 : Sous-domaines (17 postes)
        $this->loadNiveau2();
        
        // Niveau 3 : Groupes de spécialités (100 postes - sélection pertinente)
        $this->loadNiveau3();
        
        // Niveau 4 : Spécialités fines avec fonctions (sélection pour informatique)
        $this->loadNiveau4();

        $manager->flush();
    }

    private function loadNiveau1(): void
    {
        $domaines = [
            ['code' => '1', 'libelle' => 'Domaines disciplinaires', 'type' => 'disciplinaire'],
            ['code' => '2', 'libelle' => 'Domaines technico-professionnels de la production', 'type' => 'technico_prod'],
            ['code' => '3', 'libelle' => 'Domaines technico-professionnels des services', 'type' => 'technico_services'],
            ['code' => '4', 'libelle' => 'Domaines du développement personnel', 'type' => 'dev_personnel'],
        ];

        foreach ($domaines as $data) {
            $nsf = new CodeNSF();
            $nsf->setCode($data['code']);
            $nsf->setLibelle($data['libelle']);
            $nsf->setNiveau(1);
            $nsf->setTypeDomaine($data['type']);
            $nsf->setActif(true);

            $this->manager->persist($nsf);
            $this->addReference('nsf-' . $data['code'], $nsf);
        }
    }

    private function loadNiveau2(): void
    {
        $sousDomaines = [
            // Domaines disciplinaires (1x)
            ['code' => '10', 'libelle' => 'Formations générales', 'parent' => '1'],
            ['code' => '11', 'libelle' => 'Mathématiques et sciences', 'parent' => '1'],
            ['code' => '12', 'libelle' => 'Sciences humaines et droit', 'parent' => '1'],
            ['code' => '13', 'libelle' => 'Lettres et arts', 'parent' => '1'],
            
            // Domaines technico-professionnels de la production (2x)
            ['code' => '20', 'libelle' => 'Spécialités pluri-technologiques de production', 'parent' => '2'],
            ['code' => '21', 'libelle' => 'Agriculture, pêche, forêt et espaces verts', 'parent' => '2'],
            ['code' => '22', 'libelle' => 'Transformations', 'parent' => '2'],
            ['code' => '23', 'libelle' => 'Génie civil, construction, bois', 'parent' => '2'],
            ['code' => '24', 'libelle' => 'Matériaux souples', 'parent' => '2'],
            ['code' => '25', 'libelle' => 'Mécanique, électricité, électronique', 'parent' => '2'],
            
            // Domaines technico-professionnels des services (3x)
            ['code' => '30', 'libelle' => 'Spécialités plurivalentes des services', 'parent' => '3'],
            ['code' => '31', 'libelle' => 'Échanges et gestion', 'parent' => '3'],
            ['code' => '32', 'libelle' => 'Communication et information', 'parent' => '3'],
            ['code' => '33', 'libelle' => 'Services aux personnes', 'parent' => '3'],
            ['code' => '34', 'libelle' => 'Services à la collectivité', 'parent' => '3'],
            
            // Domaines du développement personnel (4x)
            ['code' => '41', 'libelle' => 'Capacités individuelles et sociales', 'parent' => '4'],
            ['code' => '42', 'libelle' => 'Activités quotidiennes et de loisirs', 'parent' => '4'],
        ];

        foreach ($sousDomaines as $data) {
            $nsf = new CodeNSF();
            $nsf->setCode($data['code']);
            $nsf->setLibelle($data['libelle']);
            $nsf->setNiveau(2);
            $nsf->setParent($this->getReference('nsf-' . $data['parent'], CodeNSF::class));
            $nsf->setActif(true);

            $this->manager->persist($nsf);
            $this->addReference('nsf-' . $data['code'], $nsf);
        }
    }

    private function loadNiveau3(): void
    {
        $groupes = [
            // === Formations générales (10x) ===
            ['code' => '100', 'libelle' => 'Formations générales', 'parent' => '10'],

            // === Mathématiques et sciences (11x) ===
            ['code' => '110', 'libelle' => 'Spécialités pluri-scientifiques', 'parent' => '11'],
            ['code' => '111', 'libelle' => 'Physique-chimie', 'parent' => '11'],
            ['code' => '113', 'libelle' => 'Sciences naturelles (biologie-géologie)', 'parent' => '11'],
            ['code' => '114', 'libelle' => 'Mathématiques', 'parent' => '11'],
            ['code' => '115', 'libelle' => 'Physique', 'parent' => '11'],
            ['code' => '116', 'libelle' => 'Chimie', 'parent' => '11'],
            ['code' => '117', 'libelle' => 'Sciences de la terre', 'parent' => '11'],
            ['code' => '118', 'libelle' => 'Sciences de la vie', 'parent' => '11'],

            // === Sciences humaines et droit (12x) ===
            ['code' => '120', 'libelle' => 'Spécialités pluridisciplinaires sciences humaines et droit', 'parent' => '12'],
            ['code' => '121', 'libelle' => 'Géographie', 'parent' => '12'],
            ['code' => '122', 'libelle' => 'Économie', 'parent' => '12'],
            ['code' => '123', 'libelle' => 'Sciences sociales', 'parent' => '12'],
            ['code' => '124', 'libelle' => 'Psychologie', 'parent' => '12'],
            ['code' => '125', 'libelle' => 'Linguistique', 'parent' => '12'],
            ['code' => '126', 'libelle' => 'Histoire', 'parent' => '12'],
            ['code' => '127', 'libelle' => 'Philosophie, éthique et théologie', 'parent' => '12'],
            ['code' => '128', 'libelle' => 'Droit, sciences politiques', 'parent' => '12'],

            // === Lettres et arts (13x) ===
            ['code' => '130', 'libelle' => 'Spécialités littéraires et artistiques plurivalentes', 'parent' => '13'],
            ['code' => '131', 'libelle' => 'Français, littérature et civilisation française', 'parent' => '13'],
            ['code' => '132', 'libelle' => 'Arts plastiques', 'parent' => '13'],
            ['code' => '133', 'libelle' => 'Musique, arts du spectacle', 'parent' => '13'],
            ['code' => '134', 'libelle' => 'Autres disciplines artistiques', 'parent' => '13'],
            ['code' => '135', 'libelle' => 'Langues et civilisations anciennes', 'parent' => '13'],
            ['code' => '136', 'libelle' => 'Langues vivantes, civilisations étrangères et régionales', 'parent' => '13'],

            // === Spécialités pluri-technologiques de production (20x) ===
            ['code' => '200', 'libelle' => 'Technologies industrielles fondamentales', 'parent' => '20'],
            ['code' => '201', 'libelle' => 'Technologies de commandes des transformations industrielles', 'parent' => '20'],

            // === Agriculture (21x) ===
            ['code' => '210', 'libelle' => 'Spécialités plurivalentes de l\'agronomie et de l\'agriculture', 'parent' => '21'],
            ['code' => '211', 'libelle' => 'Productions végétales, cultures spécialisées', 'parent' => '21'],
            ['code' => '212', 'libelle' => 'Productions animales, élevage spécialisé', 'parent' => '21'],
            ['code' => '213', 'libelle' => 'Forêts, espaces naturels, faune sauvage, pêche', 'parent' => '21'],
            ['code' => '214', 'libelle' => 'Aménagement paysager', 'parent' => '21'],

            // === Transformations (22x) ===
            ['code' => '220', 'libelle' => 'Spécialités pluritechnologiques des transformations', 'parent' => '22'],
            ['code' => '221', 'libelle' => 'Agroalimentaire, alimentation, cuisine', 'parent' => '22'],
            ['code' => '222', 'libelle' => 'Transformations chimiques et apparentées', 'parent' => '22'],
            ['code' => '223', 'libelle' => 'Métallurgie', 'parent' => '22'],
            ['code' => '224', 'libelle' => 'Matériaux de construction, verre, céramique', 'parent' => '22'],
            ['code' => '225', 'libelle' => 'Plasturgie, matériaux composites', 'parent' => '22'],
            ['code' => '226', 'libelle' => 'Papier, carton', 'parent' => '22'],
            ['code' => '227', 'libelle' => 'Énergie, génie climatique', 'parent' => '22'],

            // === Génie civil, construction, bois (23x) ===
            ['code' => '230', 'libelle' => 'Spécialités pluritechnologiques génie civil, construction, bois', 'parent' => '23'],
            ['code' => '231', 'libelle' => 'Mines et carrières, génie civil, topographie', 'parent' => '23'],
            ['code' => '232', 'libelle' => 'Bâtiment construction et couverture', 'parent' => '23'],
            ['code' => '233', 'libelle' => 'Bâtiment finitions', 'parent' => '23'],
            ['code' => '234', 'libelle' => 'Travail du bois et de l\'ameublement', 'parent' => '23'],

            // === Matériaux souples (24x) ===
            ['code' => '240', 'libelle' => 'Spécialités pluritechnologiques matériaux souples', 'parent' => '24'],
            ['code' => '241', 'libelle' => 'Textile', 'parent' => '24'],
            ['code' => '242', 'libelle' => 'Habillement', 'parent' => '24'],
            ['code' => '243', 'libelle' => 'Cuirs et peaux', 'parent' => '24'],

            // === Mécanique, électricité, électronique (25x) - IMPORTANT POUR INFORMATIQUE ===
            ['code' => '250', 'libelle' => 'Spécialités pluritechnologiques mécanique-électricité', 'parent' => '25'],
            ['code' => '251', 'libelle' => 'Mécanique générale et de précision, usinage', 'parent' => '25'],
            ['code' => '252', 'libelle' => 'Moteurs et mécanique auto', 'parent' => '25'],
            ['code' => '253', 'libelle' => 'Mécanique aéronautique et spatiale', 'parent' => '25'],
            ['code' => '254', 'libelle' => 'Structures métalliques', 'parent' => '25'],
            ['code' => '255', 'libelle' => 'Électricité, électronique', 'parent' => '25'],

            // === Spécialités plurivalentes des services (30x) ===
            ['code' => '300', 'libelle' => 'Spécialités plurivalentes des services', 'parent' => '30'],

            // === Échanges et gestion (31x) ===
            ['code' => '310', 'libelle' => 'Spécialités plurivalentes des échanges et de la gestion', 'parent' => '31'],
            ['code' => '311', 'libelle' => 'Transport, manutention, magasinage', 'parent' => '31'],
            ['code' => '312', 'libelle' => 'Commerce, vente', 'parent' => '31'],
            ['code' => '313', 'libelle' => 'Finances, banque, assurances, immobilier', 'parent' => '31'],
            ['code' => '314', 'libelle' => 'Comptabilité, gestion', 'parent' => '31'],
            ['code' => '315', 'libelle' => 'Ressources humaines, gestion du personnel, gestion de l\'emploi', 'parent' => '31'],

            // === Communication et information (32x) - TRÈS IMPORTANT POUR INFORMATIQUE ===
            ['code' => '320', 'libelle' => 'Spécialités plurivalentes de la communication et de l\'information', 'parent' => '32'],
            ['code' => '321', 'libelle' => 'Journalisme, communication', 'parent' => '32'],
            ['code' => '322', 'libelle' => 'Techniques de l\'imprimerie et de l\'édition', 'parent' => '32'],
            ['code' => '323', 'libelle' => 'Techniques de l\'image et du son, métiers connexes du spectacle', 'parent' => '32'],
            ['code' => '324', 'libelle' => 'Secrétariat, bureautique', 'parent' => '32'],
            ['code' => '325', 'libelle' => 'Documentation, bibliothèque, administration des données', 'parent' => '32'],
            ['code' => '326', 'libelle' => 'Informatique, traitement de l\'information, réseaux de transmission', 'parent' => '32'],

            // === Services aux personnes (33x) ===
            ['code' => '330', 'libelle' => 'Spécialités plurivalentes des services aux personnes', 'parent' => '33'],
            ['code' => '331', 'libelle' => 'Santé', 'parent' => '33'],
            ['code' => '332', 'libelle' => 'Travail social', 'parent' => '33'],
            ['code' => '333', 'libelle' => 'Enseignement, formation', 'parent' => '33'],
            ['code' => '334', 'libelle' => 'Accueil, hôtellerie, tourisme', 'parent' => '33'],
            ['code' => '335', 'libelle' => 'Animation culturelle, sportive et de loisirs', 'parent' => '33'],
            ['code' => '336', 'libelle' => 'Coiffure, esthétique et autres services aux personnes', 'parent' => '33'],

            // === Services à la collectivité (34x) ===
            ['code' => '340', 'libelle' => 'Spécialités plurivalentes des services à la collectivité', 'parent' => '34'],
            ['code' => '341', 'libelle' => 'Aménagement du territoire, urbanisme', 'parent' => '34'],
            ['code' => '342', 'libelle' => 'Développement et protection du patrimoine culturel', 'parent' => '34'],
            ['code' => '343', 'libelle' => 'Nettoyage, assainissement, protection de l\'environnement', 'parent' => '34'],
            ['code' => '344', 'libelle' => 'Sécurité des biens et des personnes, police, surveillance', 'parent' => '34'],
            ['code' => '345', 'libelle' => 'Application des droits et statuts des personnes', 'parent' => '34'],
            ['code' => '346', 'libelle' => 'Spécialités militaires', 'parent' => '34'],

            // === Capacités individuelles et sociales (41x) ===
            ['code' => '410', 'libelle' => 'Spécialités concernant plusieurs capacités', 'parent' => '41'],
            ['code' => '411', 'libelle' => 'Pratiques sportives', 'parent' => '41'],
            ['code' => '412', 'libelle' => 'Développement des capacités mentales, apprentissage de base', 'parent' => '41'],
            ['code' => '413', 'libelle' => 'Développement des capacités comportementales et relationnelles', 'parent' => '41'],
            ['code' => '414', 'libelle' => 'Développement des capacités individuelles d\'organisation', 'parent' => '41'],
            ['code' => '415', 'libelle' => 'Développement des capacités d\'orientation, d\'insertion', 'parent' => '41'],

            // === Activités quotidiennes et de loisirs (42x) ===
            ['code' => '421', 'libelle' => 'Jeux et activités spécifiques de loisirs', 'parent' => '42'],
            ['code' => '422', 'libelle' => 'Économie et activités domestiques', 'parent' => '42'],
            ['code' => '423', 'libelle' => 'Vie familiale, vie sociale et autres formations au développement personnel', 'parent' => '42'],
        ];

        foreach ($groupes as $data) {
            $nsf = new CodeNSF();
            $nsf->setCode($data['code']);
            $nsf->setLibelle($data['libelle']);
            $nsf->setNiveau(3);
            $nsf->setParent($this->getReference('nsf-' . $data['parent'], CodeNSF::class));
            $nsf->setActif(true);

            $this->manager->persist($nsf);
            $this->addReference('nsf-' . $data['code'], $nsf);
        }
    }

    private function loadNiveau4(): void
    {
        // Niveau 4 : Croisement avec les fonctions (lettres m à w)
        // Particulièrement pour le code 326 (Informatique)
        $fonctions = [
            'm' => 'Conception',
            'n' => 'Études et projets',
            'p' => 'Méthodes, organisation, gestion de production',
            'r' => 'Contrôle, prévention, entretien',
            's' => 'Production',
            't' => 'Mise en œuvre, production',
            'u' => 'Conduite, surveillance d\'équipement',
            'v' => 'Commercialisation',
            'w' => 'Autres fonctions transverses',
        ];

        // Spécialités fines pour l'informatique (326x)
        $specialitesInformatique = [
            ['code' => '326m', 'libelle' => 'Informatique - Conception', 'fonction' => 'm', 'libelle_fonction' => 'Conception d\'architectures et de solutions informatiques'],
            ['code' => '326n', 'libelle' => 'Analyse informatique, conception d\'architecture de réseaux', 'fonction' => 'n', 'libelle_fonction' => 'Études, développement et conduite de projets'],
            ['code' => '326p', 'libelle' => 'Informatique, programmation, développement', 'fonction' => 'p', 'libelle_fonction' => 'Méthodes, organisation, programmation'],
            ['code' => '326r', 'libelle' => 'Assistance informatique, maintenance de logiciels et réseaux', 'fonction' => 'r', 'libelle_fonction' => 'Contrôle, prévention, maintenance'],
            ['code' => '326t', 'libelle' => 'Programmation, mise en place de logiciels', 'fonction' => 't', 'libelle_fonction' => 'Mise en œuvre, installation, déploiement'],
            ['code' => '326u', 'libelle' => 'Exploitation informatique', 'fonction' => 'u', 'libelle_fonction' => 'Conduite et exploitation des systèmes'],
        ];

        foreach ($specialitesInformatique as $data) {
            $nsf = new CodeNSF();
            $nsf->setCode($data['code']);
            $nsf->setLibelle($data['libelle']);
            $nsf->setNiveau(4);
            $nsf->setParent($this->getReference('nsf-326', CodeNSF::class));
            $nsf->setCodeFonction($data['fonction']);
            $nsf->setLibelleFonction($data['libelle_fonction']);
            $nsf->setActif(true);

            $this->manager->persist($nsf);
            $this->addReference('nsf-' . $data['code'], $nsf);
        }

        // Spécialités fines pour la gestion (314x)
        $specialitesGestion = [
            ['code' => '314n', 'libelle' => 'Études, conseil en gestion', 'fonction' => 'n', 'libelle_fonction' => 'Études et projets'],
            ['code' => '314p', 'libelle' => 'Gestion des organisations', 'fonction' => 'p', 'libelle_fonction' => 'Méthodes et organisation'],
            ['code' => '314r', 'libelle' => 'Contrôle de gestion, audit', 'fonction' => 'r', 'libelle_fonction' => 'Contrôle'],
            ['code' => '314t', 'libelle' => 'Comptabilité, gestion courante', 'fonction' => 't', 'libelle_fonction' => 'Production comptable'],
        ];

        foreach ($specialitesGestion as $data) {
            $nsf = new CodeNSF();
            $nsf->setCode($data['code']);
            $nsf->setLibelle($data['libelle']);
            $nsf->setNiveau(4);
            $nsf->setParent($this->getReference('nsf-314', CodeNSF::class));
            $nsf->setCodeFonction($data['fonction']);
            $nsf->setLibelleFonction($data['libelle_fonction']);
            $nsf->setActif(true);

            $this->manager->persist($nsf);
            $this->addReference('nsf-' . $data['code'], $nsf);
        }
    }
}
