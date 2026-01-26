# Module Matières - CFA Application

## Contenu du package

Ce package contient les fichiers pour le module de gestion des matières, leur liaison avec les formations, et la redescente automatique sur les sessions.

---

## ÉTAPE 1 : Matières et FormationMatiere (Référentiel)

### Entités

- `src/Entity/Matiere.php` - Référentiel des matières (code, libellé, description)
- `src/Entity/FormationMatiere.php` - Liaison formation ↔ matière avec volume horaire et coefficient
- `src/Entity/Formation.php` - **MODIFICATION** - Ajout de la relation `formationMatieres`

### Repositories

- `src/Repository/MatiereRepository.php`
- `src/Repository/FormationMatiereRepository.php`

### Contrôleurs

- `src/Controller/Admin/MatiereController.php` - CRUD admin des matières
- `src/Controller/Admin/FormationMatiereController.php` - Gestion des matières par formation

### Formulaires

- `src/Form/MatiereType.php`
- `src/Form/FormationMatiereType.php`

### Templates

- `templates/admin/matieres/index.html.twig` - Liste des matières
- `templates/admin/matieres/form.html.twig` - Formulaire matière
- `templates/admin/matieres/show.html.twig` - Détail matière
- `templates/admin/matieres/formation_matieres.html.twig` - Matières d'une formation
- `templates/admin/matieres/formation_matiere_form.html.twig` - Formulaire liaison

### Fixtures

- `src/DataFixtures/MatiereFixtures.php` - 9 matières BTS SIO + liaisons

---

## ÉTAPE 2 : SessionMatiere (Redescente sur les sessions)

### Concept

Lors de la création d'une session, les matières du référentiel (FormationMatiere) sont automatiquement copiées vers des SessionMatiere, avec la possibilité d'ajuster les volumes pour cette session spécifique.

```
Formation
    └── FormationMatiere (référentiel)
            ├── Matiere
            ├── volumeHeuresReferentiel
            └── coefficient

    └── Session
            └── SessionMatiere (copie ajustable)
                    ├── Matiere
                    ├── volumeHeuresReferentiel (copié)
                    ├── volumeHeuresPlanifie (ajustable)
                    ├── volumeHeuresRealise (suivi)
                    └── actif (désactivable)
```

### Entités

- `src/Entity/SessionMatiere.php` - **NOUVEAU** - Matières d'une session
- `src/Entity/Session.php` - **MODIFICATION** - Ajout relation `sessionMatieres` et méthode `initMatieresFromFormation()`

### Repositories

- `src/Repository/SessionMatiereRepository.php` - **NOUVEAU**

### Contrôleurs

- `src/Controller/Admin/SessionMatiereController.php` - **NOUVEAU** - Gestion des matières d'une session
- `src/Controller/SessionController.php` - **MODIFICATION** - Appel automatique de `initMatieresFromFormation()` à la création

### Formulaires

- `src/Form/SessionMatiereType.php` - **NOUVEAU**

### Templates

- `templates/admin/session_matieres/index.html.twig` - Liste des matières de session avec édition en masse
- `templates/admin/session_matieres/form.html.twig` - Formulaire ajout/modification
- `templates/admin/session_matieres/_session_matieres_card.html.twig` - Partial pour la fiche session

### Fixtures

- `src/DataFixtures/SessionFixtures.php` - **NOUVEAU** - Sessions BTS SIO avec matières initialisées

---

## Installation

### 1. Copier les fichiers

```bash
cd /var/www/cfa.ericm.fr

# Entités
cp -r src/Entity/* /var/www/cfa.ericm.fr/src/Entity/

# Repositories
cp -r src/Repository/* /var/www/cfa.ericm.fr/src/Repository/

# Contrôleurs
cp -r src/Controller/* /var/www/cfa.ericm.fr/src/Controller/

# Formulaires
cp -r src/Form/* /var/www/cfa.ericm.fr/src/Form/

# Fixtures
cp -r src/DataFixtures/* /var/www/cfa.ericm.fr/src/DataFixtures/

# Templates
cp -r templates/admin/* /var/www/cfa.ericm.fr/templates/admin/
```

### 2. Mise à jour du schéma de base de données

```bash
cd /var/www/cfa.ericm.fr

# Vérifier les changements
php bin/console doctrine:schema:update --dump-sql

# Appliquer les changements
php bin/console doctrine:schema:update --force

# OU avec les migrations (recommandé en production)
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 3. Charger les fixtures (environnement de dev)

```bash
# Charger toutes les fixtures de base (attention : réinitialise tout)
php bin/console doctrine:fixtures:load --group=base

