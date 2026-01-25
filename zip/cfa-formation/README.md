# Module Gestion des Formations - CFA

## Fichiers à déployer

Copiez les fichiers sur votre serveur en respectant l'arborescence :

```
votre-projet-symfony/
├── public/
│   └── css/
│       └── admin.css                    # NOUVEAU - Styles administration
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   └── FormationController.php  # NOUVEAU - CRUD formations
│   │   └── ModuleController.php         # REMPLACER - Version avec redirections
│   └── Form/
│       ├── FormationType.php            # NOUVEAU
│       ├── NiveauQualificationType.php  # NOUVEAU
│       ├── TypeCertificationType.php    # NOUVEAU
│       ├── CodeNSFType.php              # NOUVEAU
│       └── CodeROMEType.php             # NOUVEAU
└── templates/
    └── admin/
        └── formations/
            ├── index.html.twig          # NOUVEAU - Page principale avec onglets
            ├── form.html.twig           # NOUVEAU - Formulaire formation
            └── form_simple.html.twig    # NOUVEAU - Formulaire tables référence
```

## Commandes de déploiement

```bash
# Sur le serveur, depuis /var/www/cfa

# 1. Copier les fichiers (adapter selon votre méthode de transfert)
# Via SCP, SFTP, Git, etc.

# 2. Vider le cache Symfony
php bin/console cache:clear

# 3. Vérifier les routes
php bin/console debug:router | grep admin_formation
```

## Routes créées

| Route | Méthode | URL | Description |
|-------|---------|-----|-------------|
| admin_formation_index | GET | /admin/formations | Page principale avec onglets |
| admin_formation_new | GET/POST | /admin/formations/new | Créer une formation |
| admin_formation_edit | GET/POST | /admin/formations/{id}/edit | Modifier une formation |
| admin_formation_delete | POST | /admin/formations/{id}/delete | Supprimer une formation |
| admin_formation_toggle | POST | /admin/formations/{id}/toggle | Activer/Désactiver |
| admin_niveau_new | GET/POST | /admin/formations/niveau/new | Créer un niveau |
| admin_niveau_edit | GET/POST | /admin/formations/niveau/{id}/edit | Modifier un niveau |
| admin_niveau_delete | POST | /admin/formations/niveau/{id}/delete | Supprimer un niveau |
| admin_type_new | GET/POST | /admin/formations/type/new | Créer un type |
| admin_type_edit | GET/POST | /admin/formations/type/{id}/edit | Modifier un type |
| admin_type_delete | POST | /admin/formations/type/{id}/delete | Supprimer un type |
| admin_nsf_new | GET/POST | /admin/formations/nsf/new | Créer un code NSF |
| admin_nsf_edit | GET/POST | /admin/formations/nsf/{id}/edit | Modifier un code NSF |
| admin_nsf_delete | POST | /admin/formations/nsf/{id}/delete | Supprimer un code NSF |
| admin_rome_new | GET/POST | /admin/formations/rome/new | Créer un code ROME |
| admin_rome_edit | GET/POST | /admin/formations/rome/{id}/edit | Modifier un code ROME |
| admin_rome_delete | POST | /admin/formations/rome/{id}/delete | Supprimer un code ROME |

## Configuration du module dans la base de données

Vérifiez que le module "Gestion des formations" est bien configuré avec la route `admin_formations` :

```sql
SELECT * FROM module WHERE route = 'admin_formations';

-- Si nécessaire, mettre à jour :
UPDATE module SET route = 'admin_formations' WHERE nom LIKE '%formation%';
```

## Sécurité

Le contrôleur utilise `#[IsGranted('ROLE_ADMIN')]` pour restreindre l'accès.
Assurez-vous que vos utilisateurs admin ont bien ce rôle attribué via la table `role` 
avec le code `ROLE_ADMIN`.

## Dépendances

Les formulaires utilisent les types Symfony standard. Si vous n'avez pas le package Form :

```bash
composer require symfony/form
```

## Interface

L'interface utilise un système d'onglets pour naviguer entre :
- **Formations** : liste et CRUD des formations du CFA
- **Niveaux** : niveaux de qualification (1 à 8)
- **Types** : types de certification (BTS, TP, CQP...)
- **Codes NSF** : nomenclature des spécialités de formation
- **Codes ROME** : répertoire opérationnel des métiers

Chaque onglet affiche un tableau avec les actions disponibles selon que l'entité 
est utilisée ou non par des formations (protection contre la suppression en cascade).
