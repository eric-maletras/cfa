<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures pour les utilisateurs
 * 
 * Crée :
 * - 1 Admin
 * - 12 Formateurs (avec partage entre BTS)
 * - 140 Apprentis (15 inscrits × 8 sessions + 20 non inscrits)
 */
class UserFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // Références admin
    public const ADMIN_REF = 'user-admin';
    
    // Préfixes pour les références formateurs
    public const FORMATEUR_PREFIX = 'formateur-';
    
    // Préfixes pour les références apprentis
    public const APPRENTI_PREFIX = 'apprenti-';
    public const APPRENTI_NON_INSCRIT_PREFIX = 'apprenti-ni-';

    // Données des formateurs avec leurs spécialités et BTS associés
    private const FORMATEURS_DATA = [
        // Formateurs partagés (enseignent dans 2+ BTS)
        ['nom' => 'Martin', 'prenom' => 'Sophie', 'specialite' => 'Culture générale et expression', 'bts' => ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM']],
        ['nom' => 'Durail', 'prenom' => 'Pierre', 'specialite' => 'Anglais', 'bts' => ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM']],
        ['nom' => 'Dubois', 'prenom' => 'Marie', 'specialite' => 'Mathématiques', 'bts' => ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR']],
        ['nom' => 'Laurent', 'prenom' => 'Jean', 'specialite' => 'Économie-Droit', 'bts' => ['SIO-SISR', 'SIO-SLAM', 'SAM']],
        ['nom' => 'Moreau', 'prenom' => 'Isabelle', 'specialite' => 'Culture économique, juridique et managériale', 'bts' => ['CIEL-IR', 'SAM']],
        
        // Formateurs techniques SIO (partagés SISR/SLAM)
        ['nom' => 'Garcia', 'prenom' => 'Antoine', 'specialite' => 'Réseaux et cybersécurité', 'bts' => ['SIO-SISR', 'CIEL-IR']],
        ['nom' => 'Roux', 'prenom' => 'Nathalie', 'specialite' => 'Développement web', 'bts' => ['SIO-SLAM', 'SIO-SISR']],
        
        // Formateurs dédiés par BTS
        ['nom' => 'Petit', 'prenom' => 'François', 'specialite' => 'Administration systèmes Linux/Windows', 'bts' => ['SIO-SISR']],
        ['nom' => 'Leroy', 'prenom' => 'Céline', 'specialite' => 'Développement applications', 'bts' => ['SIO-SLAM']],
        ['nom' => 'Simon', 'prenom' => 'Thomas', 'specialite' => 'Électronique et systèmes embarqués', 'bts' => ['CIEL-IR']],
        ['nom' => 'Michel', 'prenom' => 'Claire', 'specialite' => 'Gestion de projet et communication', 'bts' => ['SAM']],
        ['nom' => 'Lefebvre', 'prenom' => 'David', 'specialite' => 'Bureautique et outils collaboratifs', 'bts' => ['SAM']],
    ];

    // Prénoms et noms pour générer les apprentis
    private const PRENOMS_HOMMES = [
        'Lucas', 'Hugo', 'Louis', 'Nathan', 'Léo', 'Gabriel', 'Raphaël', 'Arthur', 
        'Jules', 'Adam', 'Maxime', 'Théo', 'Mathis', 'Enzo', 'Noah', 'Tom', 
        'Clément', 'Antoine', 'Alexandre', 'Victor', 'Paul', 'Pierre', 'Romain', 'Julien',
        'Nicolas', 'Kevin', 'Dylan', 'Yanis', 'Mehdi', 'Karim', 'Sofiane', 'Bilal'
    ];
    
    private const PRENOMS_FEMMES = [
        'Emma', 'Léa', 'Chloé', 'Manon', 'Camille', 'Sarah', 'Jade', 'Louise', 
        'Zoé', 'Lola', 'Clara', 'Inès', 'Lilou', 'Maëlys', 'Julia', 'Eva',
        'Marie', 'Laura', 'Océane', 'Pauline', 'Morgane', 'Anaïs', 'Lisa', 'Charlotte',
        'Fatima', 'Amina', 'Yasmine', 'Nour', 'Salma', 'Inaya', 'Assia', 'Lina'
    ];
    
    private const NOMS = [
        'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois',
        'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Leroy', 'Roux', 'David',
        'Bertrand', 'Morel', 'Fournier', 'Girard', 'Bonnet', 'Dupont', 'Lambert', 'Fontaine',
        'Rousseau', 'Vincent', 'Muller', 'Lefevre', 'Faure', 'Andre', 'Mercier', 'Blanc',
        'Guerin', 'Boyer', 'Garnier', 'Chevalier', 'Francois', 'Legrand', 'Gauthier', 'Garcia',
        'Benali', 'Diallo', 'Traore', 'Nguyen', 'Pham', 'Silva', 'Ferreira', 'Santos'
    ];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Récupérer les références avec le 2ème argument (classe)
        $roleAdmin = $this->getReference(RoleFixtures::ROLE_ADMIN_REF, Role::class);
        $roleFormateur = $this->getReference(RoleFixtures::ROLE_FORMATEUR_REF, Role::class);
        $roleApprenti = $this->getReference(RoleFixtures::ROLE_APPRENTI_REF, Role::class);

        // ============================================
        // 1. Créer l'administrateur
        // ============================================
        $admin = new User();
        $admin->setEmail('admin@cfa.ericm.fr');
        $admin->setNom('Duval');
        $admin->setPrenom('Administrateur');
        $admin->setActif(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Btssio75000!'));
        $admin->addRolesEntity($roleAdmin);
        $manager->persist($admin);
        $this->addReference(self::ADMIN_REF, $admin);

        // ============================================
        // 2. Créer les formateurs
        // ============================================
        foreach (self::FORMATEURS_DATA as $index => $data) {
            $formateur = new User();
            $email = strtolower($data['prenom'] . '.' . $data['nom']) . '@gmail.fr';
            $email = $this->normalizeEmail($email);
            
            $formateur->setEmail($email);
            $formateur->setNom($data['nom']);
            $formateur->setPrenom($data['prenom']);
            $formateur->setActif(true);
            $formateur->setPassword($this->passwordHasher->hashPassword($formateur, 'Abc123!'));
            $formateur->addRolesEntity($roleFormateur);
            
            $manager->persist($formateur);
            
            // Référence avec index
            $refKey = self::FORMATEUR_PREFIX . $index;
            $this->addReference($refKey, $formateur);
        }

        // ============================================
        // 3. Créer les apprentis
        // ============================================
        $apprentiIndex = 0;
        $usedEmails = [];
        
        $btsKeys = ['SIO-SISR', 'SIO-SLAM', 'CIEL-IR', 'SAM'];
        
        foreach ($btsKeys as $btsIndex => $btsKey) {
            // 15 apprentis pour la session active (2024-2026)
            for ($i = 0; $i < 15; $i++) {
                $apprenti = $this->createApprenti($usedEmails, $roleApprenti);
                $manager->persist($apprenti);
                $this->addReference(self::APPRENTI_PREFIX . $btsKey . '-active-' . $i, $apprenti);
                $apprentiIndex++;
            }
            
            // 15 apprentis pour la session inactive (2023-2025)
            for ($i = 0; $i < 15; $i++) {
                $apprenti = $this->createApprenti($usedEmails, $roleApprenti);
                $manager->persist($apprenti);
                $this->addReference(self::APPRENTI_PREFIX . $btsKey . '-inactive-' . $i, $apprenti);
                $apprentiIndex++;
            }
            
            // 5 apprentis non inscrits (volant)
            for ($i = 0; $i < 5; $i++) {
                $apprenti = $this->createApprenti($usedEmails, $roleApprenti);
                $manager->persist($apprenti);
                $this->addReference(self::APPRENTI_NON_INSCRIT_PREFIX . $btsKey . '-' . $i, $apprenti);
                $apprentiIndex++;
            }
        }

        $manager->flush();
    }

    /**
     * Crée un apprenti avec des données aléatoires
     */
    private function createApprenti(array &$usedEmails, Role $roleApprenti): User
    {
        $isFemale = random_int(0, 1) === 1;
        $prenoms = $isFemale ? self::PRENOMS_FEMMES : self::PRENOMS_HOMMES;
        
        // Générer un email unique
        do {
            $prenom = $prenoms[array_rand($prenoms)];
            $nom = self::NOMS[array_rand(self::NOMS)];
            $suffix = random_int(1, 99);
            $email = strtolower($prenom . '.' . $nom . $suffix) . '@apprenti.cfa-demo.fr';
            $email = $this->normalizeEmail($email);
        } while (in_array($email, $usedEmails));
        
        $usedEmails[] = $email;
        
        $apprenti = new User();
        $apprenti->setEmail($email);
        $apprenti->setNom($nom);
        $apprenti->setPrenom($prenom);
        $apprenti->setActif(true);
        $apprenti->setPassword($this->passwordHasher->hashPassword($apprenti, 'Apprenti123!'));
        $apprenti->addRolesEntity($roleApprenti);
        
        return $apprenti;
    }

    /**
     * Normalise un email (supprime les accents, caractères spéciaux)
     */
    private function normalizeEmail(string $email): string
    {
        $search = ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï', 'ç', 'œ', 'æ', 'ÿ'];
        $replace = ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'u', 'u', 'u', 'o', 'o', 'i', 'i', 'c', 'oe', 'ae', 'y'];
        return str_replace($search, $replace, $email);
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base', 'users'];
    }

    /**
     * Retourne les données des formateurs (utilisé par d'autres fixtures)
     */
    public static function getFormateursData(): array
    {
        return self::FORMATEURS_DATA;
    }
}
