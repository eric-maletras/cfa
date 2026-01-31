# CFA Step 10-3 : Justification des absences + Rapport d'heures

## Contenu

Cette archive fusionne step10-2b (justification) et ajoute le rapport d'heures d'absence.

### Fichiers inclus

**Contrôleurs :**
- `src/Controller/Admin/AbsenceController.php` - Contrôleur complet avec justification + rapport
- `src/Controller/ModuleController.php` - Mapping des routes

**Formulaires :**
- `src/Form/JustifierAbsenceType.php` - Formulaire de justification

**Templates :**
- `templates/admin/absences/index.html.twig` - Liste des apprentis
- `templates/admin/absences/show.html.twig` - Détail avec interface de justification
- `templates/admin/absences/rapport.html.twig` - **NOUVEAU** Rapport d'heures
- `templates/admin/absences/rapport_pdf.html.twig` - **NOUVEAU** Version PDF

## Routes disponibles

### Existantes (step 10-2b)
| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/absences` | GET | Liste des apprentis |
| `/admin/absences/{id}` | GET | Détail d'un apprenti |
| `/admin/absences/justifier/{id}` | POST | Justifier une absence |
| `/admin/absences/{id}/justifier-masse` | POST | Justifier en masse |
| `/admin/absences/annuler-justification/{id}` | POST | Annuler justification |

### Nouvelles (step 10-3)
| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/absences/rapport` | GET | Rapport d'heures d'absence |
| `/admin/absences/rapport/export-csv` | GET | Export CSV |
| `/admin/absences/rapport/export-pdf` | GET | Aperçu PDF |

## Installation

```bash
cd /var/www
unzip -o cfa-step10-3.zip
cd cfa.ericm.fr
php bin/console cache:clear
```

## Fonctionnalités

### Justification (step 10-2b)
- ☑️ Checkboxes pour sélection multiple
- ✏️ Bouton de justification individuelle  
- 📝 Modal avec liste des motifs
- ❌ Annulation de justification
- 🔵 Barre d'actions de masse

### Rapport (step 10-3)
- 📊 Statistiques globales
- 🔍 Filtres (formation, session, période, seuil)
- ⚠️ Indicateur d'alerte
- 📥 Export CSV
- 📄 Export PDF (impression)
