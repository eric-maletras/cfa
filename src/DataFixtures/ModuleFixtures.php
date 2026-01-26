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
        $roleAdmin = $this->getReference('role-admin', Role::class);
        $roleFormateur = $this->getReference('role-formateur', Role::class);
        $roleApprenti = $this->getReference('role-apprenti', Role::class);

        $modulesData = [
            // ===== MODULES ADMIN =====
            [
                'nom' => 'Gestion des utilisateurs',
                'description' => 'Gérer les comptes utilisateurs, rôles et permissions',
                'icone' => 'users-cog',
                'route' => 'admin_users',
                'couleur' => 'primary',
                'roles' => [$roleAdmin],
                'ordre' => 1,
            ],
            [
                'nom' => 'Gestion des formations',
                'description' => 'Configurer les formations, modules et référentiels',
                'icone' => 'graduation-cap',
                'route' => 'admin_formations',
                'couleur' => 'primary',
                'roles' => [$roleAdmin],
                'ordre' => 2,
            ],
            [
                'nom' => 'Gestion des sessions',
                'description' => 'Planifier et gérer les sessions de formation',
                'icone' => 'calendar',
                'route' => 'admin_promotions',
                'couleur' => 'primary',
                'roles' => [$roleAdmin],
                'ordre' => 3,
            ],
            [
                'nom' => 'Entreprises partenaires',
                'description' => 'Gérer les entreprises et tuteurs',
                'icone' => 'building',
                'route' => 'admin_entreprises',
                'couleur' => 'primary',
                'roles' => [$roleAdmin],
                'ordre' => 4,
            ],
            [
                'nom' => 'Statistiques',
                'description' => 'Tableaux de bord et indicateurs',
                'icone' => 'chart-bar',
                'route' => 'admin_stats',
                'couleur' => 'secondary',
                'roles' => [$roleAdmin],
                'ordre' => 5,
            ],

            // ===== MODULES FORMATEUR =====
            [
                'nom' => 'Mes sessions',
                'description' => 'Voir mes sessions de formation assignées',
                'icone' => 'book-open',
                'route' => 'formateur_sessions',
                'couleur' => 'secondary',
                'roles' => [$roleFormateur],
                'ordre' => 10,
            ],
            [
                'nom' => 'Notes et évaluations',
                'description' => 'Saisir et gérer les notes des apprentis',
                'icone' => 'edit',
                'route' => 'formateur_notes',
                'couleur' => 'secondary',
                'roles' => [$roleFormateur],
                'ordre' => 11,
            ],
            [
                'nom' => 'Gestion des absences',
                'description' => 'Faire l\'appel et gérer les absences',
                'icone' => 'calendar-x',
                'route' => 'formateur_absences',
                'couleur' => 'secondary',
                'roles' => [$roleFormateur],
                'ordre' => 12,
            ],
            [
                'nom' => 'Emploi du temps',
                'description' => 'Consulter mon planning de cours',
                'icone' => 'calendar',
                'route' => 'formateur_planning',
                'couleur' => 'secondary',
                'roles' => [$roleFormateur],
                'ordre' => 13,
            ],

            // ===== MODULES APPRENTI =====
            [
                'nom' => 'Mes notes',
                'description' => 'Consulter mes notes et moyennes',
                'icone' => 'clipboard-list',
                'route' => 'apprenti_notes',
                'couleur' => 'tertiary',
                'roles' => [$roleApprenti],
                'ordre' => 20,
            ],
            [
                'nom' => 'Mes absences',
                'description' => 'Consulter et justifier mes absences',
                'icone' => 'calendar-x',
                'route' => 'apprenti_absences',
                'couleur' => 'tertiary',
                'roles' => [$roleApprenti],
                'ordre' => 21,
            ],
            [
                'nom' => 'Mon emploi du temps',
                'description' => 'Consulter mon planning de formation',
                'icone' => 'calendar',
                'route' => 'apprenti_planning',
                'couleur' => 'tertiary',
                'roles' => [$roleApprenti],
                'ordre' => 22,
            ],
            [
                'nom' => 'Ma formation',
                'description' => 'Détails de ma formation et progression',
                'icone' => 'book-open',
                'route' => 'apprenti_formation',
                'couleur' => 'tertiary',
                'roles' => [$roleApprenti],
                'ordre' => 23,
            ],
        ];

        foreach ($modulesData as $data) {
            $module = new Module();
            $module->setNom($data['nom']);
            $module->setDescription($data['description']);
            $module->setIcone($data['icone']);
            $module->setRoute($data['route']);
            $module->setCouleur($data['couleur']);
            $module->setOrdre($data['ordre']);
            $module->setActif(true);

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
