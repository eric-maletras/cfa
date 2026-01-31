# Module d'Appel avec Signature par Email - CFA Gestion
## Étape 9

### Description

Ce module permet aux formateurs de gérer les présences des apprentis avec un système de signature par email unique.

**Fonctionnalités principales :**
- Création d'appels pour les séances planifiées
- Envoi automatique de liens de signature par email
- Signature des présences sans authentification (via token unique)
- Suivi temps réel des signatures
- Gestion des absences, retards et justificatifs
- Traitement automatique des appels expirés (cron)

---

## Structure des fichiers

```
cfa-module-appel/
├── src/
│   ├── Command/
│   │   └── TraiterAppelsExpiresCommand.php    # Commande cron
│   ├── Controller/
│   │   ├── AppelController.php                # Gestion appels (formateur)
│   │   └── SignatureController.php            # Signature publique
│   ├── DataFixtures/
│   │   └── AppelFixtures.php                  # Données de test
│   ├── Entity/
│   │   ├── Appel.php                          # Entité Appel
│   │   └── Presence.php                       # Entité Présence
│   ├── Enum/
│   │   └── StatutPresence.php                 # Enum des statuts
│   ├── Repository/
│   │   ├── AppelRepository.php
│   │   └── PresenceRepository.php
│   └── Service/
│       └── AppelService.php                   # Logique métier
├── templates/
│   ├── appel/
│   │   ├── seance.html.twig                   # Sélection des présents
│   │   └── suivi.html.twig                    # Suivi temps réel
│   ├── email/
│   │   └── signature_presence.html.twig       # Template email
│   └── signature/
│       ├── confirmer.html.twig                # Page confirmation
│       ├── succes.html.twig                   # Signature réussie
│       ├── erreur.html.twig                   # Page erreur
│       └── deja_signe.html.twig               # Déjà signé
├── migrations/
│   ├── Version20260131_AppelModule.php        # Migration Doctrine
│   └── migration_appel.sql                    # Script SQL brut
└── README.md                                  # Ce fichier
```

---

## Instructions de déploiement

### 1. Copie des fichiers

```bash
# Sur le serveur de production
cd /var/www/cfa.ericm.fr

# Copier les fichiers PHP
cp -r cfa-module-appel/src/Entity/* src/Entity/
cp -r cfa-module-appel/src/Enum/* src/Enum/
cp -r cfa-module-appel/src/Repository/* src/Repository/
cp -r cfa-module-appel/src/Service/* src/Service/
cp -r cfa-module-appel/src/Controller/* src/Controller/
cp -r cfa-module-appel/src/Command/* src/Command/
cp -r cfa-module-appel/src/DataFixtures/* src/DataFixtures/

# Copier les templates
cp -r cfa-module-appel/templates/appel templates/
cp -r cfa-module-appel/templates/signature templates/
cp -r cfa-module-appel/templates/email/signature_presence.html.twig templates/email/
```

### 2. Migration de base de données

**Option A : Via Doctrine (recommandé)**
```bash
# Générer la migration automatiquement
php bin/console doctrine:migrations:diff

# Ou copier la migration existante
cp cfa-module-appel/migrations/Version20260131_AppelModule.php migrations/

# Exécuter la migration
php bin/console doctrine:migrations:migrate
```

**Option B : Script SQL direct**
```bash
mysql -u root -p cfa_gestion < cfa-module-appel/migrations/migration_appel.sql
```

### 3. Vider le cache

```bash
php bin/console cache:clear --env=prod
```

### 4. Configuration du cron (optionnel mais recommandé)

Ajouter au crontab (`crontab -e`) :

```bash
# Traitement automatique des appels expirés - toutes les 15 minutes
*/15 * * * * cd /var/www/cfa.ericm.fr && php bin/console app:appel:traiter-expires --env=prod >> /var/log/cfa-appels.log 2>&1
```

### 5. Test de la commande cron

```bash
# Mode dry-run (sans modification)
php bin/console app:appel:traiter-expires --dry-run

# Exécution réelle
php bin/console app:appel:traiter-expires
```

### 6. Charger les fixtures de test (optionnel)

```bash
# En développement uniquement
php bin/console doctrine:fixtures:load --append --group=appel
```

---

## Modification du template existant

Modifier le fichier `templates/formateur/planning/seance.html.twig` pour activer le bouton "Faire l'appel" :

**Avant :**
```twig
<a href="#" class="action-button action-button--disabled" title="Module absences en développement">
    <span class="action-button__icon">📋</span>
    <div class="action-button__text">
        <strong>Faire l'appel</strong>
        <div class="action-button__desc">Gérer les présences/absences</div>
    </div>
</a>
```

