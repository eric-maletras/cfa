#!/bin/bash
# =============================================================================
# Installation Postfix + OpenDKIM pour CFA Gestion
# Serveur: Ubuntu Server 24.04
# Domaine: cfa.ericm.fr (configurable)
# =============================================================================

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=============================================="
echo "Installation Postfix + OpenDKIM"
echo "Configuration DKIM/SPF/DMARC"
echo "==============================================${NC}"

# Vérification root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Erreur: Ce script doit être exécuté en tant que root${NC}"
    echo "Usage: sudo ./install_postfix_dkim.sh [domaine]"
    exit 1
fi

# Configuration du domaine
DOMAIN="${1:-cfa.ericm.fr}"
MAIL_DOMAIN=$(echo "$DOMAIN" | sed 's/^[^.]*\.//')  # ericm.fr
HOSTNAME=$(hostname -f)
SELECTOR="mail"  # Sélecteur DKIM (mail._domainkey.cfa.ericm.fr)

echo ""
echo -e "${YELLOW}Configuration:${NC}"
echo "  Domaine email    : $DOMAIN"
echo "  Domaine parent   : $MAIL_DOMAIN"
echo "  Hostname serveur : $HOSTNAME"
echo "  Sélecteur DKIM   : $SELECTOR"
echo ""

read -p "Continuer avec cette configuration ? (O/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Nn]$ ]]; then
    exit 0
fi

# =============================================================================
# ÉTAPE 1 : Installation des paquets
# =============================================================================
echo ""
echo -e "${GREEN}[1/7] Installation des paquets...${NC}"
DEBIAN_FRONTEND=noninteractive apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    postfix \
    opendkim \
    opendkim-tools \
    mailutils \
    libsasl2-modules

# =============================================================================
# ÉTAPE 2 : Configuration Postfix
# =============================================================================
echo -e "${GREEN}[2/7] Configuration de Postfix...${NC}"

# Sauvegarde
[ -f /etc/postfix/main.cf ] && cp /etc/postfix/main.cf /etc/postfix/main.cf.backup.$(date +%Y%m%d_%H%M%S)

cat > /etc/postfix/main.cf << EOF
# =============================================================================
# Configuration Postfix pour CFA Gestion
# Domaine: $DOMAIN
# Généré le $(date)
# =============================================================================

# Identité du serveur
smtpd_banner = \$myhostname ESMTP
biff = no
append_dot_mydomain = no
readme_directory = no

# Nom et domaine
myhostname = $DOMAIN
mydomain = $MAIL_DOMAIN
myorigin = $DOMAIN
mydestination = \$myhostname, localhost.\$mydomain, localhost
relayhost =

# Réseaux autorisés (local uniquement)
mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128
inet_interfaces = loopback-only
inet_protocols = ipv4

# Alias
alias_maps = hash:/etc/aliases
alias_database = hash:/etc/aliases

# Limites
mailbox_size_limit = 0
recipient_delimiter = +
message_size_limit = 10240000

# Sécurité
smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination

# TLS sortant (pour les serveurs qui le supportent)
smtp_tls_security_level = may
smtp_tls_CApath = /etc/ssl/certs
smtp_tls_loglevel = 1

# =============================================================================
# Intégration OpenDKIM
# =============================================================================
milter_protocol = 6
milter_default_action = accept
smtpd_milters = inet:localhost:8891
non_smtpd_milters = inet:localhost:8891

# =============================================================================
# Headers et logging
# =============================================================================
smtp_header_checks = regexp:/etc/postfix/header_checks
EOF

# Header checks
cat > /etc/postfix/header_checks << 'EOF'
# Nettoyage des headers sensibles
/^Received:.*127\.0\.0\.1/    IGNORE
/^X-Originating-IP:/          IGNORE
/^X-Mailer:/                  IGNORE
EOF

# =============================================================================
# ÉTAPE 3 : Création des clés DKIM
# =============================================================================
echo -e "${GREEN}[3/7] Génération des clés DKIM...${NC}"

# Création des répertoires
mkdir -p /etc/opendkim/keys/$DOMAIN
chown -R opendkim:opendkim /etc/opendkim
chmod 700 /etc/opendkim/keys

# Génération de la paire de clés (2048 bits)
cd /etc/opendkim/keys/$DOMAIN
opendkim-genkey -b 2048 -d $DOMAIN -s $SELECTOR -v

# Permissions
chown opendkim:opendkim ${SELECTOR}.private ${SELECTOR}.txt
chmod 600 ${SELECTOR}.private

