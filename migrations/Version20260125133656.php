<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125133656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, intitule VARCHAR(255) NOT NULL, intitule_court VARCHAR(100) DEFAULT NULL, code_rncp VARCHAR(20) DEFAULT NULL, duree_heures SMALLINT DEFAULT NULL, duree_mois SMALLINT DEFAULT NULL, ects SMALLINT DEFAULT NULL, options JSON DEFAULT NULL, description LONGTEXT DEFAULT NULL, objectifs LONGTEXT DEFAULT NULL, prerequis LONGTEXT DEFAULT NULL, debouches LONGTEXT DEFAULT NULL, poursuite_etudes LONGTEXT DEFAULT NULL, date_enregistrement_rncp DATE DEFAULT NULL, date_echeance_rncp DATE DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, niveau_qualification_id INT NOT NULL, type_certification_id INT NOT NULL, INDEX IDX_404021BF459F298F (niveau_qualification_id), INDEX IDX_404021BF9CD8873B (type_certification_id), INDEX idx_formation_rncp (code_rncp), INDEX idx_formation_actif (actif), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE formation_code_nsf (formation_id INT NOT NULL, code_nsf_id INT NOT NULL, INDEX IDX_3279B13B5200282E (formation_id), INDEX IDX_3279B13BC585B9C9 (code_nsf_id), PRIMARY KEY (formation_id, code_nsf_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE formation_code_rome (formation_id INT NOT NULL, code_rome_id INT NOT NULL, INDEX IDX_B2E481375200282E (formation_id), INDEX IDX_B2E48137D3C44F80 (code_rome_id), PRIMARY KEY (formation_id, code_rome_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ref_code_nsf (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, libelle VARCHAR(255) NOT NULL, niveau SMALLINT NOT NULL, type_domaine VARCHAR(30) DEFAULT NULL, code_fonction VARCHAR(1) DEFAULT NULL, libelle_fonction VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, parent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_73FC204D77153098 (code), INDEX idx_nsf_code (code), INDEX idx_nsf_niveau (niveau), INDEX idx_nsf_parent (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ref_code_rome (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(5) NOT NULL, libelle VARCHAR(255) NOT NULL, domaine_code VARCHAR(1) NOT NULL, domaine_libelle VARCHAR(150) NOT NULL, sous_domaine_code VARCHAR(2) DEFAULT NULL, sous_domaine_libelle VARCHAR(150) DEFAULT NULL, definition LONGTEXT DEFAULT NULL, conditions_acces LONGTEXT DEFAULT NULL, version_rome VARCHAR(10) DEFAULT \'4.0\' NOT NULL, date_maj DATE DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_BC3D0AF77153098 (code), INDEX idx_rome_code (code), INDEX idx_rome_domaine (domaine_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ref_niveau_qualification (id INT AUTO_INCREMENT NOT NULL, code SMALLINT NOT NULL, libelle VARCHAR(100) NOT NULL, equivalent_diplome VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, ancien_niveau VARCHAR(10) DEFAULT NULL, niveau_cec SMALLINT DEFAULT NULL, actif TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_2ACC864777153098 (code), INDEX idx_niveau_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ref_type_certification (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, libelle VARCHAR(150) NOT NULL, libelle_abrege VARCHAR(50) DEFAULT NULL, certificateur_type VARCHAR(30) NOT NULL, certificateur_nom VARCHAR(150) DEFAULT NULL, enregistrement_rncp VARCHAR(20) NOT NULL, apprentissage_possible TINYINT DEFAULT 1 NOT NULL, vae_possible TINYINT DEFAULT 1 NOT NULL, description LONGTEXT DEFAULT NULL, ordre_affichage SMALLINT DEFAULT 0 NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_4E6D550077153098 (code), INDEX idx_type_cert_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF459F298F FOREIGN KEY (niveau_qualification_id) REFERENCES ref_niveau_qualification (id)');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF9CD8873B FOREIGN KEY (type_certification_id) REFERENCES ref_type_certification (id)');
        $this->addSql('ALTER TABLE formation_code_nsf ADD CONSTRAINT FK_3279B13B5200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_code_nsf ADD CONSTRAINT FK_3279B13BC585B9C9 FOREIGN KEY (code_nsf_id) REFERENCES ref_code_nsf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_code_rome ADD CONSTRAINT FK_B2E481375200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_code_rome ADD CONSTRAINT FK_B2E48137D3C44F80 FOREIGN KEY (code_rome_id) REFERENCES ref_code_rome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ref_code_nsf ADD CONSTRAINT FK_73FC204D727ACA70 FOREIGN KEY (parent_id) REFERENCES ref_code_nsf (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF459F298F');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF9CD8873B');
        $this->addSql('ALTER TABLE formation_code_nsf DROP FOREIGN KEY FK_3279B13B5200282E');
        $this->addSql('ALTER TABLE formation_code_nsf DROP FOREIGN KEY FK_3279B13BC585B9C9');
        $this->addSql('ALTER TABLE formation_code_rome DROP FOREIGN KEY FK_B2E481375200282E');
        $this->addSql('ALTER TABLE formation_code_rome DROP FOREIGN KEY FK_B2E48137D3C44F80');
        $this->addSql('ALTER TABLE ref_code_nsf DROP FOREIGN KEY FK_73FC204D727ACA70');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE formation_code_nsf');
        $this->addSql('DROP TABLE formation_code_rome');
        $this->addSql('DROP TABLE ref_code_nsf');
        $this->addSql('DROP TABLE ref_code_rome');
        $this->addSql('DROP TABLE ref_niveau_qualification');
        $this->addSql('DROP TABLE ref_type_certification');
    }
}
