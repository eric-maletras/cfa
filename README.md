# Module Gestion des Formations - CFA (v2)

## Corrections apportées

- ✅ Entité `Formation.php` complète avec tous les champs
- ✅ Formulaire `FormationType.php` corrigé (noms de propriétés)
- ✅ Template `form.html.twig` mis à jour
- ✅ Script SQL de migration inclus

## Fichiers à déployer

```
/var/www/cfa/
├── migrations/
│   └── update_formation_table.sql    # Script SQL si besoin
├── public/
│   └── css/
│       └── admin.css                 # Styles administration
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   └── FormationController.php
│   │   └── ModuleController.php
│   ├── Entity/
│   │   └── Formation.php             # REMPLACER - Version complète
│   └── Form/
│       ├── FormationType.php         # REMPLACER - Version corrigée
│       ├── NiveauQualificationType.php
│       ├── TypeCertificationType.php
│       ├── CodeNSFType.php
│       └── CodeROMEType.php
└── templates/
    └── admin/
        └── formations/
            ├── index.html.twig
            ├── form.html.twig        # REMPLACER - Version corrigée
            └── form_simple.html.twig
```

## Déploiement

### 1. Copier les fichiers

```bash
cd /var/www/cfa

# Option A : Extraire le ZIP
unzip cfa-formation-v2.zip -d /tmp/
cp -r /tmp/cfa-formation-v2/* .

# Option B : Copier manuellement chaque fichier
```

### 2. Mettre à jour la base de données

**Option A : Migration Doctrine (recommandé)**
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

**Option B : Script SQL direct**
```bash
mysql -u root -p cfa_db < migrations/update_formation_table.sql
```

**Option C : Dans phpMyAdmin**
Copier-coller le contenu de `update_formation_table.sql`

### 3. Vider le cache

```bash
php bin/console cache:clear
```

### 4. Vérifier les routes

```bash
php bin/console debug:router | grep admin
```

Résultat attendu :
```
admin_formation_index     GET        /admin/formations
admin_formation_new       GET|POST   /admin/formations/new
admin_formation_edit      GET|POST   /admin/formations/{id}/edit
admin_formation_delete    POST       /admin/formations/{id}/delete
admin_formation_toggle    POST       /admin/formations/{id}/toggle
admin_niveau_new          GET|POST   /admin/formations/niveau/new
admin_niveau_edit         GET|POST   /admin/formations/niveau/{id}/edit
admin_niveau_delete       POST       /admin/formations/niveau/{id}/delete
admin_type_new            GET|POST   /admin/formations/type/new
admin_type_edit           GET|POST   /admin/formations/type/{id}/edit
admin_type_delete         POST       /admin/formations/type/{id}/delete
admin_nsf_new             GET|POST   /admin/formations/nsf/new
admin_nsf_edit            GET|POST   /admin/formations/nsf/{id}/edit
admin_nsf_delete          POST       /admin/formations/nsf/{id}/delete
admin_rome_new            GET|POST   /admin/formations/rome/new
admin_rome_edit           GET|POST   /admin/formations/rome/{id}/edit
admin_rome_delete         POST       /admin/formations/rome/{id}/delete
```

## Structure de la table Formation

```sql
CREATE TABLE formation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    niveau_qualification_id INT NOT NULL,
    type_certification_id INT NOT NULL,
    intitule VARCHAR(255) NOT NULL,
    intitule_court VARCHAR(100) DEFAULT NULL,
    code_rncp VARCHAR(20) DEFAULT NULL,
    duree_heures SMALLINT DEFAULT NULL,
    duree_mois SMALLINT DEFAULT NULL,
    ects SMALLINT DEFAULT NULL,
    options JSON DEFAULT NULL,
    description TEXT DEFAULT NULL,
    objectifs TEXT DEFAULT NULL,
    prerequis TEXT DEFAULT NULL,
    debouches TEXT DEFAULT NULL,
    poursuite_etudes TEXT DEFAULT NULL,
    date_enregistrement_rncp DATE DEFAULT NULL,
    date_echeance_rncp DATE DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    
    INDEX idx_formation_rncp (code_rncp),
    INDEX idx_formation_actif (actif),
    
    CONSTRAINT fk_formation_niveau 
        FOREIGN KEY (niveau_qualification_id) 
        REFERENCES ref_niveau_qualification(id),
    CONSTRAINT fk_formation_type 
        FOREIGN KEY (type_certification_id) 
        REFERENCES ref_type_certification(id)
);
```

## Configuration du module

Vérifier dans la table `module` que la route est bien `admin_formations` :

```sql
SELECT * FROM module WHERE nom LIKE '%formation%';

-- Si besoin, mettre à jour :
UPDATE module SET route = 'admin_formations' WHERE nom LIKE '%formation%';
```

## Dépannage

### Erreur "Can't get a way to read the property..."
→ L'entité `Formation.php` n'a pas été mise à jour. Remplacer le fichier.

### Routes manquantes (niveau, type, nsf, rome)
→ Le contrôleur `FormationController.php` n'a pas été copié complètement.

### Erreur 500 sur /admin/formations
→ Vérifier les logs : `tail -f var/log/dev.log`

### Tables de référence vides
→ Exécuter les fixtures : `php bin/console doctrine:fixtures:load --append`
