<?php

namespace App\DataFixtures;

use App\Entity\Devoir;
use App\Entity\Note;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les devoirs et les notes
 * 
 * Crée pour chaque session/formateur :
 * - 1 devoir passé en décembre 2024
 * - Les notes pour tous les apprentis de la session
 */
class DevoirNoteFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Titres de devoirs par spécialité de formateur
    private const DEVOIRS_PAR_SPECIALITE = [
        'Culture générale et expression' => [
            'titre' => 'Synthèse de documents - La transformation numérique',
            'type' => Devoir::TYPE_DEVOIR,
            'description' => 'À partir du corpus de 4 documents fournis, rédigez une synthèse structurée sur le thème de la transformation numérique dans le monde du travail.',
        ],
        'Anglais' => [
            'titre' => 'Written comprehension - Digital workplace',
            'type' => Devoir::TYPE_CONTROLE,
            'description' => 'Reading comprehension and written expression about digital tools in the workplace.',
        ],
        'Mathématiques' => [
            'titre' => 'Contrôle - Suites numériques et algorithmes',
            'type' => Devoir::TYPE_CONTROLE,
            'description' => 'Exercices sur les suites arithmétiques, géométriques et leur implémentation algorithmique.',
        ],
        'Économie-Droit' => [
            'titre' => 'Étude de cas - RGPD et protection des données',
            'type' => Devoir::TYPE_DEVOIR,
            'description' => 'Analyse juridique d\'une situation d\'entreprise confrontée aux enjeux du RGPD.',
        ],
        'Culture économique, juridique et managériale' => [
            'titre' => 'Analyse de situation - Management d\'équipe projet',
            'type' => Devoir::TYPE_DEVOIR,
            'description' => 'Étude de cas sur le management d\'une équipe projet dans un contexte de transformation digitale.',
        ],
        'Réseaux et cybersécurité' => [
            'titre' => 'TP noté - Configuration sécurisée pfSense',
            'type' => Devoir::TYPE_TP,
            'description' => 'Configuration d\'un pare-feu pfSense avec VLANs, règles de filtrage et VPN site-à-site.',
        ],
        'Développement web' => [
            'titre' => 'Projet - API REST avec Symfony',
            'type' => Devoir::TYPE_PROJET,
            'description' => 'Développement d\'une API REST complète avec authentification JWT, documentation OpenAPI.',
        ],
        'Administration systèmes Linux/Windows' => [
            'titre' => 'TP noté - Déploiement Active Directory',
            'type' => Devoir::TYPE_TP,
            'description' => 'Installation et configuration d\'un domaine AD avec GPO, DNS intégré et réplication.',
        ],
        'Développement applications' => [
            'titre' => 'Projet - Application de gestion en C#',
            'type' => Devoir::TYPE_PROJET,
            'description' => 'Développement d\'une application WPF avec Entity Framework et pattern MVVM.',
        ],
        'Électronique et systèmes embarqués' => [
            'titre' => 'TP noté - Programmation microcontrôleur',
            'type' => Devoir::TYPE_TP,
            'description' => 'Programmation d\'un système embarqué avec capteurs et communication série.',
        ],
        'Gestion de projet et communication' => [
            'titre' => 'Oral - Présentation de projet professionnel',
            'type' => Devoir::TYPE_ORAL,
            'description' => 'Présentation orale de 15 minutes sur un projet professionnel fictif avec support visuel.',
        ],
        'Bureautique et outils collaboratifs' => [
            'titre' => 'QCM - Microsoft 365 et outils collaboratifs',
            'type' => Devoir::TYPE_QCM,
            'description' => 'QCM sur les fonctionnalités avancées de Microsoft 365, Teams, SharePoint.',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $btsKeys = ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM'];
        $formateursData = UserFixtures::getFormateursData();
        
        // Dates de devoirs en décembre 2024 (variées)
        $datesDevoirDecembre = [
            new \DateTime('2024-12-02'),
            new \DateTime('2024-12-05'),
            new \DateTime('2024-12-09'),
            new \DateTime('2024-12-12'),
            new \DateTime('2024-12-16'),
            new \DateTime('2024-12-19'),
        ];

        foreach ($btsKeys as $btsKey) {
            // Récupérer la session active
            $session = $this->getReference(SessionFixtures::SESSION_PREFIX . $btsKey . '-active');
            
            // Récupérer les apprentis de cette session
            $apprentis = [];
            for ($i = 0; $i < 15; $i++) {
                $apprentis[] = $this->getReference(UserFixtures::APPRENTI_PREFIX . $btsKey . '-active-' . $i);
            }

            // Pour chaque formateur qui enseigne dans ce BTS
            $dateIndex = 0;
            foreach ($formateursData as $formateurIndex => $formateurData) {
                // Vérifier si ce formateur enseigne dans ce BTS
                if (!in_array($btsKey, $formateurData['bts'])) {
                    continue;
                }

                $formateur = $this->getReference(UserFixtures::FORMATEUR_PREFIX . $formateurIndex);
                $specialite = $formateurData['specialite'];
                
                // Récupérer les données du devoir pour cette spécialité
                $devoirData = self::DEVOIRS_PAR_SPECIALITE[$specialite] ?? [
                    'titre' => 'Contrôle - ' . $specialite,
                    'type' => Devoir::TYPE_CONTROLE,
                    'description' => 'Évaluation en ' . $specialite,
                ];

                // Créer le devoir
                $devoir = new Devoir();
                $devoir->setSession($session);
                $devoir->setFormateur($formateur);
                $devoir->setTitre($devoirData['titre']);
                $devoir->setDescription($devoirData['description']);
                $devoir->setType($devoirData['type']);
                $devoir->setDateDevoir($datesDevoirDecembre[$dateIndex % count($datesDevoirDecembre)]);
                $devoir->setCoefficient($this->getCoefficient($devoirData['type']));
                $devoir->setBareme('20.00');
                $devoir->setVisible(true);
                $devoir->setNotesPubliees(true);
                
                $manager->persist($devoir);
                
                // Créer les notes pour chaque apprenti
                $this->createNotesForDevoir($manager, $devoir, $apprentis, $formateur);
                
                $dateIndex++;
            }
        }

        $manager->flush();
    }

    /**
     * Crée les notes pour un devoir donné
     */
    private function createNotesForDevoir(
        ObjectManager $manager,
        Devoir $devoir,
        array $apprentis,
        $formateur
    ): void {
        // Générer une distribution de notes réaliste
        // Moyenne visée : environ 12/20 avec écart-type ~3
        
        foreach ($apprentis as $apprenti) {
            $note = new Note();
            $note->setDevoir($devoir);
            $note->setApprenant($apprenti);
            
            // 5% de chance d'absence
            if (random_int(1, 100) <= 5) {
                $note->setStatut(Note::STATUT_ABSENT);
                $note->setValeur(null);
            } else {
                $note->setStatut(Note::STATUT_NORMAL);
                
                // Générer une note avec distribution normale (approximée)
                $valeur = $this->generateRealisticGrade();
                $note->setValeur(number_format($valeur, 2, '.', ''));
                
                // Ajouter un commentaire pour certaines notes
                if ($valeur >= 16) {
                    $note->setCommentaire('Excellent travail, félicitations !');
                } elseif ($valeur >= 14) {
                    $note->setCommentaire('Très bon travail.');
                } elseif ($valeur < 8) {
                    $note->setCommentaire('Des difficultés importantes, merci de venir me voir.');
                } elseif ($valeur < 10 && random_int(0, 1)) {
                    $note->setCommentaire('Travail insuffisant, des efforts sont nécessaires.');
                }
            }
            
            $note->setDateSaisie(new \DateTime('2024-12-20'));
            $note->setSaisiePar($formateur);
            
            $manager->persist($note);
            $devoir->addNote($note);
        }
    }

    /**
     * Génère une note réaliste avec une distribution normale approximée
     * Moyenne ~12, écart-type ~3
     */
    private function generateRealisticGrade(): float
    {
        // Méthode Box-Muller simplifiée pour distribution normale
        $u1 = random_int(1, 1000) / 1000;
        $u2 = random_int(1, 1000) / 1000;
        
        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
        
        // Moyenne 12, écart-type 3
        $note = 12 + ($z * 3);
        
        // Limiter entre 0 et 20
        $note = max(0, min(20, $note));
        
        // Arrondir au quart de point
        return round($note * 4) / 4;
    }

    /**
     * Retourne le coefficient selon le type de devoir
     */
    private function getCoefficient(string $type): string
    {
        return match ($type) {
            Devoir::TYPE_EXAMEN => '3.00',
            Devoir::TYPE_PROJET => '2.00',
            Devoir::TYPE_TP => '1.50',
            Devoir::TYPE_ORAL => '1.50',
            Devoir::TYPE_CONTROLE => '1.00',
            Devoir::TYPE_QCM => '0.50',
            default => '1.00',
        };
    }

    public function getDependencies(): array
    {
        return [
            SessionFixtures::class,
            InscriptionFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['evaluations', 'devoirs', 'notes'];
    }
}
