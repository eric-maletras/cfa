-- Script de mise à jour de la table formation
-- Exécuter uniquement les ALTER TABLE pour les colonnes manquantes

-- Vérifier et ajouter les colonnes manquantes
-- Note: Exécuter ce script dans phpMyAdmin ou en CLI

-- Colonnes de base (si table vide, refaire la migration complète)
-- ALTER TABLE formation ADD COLUMN IF NOT EXISTS intitule VARCHAR(255) NOT NULL;
-- ALTER TABLE formation ADD COLUMN IF NOT EXISTS intitule_court VARCHAR(100) DEFAULT NULL;
-- ALTER TABLE formation ADD COLUMN IF NOT EXISTS code_rncp VARCHAR(20) DEFAULT NULL;

-- Colonnes de durée
ALTER TABLE formation ADD COLUMN IF NOT EXISTS duree_heures SMALLINT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS duree_mois SMALLINT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS ects SMALLINT DEFAULT NULL;

-- Colonnes JSON
ALTER TABLE formation ADD COLUMN IF NOT EXISTS options JSON DEFAULT NULL;

-- Colonnes texte descriptif
ALTER TABLE formation ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS objectifs TEXT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS prerequis TEXT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS debouches TEXT DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS poursuite_etudes TEXT DEFAULT NULL;

-- Colonnes dates RNCP
ALTER TABLE formation ADD COLUMN IF NOT EXISTS date_enregistrement_rncp DATE DEFAULT NULL;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS date_echeance_rncp DATE DEFAULT NULL;

-- Colonnes statut et audit
ALTER TABLE formation ADD COLUMN IF NOT EXISTS actif TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE formation ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;

-- Relations (foreign keys)
-- ALTER TABLE formation ADD COLUMN IF NOT EXISTS niveau_qualification_id INT NOT NULL;
-- ALTER TABLE formation ADD COLUMN IF NOT EXISTS type_certification_id INT NOT NULL;

-- Index
CREATE INDEX IF NOT EXISTS idx_formation_rncp ON formation(code_rncp);
CREATE INDEX IF NOT EXISTS idx_formation_actif ON formation(actif);

-- ============================================
-- SI MariaDB < 10.0.2 (pas de IF NOT EXISTS sur ALTER)
-- Utiliser ce format à la place :
-- ============================================

-- SET @dbname = DATABASE();
-- SET @tablename = 'formation';
-- SET @columnname = 'objectifs';
-- SET @preparedStatement = (SELECT IF(
--   (
--     SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
--     WHERE TABLE_SCHEMA = @dbname
--       AND TABLE_NAME = @tablename
--       AND COLUMN_NAME = @columnname
--   ) > 0,
--   'SELECT 1',
--   CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT DEFAULT NULL')
-- ));
-- PREPARE alterIfNotExists FROM @preparedStatement;
-- EXECUTE alterIfNotExists;
-- DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- Alternative : Utiliser la migration Doctrine
-- ============================================
-- php bin/console doctrine:migrations:diff
-- php bin/console doctrine:migrations:migrate