echo -e "${GREEN}  ✓ Clé privée: /etc/opendkim/keys/$DOMAIN/${SELECTOR}.private${NC}"
echo -e "${GREEN}  ✓ Clé publique: /etc/opendkim/keys/$DOMAIN/${SELECTOR}.txt${NC}"

# =============================================================================
# ÉTAPE 4 : Configuration OpenDKIM
# =============================================================================
echo -e "${GREEN}[4/7] Configuration d'OpenDKIM...${NC}"

cat > /etc/opendkim.conf << EOF
# =============================================================================
# Configuration OpenDKIM pour CFA Gestion
# =============================================================================

# Logging
Syslog                  yes
SyslogSuccess           yes
LogWhy                  yes

# Permissions
UserID                  opendkim:opendkim
UMask                   007

# Socket pour Postfix
Socket                  inet:8891@localhost

# Mode : signer (s) et vérifier (v)
Mode                    sv

# Canonicalisation (relaxed pour plus de compatibilité)
Canonicalization        relaxed/relaxed

# Algorithme de signature
SignatureAlgorithm      rsa-sha256

# Tables de configuration
KeyTable                /etc/opendkim/key.table
SigningTable            refile:/etc/opendkim/signing.table
InternalHosts           /etc/opendkim/trusted.hosts
ExternalIgnoreList      /etc/opendkim/trusted.hosts

# Options
OversignHeaders         From
AutoRestart             yes
AutoRestartRate         10/1M
Background              yes
DNSTimeout              5
SignatureAlgorithm      rsa-sha256
EOF

# Table des clés
cat > /etc/opendkim/key.table << EOF
${SELECTOR}._domainkey.${DOMAIN} ${DOMAIN}:${SELECTOR}:/etc/opendkim/keys/${DOMAIN}/${SELECTOR}.private
EOF

# Table de signature (quels domaines signer)
cat > /etc/opendkim/signing.table << EOF
*@${DOMAIN} ${SELECTOR}._domainkey.${DOMAIN}
EOF

# Hôtes de confiance (pas de vérification pour eux)
cat > /etc/opendkim/trusted.hosts << EOF
127.0.0.1
localhost
${DOMAIN}
EOF

# Permissions
chown -R opendkim:opendkim /etc/opendkim
chmod 644 /etc/opendkim.conf

# =============================================================================
# ÉTAPE 5 : Configuration des alias
# =============================================================================
echo -e "${GREEN}[5/7] Configuration des alias...${NC}"

# Ajouter noreply si absent
if ! grep -q "^noreply:" /etc/aliases; then
    echo "noreply: /dev/null" >> /etc/aliases
fi
newaliases

# =============================================================================
# ÉTAPE 6 : Démarrage des services
# =============================================================================
echo -e "${GREEN}[6/7] Démarrage des services...${NC}"

# OpenDKIM
systemctl enable opendkim
systemctl restart opendkim

# Postfix
systemctl enable postfix
systemctl restart postfix

# Vérification
sleep 2
if systemctl is-active --quiet opendkim && systemctl is-active --quiet postfix; then
    echo -e "${GREEN}  ✓ OpenDKIM actif${NC}"
    echo -e "${GREEN}  ✓ Postfix actif${NC}"
else
    echo -e "${RED}  ✗ Erreur de démarrage${NC}"
    systemctl status opendkim postfix
    exit 1
fi

# =============================================================================
# ÉTAPE 7 : Génération des enregistrements DNS
# =============================================================================
echo -e "${GREEN}[7/7] Génération des enregistrements DNS...${NC}"

# Récupérer l'IP publique
PUBLIC_IP=$(curl -s ifconfig.me 2>/dev/null || echo "VOTRE_IP_PUBLIQUE")

# Créer le fichier des enregistrements DNS
DNS_FILE="/root/dns_records_${DOMAIN}.txt"

cat > $DNS_FILE << EOF
================================================================================
ENREGISTREMENTS DNS À CRÉER POUR : $DOMAIN
================================================================================
Créez ces enregistrements dans votre zone DNS (OVH, Cloudflare, etc.)
================================================================================

1. ENREGISTREMENT SPF (TXT)
--------------------------------------------------------------------------------
Nom     : $DOMAIN.
Type    : TXT
Valeur  : "v=spf1 ip4:${PUBLIC_IP} -all"

Explication : Autorise uniquement l'IP $PUBLIC_IP à envoyer des emails pour $DOMAIN


2. ENREGISTREMENT DKIM (TXT)
--------------------------------------------------------------------------------
Nom     : ${SELECTOR}._domainkey.${DOMAIN}.
Type    : TXT
Valeur  : $(cat /etc/opendkim/keys/$DOMAIN/${SELECTOR}.txt | grep -o '".*"' | tr -d '\n' | sed 's/" "//g')

