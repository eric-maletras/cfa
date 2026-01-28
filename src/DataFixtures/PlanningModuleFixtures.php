<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture pour ajouter le module Planning au dashboard
 * 
 * Usage: php bin/console doctrine:fixtures:load --group=planning_module --append
 */
class PlanningModuleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $roleAdmin = $this->getReference('role-admin', Role::class);
        $roleFormateur = $this->getReference('role-formateur', Role::class);

        // Module Planning pour les admins
        $modulePlanning = new Module();
        $modulePlanning->setNom('Planning & Ressources');
        $modulePlanning->setDescription('Gérer les salles, calendriers et ressources pédagogiques');
        $modulePlanning->setIcone('calendar-days');
        $modulePlanning->setRoute('admin_planning');
        $modulePlanning->setCouleur('primary');
        $modulePlanning->setOrdre(6); // Après Statistiques (ordre 5)
        $modulePlanning->setActif(true);
        $modulePlanning->addRole($roleAdmin);

        $manager->persist($modulePlanning);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['planning_module', 'planning'];
    }
}
