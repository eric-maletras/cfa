<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les rôles de base du CFA
 * 
 * Rôles créés :
 * - ROLE_ADMIN : Administrateur CFA
 * - ROLE_FORMATEUR : Formateur/Enseignant
 * - ROLE_APPRENTI : Apprenti/Étudiant
 */
class RoleFixtures extends Fixture implements FixtureGroupInterface
{
    // Constantes pour les références (utilisées par les autres fixtures)
    public const ROLE_ADMIN_REF = 'role-admin';
    public const ROLE_FORMATEUR_REF = 'role-formateur';
    public const ROLE_APPRENTI_REF = 'role-apprenti';

    public function load(ObjectManager $manager): void
    {
        // Rôle Administrateur
        $roleAdmin = new Role();
        $roleAdmin->setCode('ROLE_ADMIN');
        $roleAdmin->setLibelle('Administrateur');
        $roleAdmin->setDescription('Administrateur du CFA - Accès complet à toutes les fonctionnalités');
        $manager->persist($roleAdmin);
        $this->addReference(self::ROLE_ADMIN_REF, $roleAdmin);

        // Rôle Formateur
        $roleFormateur = new Role();
        $roleFormateur->setCode('ROLE_FORMATEUR');
        $roleFormateur->setLibelle('Formateur');
        $roleFormateur->setDescription('Formateur/Enseignant - Gestion des cours, devoirs et notes');
        $manager->persist($roleFormateur);
        $this->addReference(self::ROLE_FORMATEUR_REF, $roleFormateur);

        // Rôle Apprenti
        $roleApprenti = new Role();
        $roleApprenti->setCode('ROLE_APPRENTI');
        $roleApprenti->setLibelle('Apprenti');
        $roleApprenti->setDescription('Apprenti/Étudiant - Consultation des cours, notes et absences');
        $manager->persist($roleApprenti);
        $this->addReference(self::ROLE_APPRENTI_REF, $roleApprenti);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['base', 'roles'];
    }
}