Note : Si la valeur est trop longue, divisez-la en plusieurs chaînes de 255 caractères.


3. ENREGISTREMENT DMARC (TXT)
--------------------------------------------------------------------------------
Nom     : _dmarc.${DOMAIN}.
Type    : TXT
Valeur  : "v=DMARC1; p=quarantine; rua=mailto:postmaster@${MAIL_DOMAIN}; pct=100; adkim=s; aspf=s"

Politique DMARC :
  - p=quarantine : Les emails non conformes vont en spam
  - p=reject     : Les emails non conformes sont rejetés (plus strict, activer plus tard)
  - rua=         : Adresse pour recevoir les rapports DMARC


4. ENREGISTREMENT MX (optionnel, si vous recevez des emails)
--------------------------------------------------------------------------------
Nom     : ${DOMAIN}.
Type    : MX
Priorité: 10
Valeur  : ${DOMAIN}.


5. ENREGISTREMENT PTR (Reverse DNS) - À CONFIGURER CHEZ SCALEWAY
--------------------------------------------------------------------------------
IP      : ${PUBLIC_IP}
PTR     : ${DOMAIN}

Note : Le reverse DNS se configure dans le panneau Scaleway, pas dans votre zone DNS.
       C'est CRUCIAL pour la délivrabilité !


================================================================================
VÉRIFICATION
================================================================================
Après avoir créé les enregistrements, attendez la propagation DNS (jusqu'à 24h)
puis vérifiez avec :

# Vérifier SPF
dig TXT ${DOMAIN} +short

# Vérifier DKIM  
dig TXT ${SELECTOR}._domainkey.${DOMAIN} +short

# Vérifier DMARC
dig TXT _dmarc.${DOMAIN} +short

# Test complet (envoyez un email à cette adresse)
# https://www.mail-tester.com/

================================================================================
EOF

echo ""
echo -e "${BLUE}=============================================="
echo "INSTALLATION TERMINÉE"
echo "==============================================${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT : Enregistrements DNS à créer${NC}"
echo ""
echo "Le fichier contenant les enregistrements DNS a été créé :"
echo -e "${GREEN}  $DNS_FILE${NC}"
echo ""
echo "Affichez-le avec : cat $DNS_FILE"
echo ""

# Afficher un résumé des enregistrements
echo -e "${YELLOW}Résumé des enregistrements DNS :${NC}"
echo ""
echo -e "${BLUE}1. SPF${NC} (TXT sur $DOMAIN)"
echo "   v=spf1 ip4:${PUBLIC_IP} -all"
echo ""
echo -e "${BLUE}2. DKIM${NC} (TXT sur ${SELECTOR}._domainkey.${DOMAIN})"
echo "   $(cat /etc/opendkim/keys/$DOMAIN/${SELECTOR}.txt | grep -o '".*"' | head -1)..."
echo ""
echo -e "${BLUE}3. DMARC${NC} (TXT sur _dmarc.${DOMAIN})"
echo "   v=DMARC1; p=quarantine; rua=mailto:postmaster@${MAIL_DOMAIN}; pct=100; adkim=s; aspf=s"
echo ""

# Test optionnel
echo -e "${YELLOW}=============================================="
echo "TEST D'ENVOI"
echo "==============================================${NC}"
read -p "Envoyer un email de test maintenant ? (o/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Oo]$ ]]; then
    read -p "Adresse email de destination : " TEST_EMAIL
    
    echo "Envoi du test..."
    echo "Test d'envoi CFA Gestion - $(date)

Cet email a été envoyé depuis le serveur $HOSTNAME
Configuration : Postfix + OpenDKIM
Domaine : $DOMAIN

Si vous recevez cet email :
1. Vérifiez qu'il n'est pas dans les spams
2. Consultez les headers pour voir la signature DKIM
3. Testez sur https://www.mail-tester.com/ pour un diagnostic complet
" | mail -s "Test CFA Gestion - Postfix + DKIM" -r "noreply@$DOMAIN" "$TEST_EMAIL"
    
    echo ""
    echo -e "${GREEN}Email envoyé à $TEST_EMAIL${NC}"
    echo "Vérifiez votre boîte de réception (et les spams)."
    echo ""
    echo "Pour voir les logs : tail -f /var/log/mail.log"
fi

echo ""
echo -e "${YELLOW}Commandes utiles :${NC}"
echo "  Logs mail     : tail -f /var/log/mail.log"
echo "  Queue         : mailq"
echo "  Test DKIM     : opendkim-testkey -d $DOMAIN -s $SELECTOR -vvv"
echo "  Statut        : systemctl status postfix opendkim"
echo ""