**Après :**
```twig
<a href="{{ path('app_appel_seance', {id: seance.id}) }}" class="action-button action-button--primary">
    <span class="action-button__icon">📋</span>
    <div class="action-button__text">
        <strong>Faire l'appel</strong>
        <div class="action-button__desc">Gérer les présences/absences</div>
    </div>
</a>
```

---

## Routes créées

| Route | Méthode | URL | Description |
|-------|---------|-----|-------------|
| `app_appel_seance` | GET | `/module/formateur_planning/appel/seance/{id}` | Page sélection présents |
| `app_appel_creer` | POST | `/module/formateur_planning/appel/creer/{id}` | Créer un appel |
| `app_appel_suivi` | GET | `/module/formateur_planning/appel/suivi/{id}` | Suivi temps réel |
| `app_appel_envoyer_emails` | POST | `/module/formateur_planning/appel/envoyer-emails/{id}` | Envoyer emails |
| `app_appel_renvoyer_email` | POST | `/module/formateur_planning/appel/renvoyer-email/{id}` | Renvoyer un email |
| `app_appel_modifier_presence` | POST | `/module/formateur_planning/appel/modifier-presence/{id}` | Modifier statut |
| `app_appel_cloturer` | POST | `/module/formateur_planning/appel/cloturer/{id}` | Clôturer appel |
| `app_appel_etat` | GET | `/module/formateur_planning/appel/etat/{id}` | État JSON (AJAX) |
| `app_appel_supprimer` | POST | `/module/formateur_planning/appel/supprimer/{id}` | Supprimer appel |
| `app_signature_signer` | GET/POST | `/signature/{token}` | Signature publique |

---

## Workflow utilisateur

### Côté Formateur

1. Accéder à une séance : `/module/formateur_planning/seance/{id}`
2. Cliquer sur "Faire l'appel"
3. Cocher les apprentis présents physiquement
4. Configurer le délai d'expiration (1-12h)
5. Créer l'appel
6. Envoyer les emails de signature
7. Suivre les signatures en temps réel (refresh automatique 5s)
8. Modifier les statuts si nécessaire (retard, absence justifiée...)
9. Clôturer l'appel

### Côté Apprenti

1. Recevoir l'email de signature
2. Cliquer sur le lien unique
3. Voir les détails du cours
4. Confirmer sa présence
5. Recevoir la confirmation

---

## Statuts de présence

| Statut | Description | Couleur |
|--------|-------------|---------|
| `en_attente` | Lien envoyé, en attente de signature | Orange |
| `present` | Présent et a signé | Vert |
| `absent` | Marqué absent par le formateur | Rouge |
| `absent_justifie` | Absent avec justification | Bleu |
| `retard` | Arrivé en retard | Orange foncé |
| `non_signe` | N'a pas signé dans le délai | Gris |

---

## Sécurité

- **Tokens UUID v4** : 64 caractères hexadécimaux uniques par présence
- **Protection CSRF** : Sur tous les formulaires
- **Vérification accès formateur** : Le formateur doit être assigné à la séance/session
- **Signature publique** : Le token fait office d'authentification
- **Traçabilité** : IP + User-Agent enregistrés à la signature
- **Protection double signature** : Vérification avant chaque signature
- **Expiration automatique** : Les liens ont une durée de validité limitée

---

## Dépendances

Ce module utilise les services existants :
- `EmailService` : Envoi des emails (configuré étape 8b)
- `SeancePlanifiee` : Séances du planning
- `Session` : Sessions de formation
- `User` : Utilisateurs (formateurs et apprentis)
- `Inscription` : Inscriptions validées

---

## Troubleshooting

### Les emails ne sont pas envoyés
- Vérifier la configuration MAILER_DSN dans `.env`
- Consulter les logs : `tail -f var/log/prod.log`
- Tester l'envoi : `php bin/console app:email:test test@example.com`

### Le cron ne fonctionne pas
- Vérifier le crontab : `crontab -l`
- Tester manuellement : `php bin/console app:appel:traiter-expires`
- Consulter les logs : `tail -f /var/log/cfa-appels.log`

### Erreur 500 sur les pages
- Vider le cache : `php bin/console cache:clear --env=prod`
- Vérifier les permissions : `chown -R www-data:www-data var/`
- Consulter les logs Symfony et Nginx

---

## Support

Pour toute question ou problème, consulter :
- La documentation Symfony : https://symfony.com/doc
- Le référentiel GitHub du projet
- Les logs applicatifs dans `var/log/`
