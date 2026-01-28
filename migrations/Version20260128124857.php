<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128124857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendrier_annee (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, libelle VARCHAR(100) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, heure_debut_defaut TIME NOT NULL, heure_fin_defaut TIME NOT NULL, actif TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_978544B977153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE jour_ferme (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, libelle VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, calendrier_id INT NOT NULL, INDEX IDX_647EEFB1FF52FC51 (calendrier_id), UNIQUE INDEX unique_calendrier_date (calendrier_id, date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE jour_ferme ADD CONSTRAINT FK_647EEFB1FF52FC51 FOREIGN KEY (calendrier_id) REFERENCES calendrier_annee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_matiere_formateur RENAME INDEX idx_smf_session_matiere TO IDX_EB54F36F14FAD903');
        $this->addSql('ALTER TABLE session_matiere_formateur RENAME INDEX idx_smf_formateur TO IDX_EB54F36F155D8F51');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jour_ferme DROP FOREIGN KEY FK_647EEFB1FF52FC51');
        $this->addSql('DROP TABLE calendrier_annee');
        $this->addSql('DROP TABLE jour_ferme');
        $this->addSql('ALTER TABLE session_matiere_formateur RENAME INDEX idx_eb54f36f14fad903 TO IDX_SMF_SESSION_MATIERE');
        $this->addSql('ALTER TABLE session_matiere_formateur RENAME INDEX idx_eb54f36f155d8f51 TO IDX_SMF_FORMATEUR');
    }
}
