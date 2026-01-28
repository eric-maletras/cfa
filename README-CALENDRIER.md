# Module Calendrier Annuel - CFA

## Description

Ce module ajoute la gestion des calendriers annuels et des jours fermés au système CFA.

### Fonctionnalités

- **Calendriers annuels** : Gestion des années scolaires avec dates et horaires par défaut
- **Jours fermés** : Fériés, vacances, fermetures, ponts
- **Import automatique** : Jours fériés français (fixes + mobiles)
- **Vue calendrier** : Grille mensuelle visuelle avec navigation
- **Fixtures dynamiques** : Année scolaire calculée automatiquement

## Fichiers créés

### Enum
- `src/Enum/TypeJourFerme.php` - Types de jours fermés (ferie, vacances, fermeture, pont)

### Entités
- `src/Entity/CalendrierAnnee.php` - Calendrier annuel
- `src/Entity/JourFerme.php` - Jour fermé

### Repositories
- `src/Repository/CalendrierAnneeRepository.php`
- `src/Repository/JourFermeRepository.php`

### Services
- `src/Service/JoursFeriesFranceService.php` - Calcul des fériés français

### Formulaires
- `src/Form/CalendrierAnneeType.php`
- `src/Form/JourFermeType.php`

### Contrôleurs
- `src/Controller/Admin/CalendrierController.php` - CRUD complet
- `src/Controller/Admin/PlanningController.php` - Mis à jour avec stats calendrier

### Fixtures
- `src/DataFixtures/CalendrierFixtures.php` - Données dynamiques

### Templates
- `templates/admin/calendrier/index.html.twig` - Liste des calendriers
- `templates/admin/calendrier/show.html.twig` - Vue détaillée avec grille calendrier
- `templates/admin/calendrier/new.html.twig` - Formulaire création
- `templates/admin/calendrier/edit.html.twig` - Formulaire modification
- `templates/admin/calendrier/jours_fermes.html.twig` - Liste des jours fermés
- `templates/admin/jour_ferme/new.html.twig` - Ajout jour fermé
- `templates/admin/jour_ferme/edit.html.twig` - Modification jour fermé
- `templates/admin/planning/index.html.twig` - Sous-dashboard mis à jour

## Installation

### 1. Extraire les fichiers

```bash
# Extraire le ZIP dans votre projet Symfony
unzip calendrier-module.zip -d /chemin/vers/projet/
```

### 2. Créer les tables

```bash
# Générer la migration
php bin/console make:migration

# Exécuter la migration
php bin/console doctrine:migrations:migrate
```

### 3. Charger les fixtures (optionnel)

```bash
# ATTENTION : Cette commande purge la base de données !
# Utilisez --append pour ajouter sans purger

# Charger toutes les fixtures
php bin/console doctrine:fixtures:load

# Ou ajouter seulement les nouvelles fixtures
php bin/console doctrine:fixtures:load --append
```

### 4. Vider le cache

```bash
php bin/console cache:clear
```

## Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/calendriers` | GET | Liste des calendriers |
| `/admin/calendriers/new` | GET/POST | Création calendrier |
| `/admin/calendriers/{id}` | GET | Vue détaillée avec grille |
| `/admin/calendriers/{id}/edit` | GET/POST | Modification |
| `/admin/calendriers/{id}/delete` | POST | Suppression |
| `/admin/calendriers/{id}/toggle` | POST | Activer/désactiver |
| `/admin/calendriers/{id}/import-feries` | POST | Import fériés France |
| `/admin/calendriers/{id}/jours-fermes` | GET | Liste jours fermés |
| `/admin/calendriers/{id}/jours-fermes/new` | GET/POST | Ajout jour fermé |
| `/admin/calendriers/jours-fermes/{id}/edit` | GET/POST | Modification jour |
| `/admin/calendriers/jours-fermes/{id}/delete` | POST | Suppression jour |

## Fixtures dynamiques

Les fixtures calculent automatiquement l'année scolaire au moment de l'exécution :

- **Exécution en janvier 2026** → Crée calendrier 2025-2026 (01/09/2025 au 31/08/2026)
- **Exécution en octobre 2026** → Crée calendrier 2026-2027 (01/09/2026 au 31/08/2027)

Contenu généré :
- 📅 Calendrier de l'année scolaire courante
- 🇫🇷 Tous les jours fériés français des deux années civiles
- 🎄 Vacances de Noël (21/12 au 04/01)
- 🌉 Ponts calculés automatiquement (férié jeudi → pont vendredi)

## Service JoursFeriesFranceService

### Jours fériés fixes
- 1er janvier (Jour de l'An)
- 1er mai (Fête du Travail)
- 8 mai (Victoire 1945)
- 14 juillet (Fête Nationale)
- 15 août (Assomption)
- 1er novembre (Toussaint)
- 11 novembre (Armistice 1918)
- 25 décembre (Noël)

### Jours fériés mobiles (basés sur Pâques)
- Lundi de Pâques (Pâques + 1 jour)
- Ascension (Pâques + 39 jours)
- Lundi de Pentecôte (Pâques + 50 jours)

### Utilisation

```php
// Dans un contrôleur ou service
$service = new JoursFeriesFranceService();

// Obtenir tous les fériés d'une année
$feries = $service->getJoursFeries(2026);

// Vérifier si une date est fériée
$estFerie = $service->estJourFerie(new \DateTime('2026-07-14')); // true

// Obtenir le libellé
$libelle = $service->getLibelleJourFerie(new \DateTime('2026-07-14')); // "Fête Nationale"
```

## Types de jours fermés

| Type | Badge | Couleur | Usage |
|------|-------|---------|-------|
| `ferie` | danger (rouge) | #ffebee | Jours fériés nationaux |
| `vacances` | info (bleu) | #e3f2fd | Périodes de vacances |
| `fermeture` | warning (orange) | #fff3e0 | Fermetures exceptionnelles |
| `pont` | secondary (gris) | #f5f5f5 | Ponts accordés |

## Notes techniques

- Un seul calendrier peut être actif à la fois (toggle automatique)
- Suppression en cascade : supprimer un calendrier supprime ses jours fermés
- Les dates des jours fermés doivent être dans la période du calendrier
- Navigation calendrier avec paramètres URL (`?mois=X&annee=Y`)
