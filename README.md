# CFA Step 10-3 : Rapport d'heures d'absence

## Contenu

Cette archive contient les fichiers pour ajouter le rapport d'heures d'absence au module de gestion des absences.

### Fichiers inclus

- `src/Controller/Admin/AbsenceController.php` - Contrôleur avec les nouvelles routes rapport
- `templates/admin/absences/index.html.twig` - Template liste des absences (original step 10-1)
- `templates/admin/absences/show.html.twig` - Template détail apprenti (original step 10-1)
- `templates/admin/absences/rapport.html.twig` - **NOUVEAU** Template du rapport d'heures
- `templates/admin/absences/rapport_pdf.html.twig` - **NOUVEAU** Template PDF du rapport

## Nouvelles routes ajoutées

| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/absences/rapport` | GET | Rapport d'heures d'absence |
| `/admin/absences/rapport/export-csv` | GET | Export CSV du rapport |
| `/admin/absences/rapport/export-pdf` | GET | Aperçu PDF du rapport |

## Installation

```bash
cd /var/www
unzip -o cfa-step10-3.zip
cd cfa.ericm.fr
php bin/console cache:clear
```

## Fonctionnalités du rapport

- **Filtres** : Formation, session, période, seuil d'alerte
- **Statistiques globales** : Nombre d'apprentis, heures totales, justifiées, non justifiées, en alerte
- **Export CSV** : Téléchargement du rapport au format tableur
- **Aperçu PDF** : Version imprimable avec bouton d'impression

## Notes

- Les routes existantes (`admin_absence_index`, `admin_absence_show`) sont conservées sans modification
- Les nouvelles routes utilisent le préfixe `admin_absences_` (avec "s")
- Le seuil d'alerte par défaut est de 20 heures
