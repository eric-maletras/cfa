<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration : Création de la table salle pour le module planning
 */
final class Version20250128100000CreateSalle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create salle table for room management in scheduling module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE salle (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(20) NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            capacite INT DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            actif TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_4E977E5C77153098 (code),
            INDEX idx_salle_actif (actif),
            INDEX idx_salle_type (type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE salle');
    }
}
