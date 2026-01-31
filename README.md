# Module Email CFA Gestion - Configuration Production

## Vue d'ensemble

Configuration complète pour l'envoi d'emails avec **authentification DKIM/SPF/DMARC** garantissant une délivrabilité optimale.

```
Architecture
─────────────────────────────────────────────────────────
  Symfony App ──► Postfix ──► OpenDKIM ──► Internet
                    │            │
                    │            └── Signature DKIM
                    └── Envoi SMTP
─────────────────────────────────────────────────────────
```

## Contenu du package

```
cfa.ericm.fr/
├── config/
│   ├── packages/
│   │   └── mailer.yaml
│   └── services_email.yaml.append
├── scripts/
│   ├── install_postfix_dkim.sh      # Installation serveur
│   └── diagnostic_email.sh          # Test et diagnostic
├── src/
│   ├── Controller/Admin/
│   │   └── EmailTestController.php
│   └── Service/
│       └── EmailService.php
├── templates/
│   ├── admin/email/
│   │   └── test.html.twig
│   └── email/
│       ├── base.html.twig
│       ├── test.html.twig
│       └── system_notification.html.twig
├── env.local.append
└── README.md
```

---

## Installation (3 phases)

### Phase 1 : Installation serveur (Postfix + OpenDKIM)

```bash
# 1. Copier le script sur le serveur
scp scripts/install_postfix_dkim.sh root@cfa.ericm.fr:/tmp/

# 2. Exécuter l'installation
ssh root@cfa.ericm.fr
chmod +x /tmp/install_postfix_dkim.sh
/tmp/install_postfix_dkim.sh cfa.ericm.fr
```

Le script va :
- Installer Postfix et OpenDKIM
- Générer les clés DKIM (2048 bits)
- Configurer la signature automatique des emails
- Générer un fichier avec les enregistrements DNS à créer

### Phase 2 : Configuration DNS

Après l'installation, le script génère `/root/dns_records_cfa.ericm.fr.txt` avec les enregistrements à créer.

#### Chez votre registrar (OVH, Cloudflare, etc.) :

**1. Enregistrement SPF** (autorise votre serveur à envoyer)
```
Type  : TXT
Nom   : cfa.ericm.fr
Valeur: v=spf1 ip4:VOTRE_IP_SERVEUR -all
```

**2. Enregistrement DKIM** (signature cryptographique)
```
Type  : TXT
Nom   : mail._domainkey.cfa.ericm.fr
Valeur: v=DKIM1; k=rsa; p=VOTRE_CLE_PUBLIQUE...
```
*(La clé complète est dans le fichier généré)*

