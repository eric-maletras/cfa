# Tables de référence CFA - Entités Doctrine et Données

## Contenu du package

Ce package contient les entités Doctrine, repositories et fixtures pour les 4 tables de référence normalisées :

| Table | Description | Source |
|-------|-------------|--------|
| `ref_niveau_qualification` | 8 niveaux du cadre national (1-8) | Décret n° 2019-14 du 8 janvier 2019 |
| `ref_type_certification` | Types de certifications (BTS, TP, CQP...) | France Compétences |
| `ref_code_nsf` | Nomenclature des Spécialités de Formation | INSEE (Décret n° 94-522) |
| `ref_code_rome` | Répertoire Opérationnel des Métiers et Emplois | France Travail (ROME 4.0) |

## Structure des fichiers

```
src/
├── Entity/
│   ├── NiveauQualification.php    # 8 niveaux de qualification
│   ├── TypeCertification.php      # ~25 types de certifications
│   ├── CodeNSF.php                # Structure hiérarchique NSF (4 niveaux)
│   └── CodeROME.php               # Codes métiers ROME
├── Repository/
│   ├── NiveauQualificationRepository.php
│   ├── TypeCertificationRepository.php
│   ├── CodeNSFRepository.php
│   └── CodeROMERepository.php
└── DataFixtures/
    ├── NiveauQualificationFixtures.php  # 8 enregistrements
    ├── TypeCertificationFixtures.php    # 26 enregistrements
    ├── CodeNSFFixtures.php              # ~100 enregistrements (hiérarchiques)
    └── CodeROMEFixtures.php             # ~50 codes métiers principaux

migrations/
└── data_reference_insert.sql      # Script SQL direct (alternative aux fixtures)
```

## Installation

### 1. Copier les fichiers

Copiez les dossiers `Entity/`, `Repository/` et `DataFixtures/` dans votre projet Symfony :

```bash
cp -r src/Entity/* /chemin/vers/votre/projet/src/Entity/
cp -r src/Repository/* /chemin/vers/votre/projet/src/Repository/
cp -r src/DataFixtures/* /chemin/vers/votre/projet/src/DataFixtures/
```

### 2. Installer doctrine/doctrine-fixtures-bundle (si pas déjà fait)

```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

### 3. Créer les tables (migration)

```bash
# Générer la migration
php bin/console make:migration

# Exécuter la migration
php bin/console doctrine:migrations:migrate
```

### 4. Charger les données de référence

**Option A : Via les Fixtures Doctrine (recommandé en développement)**

```bash
# Charger uniquement les tables de référence
php bin/console doctrine:fixtures:load --group=reference --append

# Ou charger tout
php bin/console doctrine:fixtures:load
```

**Option B : Via le script SQL (recommandé en production)**

```bash
mysql -u utilisateur -p nom_base < migrations/data_reference_insert.sql
```

## Utilisation dans l'entité Formation

Les entités sont conçues pour être liées à l'entité `Formation` :

```php
// src/Entity/Formation.php

#[ORM\Entity]
class Formation
{
    // Relation ManyToOne vers NiveauQualification
    #[ORM\ManyToOne(targetEntity: NiveauQualification::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NiveauQualification $niveauQualification = null;

    // Relation ManyToOne vers TypeCertification
    #[ORM\ManyToOne(targetEntity: TypeCertification::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeCertification $typeCertification = null;

    // Relation ManyToMany vers CodeNSF (une formation peut avoir plusieurs codes)
    #[ORM\ManyToMany(targetEntity: CodeNSF::class, inversedBy: 'formations')]
    #[ORM\JoinTable(name: 'formation_code_nsf')]
    private Collection $codesNsf;

    // Relation ManyToMany vers CodeROME (une formation vise plusieurs métiers)
    #[ORM\ManyToMany(targetEntity: CodeROME::class, inversedBy: 'formations')]
    #[ORM\JoinTable(name: 'formation_code_rome')]
    private Collection $codesRome;
    
    // ... getters/setters
}
```

## Exemples d'utilisation des Repositories

```php
// Dans un contrôleur ou service

// Récupérer tous les niveaux pour un formulaire
$niveaux = $niveauQualificationRepository->findAllActifs();

// Récupérer les types de certifications pour l'apprentissage
$types = $typeCertificationRepository->findEligiblesApprentissage();

// Rechercher des codes NSF pour l'informatique
$codesInfo = $codeNSFRepository->findCodesInformatique();

// Rechercher des codes ROME par mot-clé
$metiers = $codeROMERepository->search('développeur');

// Récupérer les codes ROME groupés par domaine (pour un select optgroup)
$romeGroupes = $codeROMERepository->findForSelectGroupedByDomaine();
```

## Structure des données

### Niveaux de qualification (8 niveaux)

| Code | Libellé | Équivalent |
|------|---------|------------|
| 1 | Savoirs de base | - |
| 2 | Infra CAP | - |
| 3 | CAP/BEP | Niveau V (ancien) |
| 4 | Baccalauréat | Niveau IV |
| 5 | BTS/DUT | Niveau III |
| 6 | Licence/BUT | Niveau II |
| 7 | Master/Ingénieur | Niveau I |
| 8 | Doctorat | Niveau I |

### Types de certifications (extraits)

| Code | Libellé | Certificateur |
|------|---------|---------------|
| BTS | Brevet de technicien supérieur | Ministère EN |
| TP | Titre professionnel | Ministère Travail |
| CQP | Certificat de qualification professionnelle | Branches |
| TFP | Titre à finalité professionnelle | Organismes privés |

### Codes NSF - Structure hiérarchique

```
Niveau 1 (4 postes)    → 3 = Services
  └── Niveau 2 (17)    → 32 = Communication et information
       └── Niveau 3 (93)  → 326 = Informatique, réseaux
            └── Niveau 4     → 326n = Analyse, conception réseaux
```

### Codes ROME - Informatique (exemples)

| Code | Libellé |
|------|---------|
| M1801 | Administration de systèmes d'information |
| M1802 | Expertise et support en SI |
| M1805 | Études et développement informatique |
| M1812 | Cybersécurité |
| I1401 | Maintenance informatique et bureautique |

## Notes importantes

1. **Tables de référence** : Ces tables sont préfixées `ref_` pour les identifier clairement comme tables de lookup.

2. **Soft delete** : Le champ `actif` permet de désactiver un enregistrement sans le supprimer (important pour l'intégrité des données historiques).

3. **Évolutivité** : 
   - Les niveaux de qualification sont définis par décret et changent rarement
   - Les types de certification évoluent occasionnellement
   - Les codes NSF et ROME peuvent être enrichis selon les besoins

4. **Performances** : Des index sont créés sur les colonnes `code` pour optimiser les recherches.

## Licence

Ces données sont issues de sources publiques (France Compétences, INSEE, France Travail).
