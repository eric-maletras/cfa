<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupération des rôles
        $roleAdmin = $this->getReference('role-ROLE_ADMIN', Role::class);
        $roleFormateur = $this->getReference('role-ROLE_FORMATEUR', Role::class);
        $roleApprenti = $this->getReference('role-ROLE_APPRENTI', Role::class);

        // Définition des modules avec leurs vraies routes
        $modulesData = [
            // ===== MODULES ADMIN =====
            [
                'nom' => 'Gestion des utilisateurs',
                'slug' => 'admin_users',
                'description' => 'Gérer les comptes utilisateurs, rôles et permissions',
                'icone' => 'fas fa-users-cog',
                'route' => 'admin_user_index',  // Route réelle
                'roles' => [$roleAdmin],
                'ordre' => 1,
                'actif' => true,
            ],
            [
                'nom' => 'Gestion des formations',
                'slug' => 'admin_formations',
                'description' => 'Configurer les formations, modules et référentiels',
                'icone' => 'fas fa-graduation-cap',
                'route' => 'admin_formation_index',  // Route réelle
                'roles' => [$roleAdmin],
                'ordre' => 2,
                'actif' => true,
            ],
            [
                'nom' => 'Gestion des sessions',
                'slug' => 'admin_sessions',
                'description' => 'Planifier et gérer les sessions de formation',
                'icone' => 'fas fa-calendar-alt',
                'route' => 'admin_session_index',  // Route réelle
                'roles' => [$roleAdmin],
                'ordre' => 3,
                'actif' => true,
            ],
            [
                'nom' => 'Entreprises partenaires',
                'slug' => 'admin_entreprises',
                'description' => 'Gérer les entreprises et tuteurs',
                'icone' => 'fas fa-building',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleAdmin],
                'ordre' => 4,
                'actif' => true,
            ],
            [
                'nom' => 'Statistiques',
                'slug' => 'admin_stats',
                'description' => 'Tableaux de bord et indicateurs',
                'icone' => 'fas fa-chart-bar',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleAdmin],
                'ordre' => 5,
                'actif' => true,
            ],

            // ===== MODULES FORMATEUR =====
            [
                'nom' => 'Mes sessions',
                'slug' => 'formateur_sessions',
                'description' => 'Voir mes sessions de formation assignées',
                'icone' => 'fas fa-chalkboard-teacher',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleFormateur],
                'ordre' => 10,
                'actif' => true,
            ],
            [
                'nom' => 'Notes et évaluations',
                'slug' => 'formateur_notes',
                'description' => 'Saisir et gérer les notes des apprentis',
                'icone' => 'fas fa-edit',
                'route' => 'app_formateur_notes_index',  // Route réelle
                'roles' => [$roleFormateur],
                'ordre' => 11,
                'actif' => true,
            ],
            [
                'nom' => 'Gestion des absences',
                'slug' => 'formateur_absences',
                'description' => 'Faire l\'appel et gérer les absences',
                'icone' => 'fas fa-user-clock',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleFormateur],
                'ordre' => 12,
                'actif' => true,
            ],
            [
                'nom' => 'Emploi du temps',
                'slug' => 'formateur_planning',
                'description' => 'Consulter mon planning de cours',
                'icone' => 'fas fa-calendar-week',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleFormateur],
                'ordre' => 13,
                'actif' => true,
            ],

            // ===== MODULES APPRENTI =====
            [
                'nom' => 'Mes notes',
                'slug' => 'apprenti_notes',
                'description' => 'Consulter mes notes et moyennes',
                'icone' => 'fas fa-poll',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleApprenti],
                'ordre' => 20,
                'actif' => true,
            ],
            [
                'nom' => 'Mes absences',
                'slug' => 'apprenti_absences',
                'description' => 'Consulter et justifier mes absences',
                'icone' => 'fas fa-calendar-times',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleApprenti],
                'ordre' => 21,
                'actif' => true,
            ],
            [
                'nom' => 'Mon emploi du temps',
                'slug' => 'apprenti_planning',
                'description' => 'Consulter mon planning de formation',
                'icone' => 'fas fa-calendar-day',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleApprenti],
                'ordre' => 22,
                'actif' => true,
            ],
            [
                'nom' => 'Ma formation',
                'slug' => 'apprenti_formation',
                'description' => 'Détails de ma formation et progression',
                'icone' => 'fas fa-book-reader',
                'route' => 'app_module',  // Pas encore développé
                'roles' => [$roleApprenti],
                'ordre' => 23,
                'actif' => true,
            ],
        ];

        foreach ($modulesData as $data) {
            $module = new Module();
            $module->setNom($data['nom']);
            $module->setSlug($data['slug']);
            $module->setDescription($data['description']);
            $module->setIcone($data['icone']);
            $module->setRoute($data['route']);
            $module->setOrdre($data['ordre']);
            $module->setActif($data['actif']);

            foreach ($data['roles'] as $role) {
                $module->addRole($role);
            }

            $manager->persist($module);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }
}
