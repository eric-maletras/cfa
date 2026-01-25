# Fixtures CFA - Données de test

## Résumé des données créées

### Rôles (3)
| Code | Libellé |
|------|---------|
| ROLE_ADMIN | Administrateur |
| ROLE_FORMATEUR | Formateur |
| ROLE_APPRENTI | Apprenti |

### Formations (4 BTS)
| Code court | Intitulé | RNCP |
|------------|----------|------|
| BTS SIO SISR | Services Informatiques aux Organisations - Systèmes et Réseaux | RNCP35340 |
| BTS SIO SLAM | Services Informatiques aux Organisations - Développement | RNCP35340 |
| BTS CIEL IR | Cybersécurité, Informatique et réseaux Électroniques - Informatique et Réseaux | RNCP37391 |
| BTS SAM | Support à l'Action Managériale | RNCP34029 |

### Sessions (8)
- **4 sessions actives** (2024-2026) : `BTSSIISISR-2024`, `BTSSIO­SLAM-2024`, `BTSCIELIR-2024`, `BTSSAM-2024`
- **4 sessions inactives** (2023-2025) : mêmes codes avec `-2023`

### Utilisateurs

| Type | Nombre | Email pattern | Mot de passe |
|------|--------|---------------|--------------|
| Admin | 1 | admin@cfa-demo.fr | Admin123! |
| Formateurs | 12 | prenom.nom@cfa-demo.fr | Formateur123! |
| Apprentis inscrits | 120 | prenom.nomXX@apprenti.cfa-demo.fr | Apprenti123! |
| Apprentis non inscrits | 20 | idem | Apprenti123! |

### Formateurs et leurs BTS

| Formateur | Spécialité | BTS |
|-----------|------------|-----|
| Sophie Martin | Culture générale | SIO-SISR, SIO-SLAM, CIEL-IR, SAM |
| Philippe Bernard | Anglais | SIO-SISR, SIO-SLAM, CIEL-IR, SAM |
| Marie Dubois | Mathématiques | SIO-SISR, SIO-SLAM, CIEL-IR |
| Jean Laurent | Économie-Droit | SIO-SISR, SIO-SLAM, SAM |
| Isabelle Moreau | CEJM | CIEL-IR, SAM |
| Antoine Garcia | Réseaux/Cybersécurité | SIO-SISR, CIEL-IR |
| Nathalie Roux | Développement web | SIO-SLAM, SIO-SISR |
| François Petit | Admin systèmes | SIO-SISR |
| Céline Leroy | Développement apps | SIO-SLAM |
| Thomas Simon | Électronique/Embarqué | CIEL-IR |
| Claire Michel | Gestion projet/Comm | SAM |
| David Lefebvre | Bureautique/Collab | SAM |

### Devoirs et Notes
- **1 devoir par formateur/session active** en décembre 2024
- **Notes générées** avec distribution normale (moyenne ~12/20)
- **5% d'absences** simulées
- **Commentaires automatiques** selon la note

## Installation

1. Copier les fichiers dans `src/DataFixtures/`

2. Installer le bundle (si pas déjà fait) :
```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

3. Charger les fixtures :
```bash
# Environnement dev (purge et recharge)
php bin/console doctrine:fixtures:load --no-interaction

# Environnement test
php bin/console doctrine:fixtures:load --env=test --no-interaction

# Uniquement certains groupes
php bin/console doctrine:fixtures:load --group=base
php bin/console doctrine:fixtures:load --group=evaluations
```

## Groupes de fixtures

| Groupe | Fixtures incluses |
|--------|-------------------|
| `base` | Rôles, Références, Formations, Users, Sessions, Inscriptions |
| `roles` | RoleFixtures |
| `references` | ReferenceFixtures |
| `formations` | FormationFixtures |
| `users` | UserFixtures |
| `sessions` | SessionFixtures |
| `inscriptions` | InscriptionFixtures |
| `evaluations` | DevoirNoteFixtures |
| `devoirs` | DevoirNoteFixtures |
| `notes` | DevoirNoteFixtures |

## Ordre de chargement

```
RoleFixtures
    ↓
ReferenceFixtures
    ↓
FormationFixtures
    ↓
UserFixtures
    ↓
SessionFixtures
    ↓
InscriptionFixtures
    ↓
DevoirNoteFixtures
```

## Comptes de test

### Administrateur
- **Email** : admin@cfa-demo.fr
- **Mot de passe** : Admin123!

### Formateur (exemple)
- **Email** : sophie.martin@cfa-demo.fr
- **Mot de passe** : Formateur123!

### Apprenti (exemple)
- **Email** : lucas.martin42@apprenti.cfa-demo.fr
- **Mot de passe** : Apprenti123!
