-- =============================================
-- Migration Module Appel - CFA Gestion
-- Étape 9 : Gestion des présences avec signature par email
-- =============================================
-- À exécuter sur la base de données cfa_gestion
-- Testé sur MariaDB 10.x / MySQL 8.x
-- =============================================

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- TABLE: appel
-- Représente une session d'appel pour une séance
-- =============================================
CREATE TABLE IF NOT EXISTS appel (
    id INT AUTO_INCREMENT NOT NULL,
    seance_id INT NOT NULL COMMENT 'Référence vers la séance planifiée',
    formateur_id INT NOT NULL COMMENT 'Formateur ayant créé l\'appel',
    date_appel DATETIME NOT NULL COMMENT 'Date et heure de création de l\'appel',
    date_expiration DATETIME NOT NULL COMMENT 'Date et heure d\'expiration des liens de signature',
    emails_envoyes TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Indique si les emails ont été envoyés',
    date_envoi_emails DATETIME DEFAULT NULL COMMENT 'Date d\'envoi des emails',
    commentaire LONGTEXT DEFAULT NULL COMMENT 'Commentaire du formateur',
    cloture TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Indique si l\'appel est clôturé',
    date_cloture DATETIME DEFAULT NULL COMMENT 'Date de clôture de l\'appel',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Index pour la table appel
CREATE INDEX idx_appel_seance ON appel (seance_id);
CREATE INDEX idx_appel_formateur ON appel (formateur_id);
CREATE INDEX idx_appel_date ON appel (date_appel);
CREATE INDEX idx_appel_cloture ON appel (cloture);
CREATE INDEX idx_appel_expiration ON appel (date_expiration);

-- =============================================
-- TABLE: presence
-- Représente la présence d'un apprenti avec token de signature
-- =============================================
CREATE TABLE IF NOT EXISTS presence (
    id INT AUTO_INCREMENT NOT NULL,
    appel_id INT NOT NULL COMMENT 'Référence vers l\'appel',
    apprenti_id INT NOT NULL COMMENT 'Référence vers l\'apprenti',
    statut VARCHAR(20) NOT NULL DEFAULT 'en_attente' COMMENT 'Statut: en_attente, present, absent, absent_justifie, retard, non_signe',
    token VARCHAR(64) DEFAULT NULL COMMENT 'Token unique UUID pour la signature par email',
    date_signature DATETIME DEFAULT NULL COMMENT 'Date et heure de signature',
    ip_signature VARCHAR(45) DEFAULT NULL COMMENT 'Adresse IP lors de la signature (IPv4 ou IPv6)',
    user_agent_signature LONGTEXT DEFAULT NULL COMMENT 'User-Agent du navigateur lors de la signature',
    email_envoye TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Indique si l\'email de signature a été envoyé',
    date_envoi_email DATETIME DEFAULT NULL COMMENT 'Date d\'envoi de l\'email',
    motif_absence LONGTEXT DEFAULT NULL COMMENT 'Motif en cas d\'absence justifiée',
    minutes_retard SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Nombre de minutes de retard',
    commentaire LONGTEXT DEFAULT NULL COMMENT 'Commentaire libre',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Index pour la table presence
CREATE UNIQUE INDEX uniq_presence_token ON presence (token);
CREATE INDEX idx_presence_appel ON presence (appel_id);
CREATE INDEX idx_presence_apprenti ON presence (apprenti_id);
CREATE INDEX idx_presence_statut ON presence (statut);

-- Contrainte d'unicité : un apprenti ne peut avoir qu'une seule présence par appel
CREATE UNIQUE INDEX unique_appel_apprenti ON presence (appel_id, apprenti_id);

-- =============================================
-- CLÉS ÉTRANGÈRES
-- =============================================

-- Clés étrangères pour la table appel
ALTER TABLE appel
    ADD CONSTRAINT fk_appel_seance 
        FOREIGN KEY (seance_id) REFERENCES seance_planifiee (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_appel_formateur 
        FOREIGN KEY (formateur_id) REFERENCES `user` (id) ON DELETE CASCADE;

-- Clés étrangères pour la table presence
ALTER TABLE presence
    ADD CONSTRAINT fk_presence_appel 
        FOREIGN KEY (appel_id) REFERENCES appel (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_presence_apprenti 
        FOREIGN KEY (apprenti_id) REFERENCES `user` (id) ON DELETE CASCADE;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- VÉRIFICATION
-- =============================================
-- Afficher les tables créées
SHOW TABLES LIKE 'appel';
SHOW TABLES LIKE 'presence';

-- Afficher la structure des tables
DESCRIBE appel;
DESCRIBE presence;

-- =============================================
-- SCRIPT DE ROLLBACK (à exécuter en cas de problème)
-- =============================================
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS presence;
-- DROP TABLE IF EXISTS appel;
-- SET FOREIGN_KEY_CHECKS = 1;