**3. Enregistrement DMARC** (politique d'authentification)
```
Type  : TXT
Nom   : _dmarc.cfa.ericm.fr
Valeur: v=DMARC1; p=quarantine; rua=mailto:postmaster@ericm.fr; pct=100; adkim=s; aspf=s
```

**4. Reverse DNS (PTR)** - **CRUCIAL !**

À configurer dans le **panneau Scaleway** (pas chez le registrar DNS) :
- Aller dans Instances > Votre serveur > Réseau
- Configurer le reverse DNS de l'IP vers `cfa.ericm.fr`

#### Vérification de la propagation DNS

```bash
# Attendre 1-24h puis vérifier
dig TXT cfa.ericm.fr +short
dig TXT mail._domainkey.cfa.ericm.fr +short
dig TXT _dmarc.cfa.ericm.fr +short
```

### Phase 3 : Déploiement Symfony

```bash
# Sur le serveur, dans le répertoire Symfony
cd /var/www/cfa.ericm.fr

# 1. Copier les fichiers
cp -r cfa.ericm.fr/src/Service/EmailService.php src/Service/
cp -r cfa.ericm.fr/src/Controller/Admin/EmailTestController.php src/Controller/Admin/
cp -r cfa.ericm.fr/templates/email templates/
mkdir -p templates/admin/email
cp cfa.ericm.fr/templates/admin/email/test.html.twig templates/admin/email/
cp cfa.ericm.fr/config/packages/mailer.yaml config/packages/

# 2. Ajouter au .env.local
cat >> .env.local << 'EOF'
###> symfony/mailer ###
MAILER_DSN=sendmail://default
MAILER_FROM_ADDRESS=noreply@cfa.ericm.fr
MAILER_FROM_NAME="CFA Gestion"
###< symfony/mailer ###
EOF

# 3. Ajouter dans config/services.yaml (section services)
# App\Service\EmailService:
#     arguments:
#         $fromAddress: '%env(MAILER_FROM_ADDRESS)%'
#         $fromName: '%env(MAILER_FROM_NAME)%'

# 4. Vider le cache
php bin/console cache:clear
```

---

## Test et validation

### 1. Diagnostic serveur

```bash
# Lancer le diagnostic complet
/tmp/diagnostic_email.sh cfa.ericm.fr
```

### 2. Test via l'interface admin

Accéder à : `https://cfa.ericm.fr/admin/email/test`

### 3. Test externe (recommandé)

Envoyez un email à : **check-auth@verifier.port25.com**

Vous recevrez un rapport automatique indiquant :
- ✅ SPF pass
- ✅ DKIM pass
- ✅ DMARC pass

### 4. Score de délivrabilité

Testez sur [mail-tester.com](https://www.mail-tester.com/) pour obtenir un score sur 10.

**Objectif : 9/10 minimum**

---

## Dépannage

### Email non reçu

```bash
# Vérifier la queue
mailq

# Vérifier les logs
tail -f /var/log/mail.log

# Vérifier le statut des services
systemctl status postfix opendkim
```

### Erreur DKIM

```bash
# Tester la clé DKIM
opendkim-testkey -d cfa.ericm.fr -s mail -vvv
```

Erreurs courantes :
- `key not found` → L'enregistrement DNS n'est pas encore propagé
- `key not secure` → Pas grave, signifie que DNSSEC n'est pas utilisé

### Email en spam

Vérifiez :
1. **Reverse DNS** configuré chez Scaleway
2. **SPF** avec `-all` (pas `~all`)
3. **IP non blacklistée** : [mxtoolbox.com/blacklists](https://mxtoolbox.com/blacklists.aspx)

---

## Utilisation dans le code

### Injection du service

```php
use App\Service\EmailService;

class MonController extends AbstractController
{
    public function __construct(
        private EmailService $emailService
    ) {}
}
```

### Envoi avec template

```php
$result = $this->emailService->sendTemplatedEmail(
    'destinataire@example.com',
    'Sujet de l\'email',
    'email/mon_template.html.twig',
    [
        'variable1' => 'valeur1',
        'variable2' => 'valeur2',
    ]
);

if ($result->success) {
    // OK
} else {
    // Erreur : $result->message
}
```

### Envoi en masse (avec délai)

```php
$recipients = ['email1@test.com', 'email2@test.com', ...];

$results = $this->emailService->sendBulkEmail(
    $recipients,
    'Notification',
    'email/notification.html.twig',
    ['data' => $data]
);

// Analyse des résultats
$failed = array_filter($results, fn($r) => !$r->success);
```

---

## Configuration multi-domaines (version commerciale)

Pour la version avec plusieurs écoles, le script supporte le multi-domaine :

```bash
# Installation pour chaque école
./install_postfix_dkim.sh isce.cfagestion.fr
./install_postfix_dkim.sh aurlom.cfagestion.fr
```

Les fichiers de configuration OpenDKIM (`signing.table`, `key.table`) seront à fusionner manuellement.

---

## Checklist de mise en production

- [ ] Script `install_postfix_dkim.sh` exécuté
- [ ] Enregistrement **SPF** créé
- [ ] Enregistrement **DKIM** créé
- [ ] Enregistrement **DMARC** créé
- [ ] **Reverse DNS** configuré chez Scaleway
- [ ] Test sur mail-tester.com : score ≥ 9/10
- [ ] Fichiers Symfony déployés
- [ ] Test depuis `/admin/email/test` OK

---

## Support

### Commandes utiles

```bash
# Logs temps réel
tail -f /var/log/mail.log

# Queue des emails
mailq

# Vider la queue (si bloquée)
postsuper -d ALL

# Relancer les services
systemctl restart postfix opendkim

# Recharger la config Postfix
postfix reload
```

### Liens utiles

- [MXToolbox](https://mxtoolbox.com/) - Diagnostic DNS/Blacklist
- [Mail-tester](https://www.mail-tester.com/) - Score délivrabilité
- [DMARC Analyzer](https://www.dmarcanalyzer.com/) - Rapports DMARC
