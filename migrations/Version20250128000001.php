<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table session_matiere_formateur
 * Permet d'assigner des formateurs aux matières d'une session
 */
final class Version20250128000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table session_matiere_formateur pour l\'assignation des formateurs aux matières de session';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE session_matiere_formateur (
            id INT AUTO_INCREMENT NOT NULL,
            session_matiere_id INT NOT NULL,
            formateur_id INT NOT NULL,
            heures_assignees SMALLINT DEFAULT NULL,
            est_responsable TINYINT(1) DEFAULT 0 NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_SMF_SESSION_MATIERE (session_matiere_id),
            INDEX IDX_SMF_FORMATEUR (formateur_id),
            INDEX idx_smf_responsable (est_responsable),
            UNIQUE INDEX unique_session_matiere_formateur (session_matiere_id, formateur_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_SMF_SESSION_MATIERE FOREIGN KEY (session_matiere_id) 
                REFERENCES session_matiere (id) ON DELETE CASCADE,
            CONSTRAINT FK_SMF_FORMATEUR FOREIGN KEY (formateur_id) 
                REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE session_matiere_formateur');
    }
}
