<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration : Ajout du module Gestion des absences
 * 
 * Exécution : php bin/console doctrine:migrations:migrate
 */
final class Version20250131100000AddModuleAbsences extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le module Gestion des absences et l\'associe au rôle ADMIN';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si le module existe déjà
        $moduleExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM module WHERE route = 'admin_absences'"
        );

        if ($moduleExists == 0) {
            // Insérer le module
            $this->addSql("
                INSERT INTO module (nom, description, route, icone, actif, ordre, created_at) 
                VALUES (
                    'Gestion des absences',
                    'Suivi et gestion des absences des apprentis',
                    'admin_absences',
                    '📊',
                    1,
                    70,
                    NOW()
                )
            ");
        }

        // Récupérer l'ID du module et du rôle ADMIN pour l'association
        // Cette partie sera exécutée via postUp car elle nécessite l'ID auto-généré
    }

    public function postUp(Schema $schema): void
    {
        // Récupérer l'ID du module
        $moduleId = $this->connection->fetchOne(
            "SELECT id FROM module WHERE route = 'admin_absences'"
        );

        if (!$moduleId) {
            return;
        }

        // Récupérer l'ID du rôle ADMIN
        $roleAdminId = $this->connection->fetchOne(
            "SELECT id FROM role WHERE code = 'ROLE_ADMIN'"
        );

        if (!$roleAdminId) {
            return;
        }

        // Vérifier si l'association existe déjà
        $assocExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM role_module WHERE role_id = ? AND module_id = ?",
            [$roleAdminId, $moduleId]
        );

        if ($assocExists == 0) {
            // Créer l'association
            $this->connection->executeStatement(
                "INSERT INTO role_module (role_id, module_id) VALUES (?, ?)",
                [$roleAdminId, $moduleId]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'association role_module
        $this->addSql("
            DELETE rm FROM role_module rm
            INNER JOIN module m ON rm.module_id = m.id
            WHERE m.route = 'admin_absences'
        ");

        // Supprimer le module
        $this->addSql("DELETE FROM module WHERE route = 'admin_absences'");
    }
}