# OU charger par étapes (si les données existent déjà)
php bin/console doctrine:fixtures:load --group=matieres --append
php bin/console doctrine:fixtures:load --group=sessions --append
```

### 4. Vider le cache

```bash
php bin/console cache:clear
```

---

## Routes créées

### Étape 1 - Gestion des matières

| Route | URL | Description |
|-------|-----|-------------|
| `admin_matiere_index` | `/admin/matieres` | Liste des matières |
| `admin_matiere_new` | `/admin/matieres/new` | Création matière |
| `admin_matiere_show` | `/admin/matieres/{id}` | Détail matière |
| `admin_matiere_edit` | `/admin/matieres/{id}/edit` | Modification |
| `admin_matiere_delete` | `/admin/matieres/{id}/delete` | Suppression |
| `admin_matiere_toggle` | `/admin/matieres/{id}/toggle` | Activer/désactiver |
| `admin_formation_matiere_index` | `/admin/formations/{formationId}/matieres` | Matières formation |
| `admin_formation_matiere_add` | `/admin/formations/{formationId}/matieres/add` | Ajouter |
| `admin_formation_matiere_edit` | `/admin/formations/{formationId}/matieres/{id}/edit` | Modifier |
| `admin_formation_matiere_delete` | `/admin/formations/{formationId}/matieres/{id}/delete` | Retirer |

### Étape 2 - Matières de session

| Route | URL | Description |
|-------|-----|-------------|
| `admin_session_matiere_index` | `/admin/sessions/{sessionId}/matieres` | Matières session |
| `admin_session_matiere_init` | `/admin/sessions/{sessionId}/matieres/init` | Initialiser depuis référentiel |
| `admin_session_matiere_add` | `/admin/sessions/{sessionId}/matieres/add` | Ajouter hors référentiel |
| `admin_session_matiere_edit` | `/admin/sessions/{sessionId}/matieres/{id}/edit` | Modifier |
| `admin_session_matiere_toggle` | `/admin/sessions/{sessionId}/matieres/{id}/toggle` | Activer/désactiver |
| `admin_session_matiere_delete` | `/admin/sessions/{sessionId}/matieres/{id}/delete` | Supprimer |
| `admin_session_matiere_update_volumes` | `/admin/sessions/{sessionId}/matieres/update-volumes` | Mise à jour en masse |

---

## Comportement automatique

### À la création d'une session

1. L'utilisateur crée une nouvelle session en choisissant une formation
2. Après validation, `initMatieresFromFormation()` est automatiquement appelé
3. Toutes les `FormationMatiere` sont copiées en `SessionMatiere`
4. Les volumes horaires et coefficients du référentiel sont conservés
5. L'utilisateur peut ensuite ajuster les volumes planifiés si nécessaire

### Données copiées automatiquement

| FormationMatiere | → | SessionMatiere |
|------------------|---|----------------|
| matiere | → | matiere |
| volumeHeuresReferentiel | → | volumeHeuresReferentiel |
| coefficient | → | coefficient |
| ordre | → | ordre |
| — | → | volumeHeuresPlanifie (null) |
| — | → | volumeHeuresRealise (null) |
| — | → | actif (true) |

---

## Intégration dans l'interface

### Accès aux matières du référentiel

Dans `templates/admin/formations/index.html.twig`, l'onglet "📖 Matières" est déjà ajouté.

### Accès aux matières d'une session

Ajouter dans `templates/session/show.html.twig` :

```twig
{# Section matières #}
{% include 'admin/session_matieres/_session_matieres_card.html.twig' %}

{# OU juste un bouton d'accès #}
<a href="{{ path('admin_session_matiere_index', {sessionId: session.id}) }}" 
   class="btn btn--secondary">
    📖 Gérer les matières
</a>
```

---

## Matières créées par les fixtures

| Code | Libellé | Volume SLAM | Volume SISR | Coef |
|------|---------|-------------|-------------|------|
| CULT | Culture générale et expression | 120h | 120h | 2.0 |
| ANGL | Anglais | 120h | 120h | 2.0 |
| MATH | Mathématiques pour l'informatique | 90h | 90h | 2.0 |
| CEJM | Culture économique, juridique et managériale | 120h | 120h | 3.0 |
| SI | Support et mise à disposition de services | 240h | 240h | 4.0 |
| SLAM | Solutions logicielles et applications métiers | 280h | — | 4.0 |
| SISR | Administration des systèmes et des réseaux | — | 280h | 4.0 |
| CYBER-SLAM | Cybersécurité (option SLAM) | 70h | — | 4.0 |
| CYBER-SISR | Cybersécurité (option SISR) | — | 70h | 4.0 |

**Total par option : 1040h**

## Sessions créées par les fixtures

| Code | Libellé | Statut |
|------|---------|--------|
| BTSSIO-SLAM-2024 | BTS SIO option SLAM - Promotion 2024-2026 | En cours |
| BTSSIO-SISR-2024 | BTS SIO option SISR - Promotion 2024-2026 | En cours |
| BTSSIO-SLAM-2025 | BTS SIO option SLAM - Promotion 2025-2027 | Inscriptions ouvertes |
| BTSSIO-SISR-2025 | BTS SIO option SISR - Promotion 2025-2027 | Inscriptions ouvertes |

Les sessions en cours ont ~40% de réalisation simulée.
