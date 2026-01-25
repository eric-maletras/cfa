<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125193639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE devoir (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) DEFAULT \'devoir\' NOT NULL, date_devoir DATE NOT NULL, date_limite DATE DEFAULT NULL, coefficient NUMERIC(4, 2) DEFAULT \'1.00\' NOT NULL, bareme NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL, visible TINYINT DEFAULT 1 NOT NULL, notes_publiees TINYINT DEFAULT 0 NOT NULL, commentaire_interne LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, session_id INT NOT NULL, formateur_id INT NOT NULL, INDEX IDX_749EA771613FECDF (session_id), INDEX IDX_749EA771155D8F51 (formateur_id), INDEX idx_devoir_date (date_devoir), INDEX idx_devoir_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, valeur NUMERIC(5, 2) DEFAULT NULL, statut VARCHAR(20) DEFAULT \'normal\' NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_saisie DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, devoir_id INT NOT NULL, apprenant_id INT NOT NULL, saisie_par_id INT DEFAULT NULL, INDEX IDX_CFBDFA14C583534E (devoir_id), INDEX IDX_CFBDFA14C5697D6D (apprenant_id), INDEX IDX_CFBDFA14C74AC7FE (saisie_par_id), INDEX idx_note_valeur (valeur), UNIQUE INDEX unique_devoir_apprenant (devoir_id, apprenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE devoir ADD CONSTRAINT FK_749EA771613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE devoir ADD CONSTRAINT FK_749EA771155D8F51 FOREIGN KEY (formateur_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14C583534E FOREIGN KEY (devoir_id) REFERENCES devoir (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14C5697D6D FOREIGN KEY (apprenant_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14C74AC7FE FOREIGN KEY (saisie_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771613FECDF');
        $this->addSql('ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771155D8F51');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C583534E');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C5697D6D');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C74AC7FE');
        $this->addSql('DROP TABLE devoir');
        $this->addSql('DROP TABLE note');
    }
}
