<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour le module d'appel - Étape 9
 * 
 * Crée les tables :
 * - appel : Session d'appel pour une séance
 * - presence : Présence d'un apprenti avec token de signature
 */
final class Version20260131_AppelModule extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables appel et presence pour le module de gestion des présences avec signature par email';
    }

    public function up(Schema $schema): void
    {
        // Table appel
        $this->addSql('
            CREATE TABLE appel (
                id INT AUTO_INCREMENT NOT NULL,
                seance_id INT NOT NULL,
                formateur_id INT NOT NULL,
                date_appel DATETIME NOT NULL COMMENT \'Date et heure de création de l\'\'appel\',
                date_expiration DATETIME NOT NULL COMMENT \'Date et heure d\'\'expiration des liens de signature\',
                emails_envoyes TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'Indique si les emails ont été envoyés\',
                date_envoi_emails DATETIME DEFAULT NULL COMMENT \'Date d\'\'envoi des emails\',
                commentaire LONGTEXT DEFAULT NULL COMMENT \'Commentaire du formateur\',
                cloture TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'Indique si l\'\'appel est clôturé\',
                date_cloture DATETIME DEFAULT NULL COMMENT \'Date de clôture de l\'\'appel\',
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                INDEX idx_appel_seance (seance_id),
                INDEX idx_appel_formateur (formateur_id),
                INDEX idx_appel_date (date_appel),
                INDEX idx_appel_cloture (cloture),
                CONSTRAINT fk_appel_seance FOREIGN KEY (seance_id) 
                    REFERENCES seance_planifiee (id) ON DELETE CASCADE,
                CONSTRAINT fk_appel_formateur FOREIGN KEY (formateur_id) 
                    REFERENCES `user` (id) ON DELETE CASCADE,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Table presence
        $this->addSql('
            CREATE TABLE presence (
                id INT AUTO_INCREMENT NOT NULL,
                appel_id INT NOT NULL,
                apprenti_id INT NOT NULL,
                statut VARCHAR(20) NOT NULL COMMENT \'Statut de présence (en_attente, present, absent, absent_justifie, retard, non_signe)\',
                token VARCHAR(64) DEFAULT NULL COMMENT \'Token unique pour la signature par email\',
                date_signature DATETIME DEFAULT NULL COMMENT \'Date et heure de signature\',
                ip_signature VARCHAR(45) DEFAULT NULL COMMENT \'Adresse IP lors de la signature\',
                user_agent_signature LONGTEXT DEFAULT NULL COMMENT \'User-Agent du navigateur lors de la signature\',
                email_envoye TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'Indique si l\'\'email de signature a été envoyé\',
                date_envoi_email DATETIME DEFAULT NULL COMMENT \'Date d\'\'envoi de l\'\'email\',
                motif_absence LONGTEXT DEFAULT NULL COMMENT \'Motif en cas d\'\'absence justifiée\',
                minutes_retard SMALLINT DEFAULT NULL COMMENT \'Nombre de minutes de retard\',
                commentaire LONGTEXT DEFAULT NULL COMMENT \'Commentaire libre\',
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                UNIQUE INDEX uniq_presence_token (token),
                INDEX idx_presence_appel (appel_id),
                INDEX idx_presence_apprenti (apprenti_id),
                INDEX idx_presence_statut (statut),
                UNIQUE INDEX unique_appel_apprenti (appel_id, apprenti_id),
                CONSTRAINT fk_presence_appel FOREIGN KEY (appel_id) 
                    REFERENCES appel (id) ON DELETE CASCADE,
                CONSTRAINT fk_presence_apprenti FOREIGN KEY (apprenti_id) 
                    REFERENCES `user` (id) ON DELETE CASCADE,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        // Suppression dans l'ordre inverse des dépendances
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY fk_presence_appel');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY fk_presence_apprenti');
        $this->addSql('DROP TABLE presence');
        
        $this->addSql('ALTER TABLE appel DROP FOREIGN KEY fk_appel_seance');
        $this->addSql('ALTER TABLE appel DROP FOREIGN KEY fk_appel_formateur');
        $this->addSql('DROP TABLE appel');
    }
}
