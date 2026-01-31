<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration : Correction de l'icône du module Motifs d'absence
 */
final class Version20260131210000FixModuleMotifAbsenceIcone extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction de l\'icône du module Motifs d\'absence';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour l'icône du module motifs_absence (utiliser clipboard-list qui existe)
        $this->addSql("UPDATE module SET icone = 'clipboard-list' WHERE route = 'admin_motifs_absence'");
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback nécessaire
    }
}
