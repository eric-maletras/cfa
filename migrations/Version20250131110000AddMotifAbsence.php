<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration : Création de la table motif_absence et modification de presence
 * 
 * Cette migration :
 * 1. Crée la table motif_absence pour stocker les motifs prédéfinis
 * 2. Renomme la colonne motif_absence en commentaire_justification dans presence
 * 3. Ajoute la colonne motif_absence_id (FK) dans presence
 * 4. Ajoute le module admin dans la table module
 */
final class Version20250131110000AddMotifAbsence extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table motif_absence et modification de presence';
    }

    public function up(Schema $schema): void
    {
        // 1. Créer la table motif_absence
        $this->addSql('
            CREATE TABLE motif_absence (
                id INT AUTO_INCREMENT NOT NULL,
                libelle VARCHAR(100) NOT NULL,
                code VARCHAR(50) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                justificatif_obligatoire TINYINT(1) NOT NULL DEFAULT 0,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                ordre INT NOT NULL DEFAULT 0,
                couleur VARCHAR(50) DEFAULT NULL,
                icone VARCHAR(20) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_motif_absence_code (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // 2. Renommer la colonne motif_absence en commentaire_justification dans presence
        $this->addSql('ALTER TABLE presence CHANGE motif_absence commentaire_justification LONGTEXT DEFAULT NULL');

        // 3. Ajouter la colonne motif_absence_id (FK) dans presence
        $this->addSql('ALTER TABLE presence ADD COLUMN motif_absence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_presence_motif_absence FOREIGN KEY (motif_absence_id) REFERENCES motif_absence (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_presence_motif_absence ON presence (motif_absence_id)');

        // 4. Insérer les motifs par défaut
        $this->addSql("
            INSERT INTO motif_absence (libelle, code, description, justificatif_obligatoire, actif, ordre, couleur, icone, created_at) VALUES
            ('Maladie', 'MALADIE', 'Absence pour raison de santé (rhume, grippe, etc.)', 1, 1, 1, 'warning', '🤒', NOW()),
            ('Rendez-vous médical', 'RDV_MEDICAL', 'Consultation médicale, spécialiste, examens', 1, 1, 2, 'info', '🏥', NOW()),
            ('Hospitalisation', 'HOSPITALISATION', 'Séjour hospitalier', 1, 1, 3, 'danger', '🏥', NOW()),
            ('Problème de transport', 'TRANSPORT', 'Grève, panne, accident sur le trajet', 0, 1, 4, 'secondary', '🚗', NOW()),
            ('Événement familial', 'FAMILLE', 'Décès, naissance, mariage dans la famille proche', 1, 1, 5, 'info', '👨‍👩‍👧', NOW()),
            ('Convocation officielle', 'CONVOCATION', 'Convocation tribunal, police, administration', 1, 1, 6, 'warning', '⚖️', NOW()),
            ('Mission entreprise', 'MISSION_ENTREPRISE', 'Déplacement professionnel, salon, formation entreprise', 1, 1, 7, 'success', '💼', NOW()),
            ('Examen / Concours', 'EXAMEN', 'Passage d\\'examen ou concours externe', 1, 1, 8, 'success', '🎓', NOW()),
            ('Intempéries', 'INTEMPERIES', 'Conditions météo empêchant le déplacement', 0, 1, 9, 'secondary', '🌧️', NOW()),
            ('Autre motif justifié', 'AUTRE', 'Autre motif avec justification écrite', 0, 1, 99, 'secondary', '❓', NOW())
        ");

        // 5. Ajouter le module admin
        $this->addSql("
            INSERT INTO module (nom, description, icone, route, couleur, ordre, actif) VALUES
            ('Motifs d\\'absence', 'Gestion des motifs prédéfinis d\\'absence', 'list-check', 'admin_motifs_absence', 'secondary', 8, 1)
        ");

        // 6. Lier le module au rôle ADMIN (ID 110)
        $this->addSql("
            INSERT INTO module_role (module_id, role_id) 
            SELECT m.id, 110 FROM module m WHERE m.route = 'admin_motifs_absence'
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer la liaison module_role
        $this->addSql("
            DELETE mr FROM module_role mr 
            INNER JOIN module m ON mr.module_id = m.id 
            WHERE m.route = 'admin_motifs_absence'
        ");

        // Supprimer le module
        $this->addSql("DELETE FROM module WHERE route = 'admin_motifs_absence'");

        // Supprimer la contrainte et la colonne FK dans presence
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_presence_motif_absence');
        $this->addSql('DROP INDEX IDX_presence_motif_absence ON presence');
        $this->addSql('ALTER TABLE presence DROP COLUMN motif_absence_id');

        // Renommer commentaire_justification en motif_absence
        $this->addSql('ALTER TABLE presence CHANGE commentaire_justification motif_absence LONGTEXT DEFAULT NULL');

        // Supprimer la table motif_absence
        $this->addSql('DROP TABLE motif_absence');
    }
}
