#!/bin/bash
# =============================================================================
# Diagnostic de la configuration email
# Vérifie Postfix, OpenDKIM, et les enregistrements DNS
# =============================================================================

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DOMAIN="${1:-cfa.ericm.fr}"
SELECTOR="mail"

echo -e "${BLUE}=============================================="
echo "Diagnostic Email - $DOMAIN"
echo "==============================================${NC}"
echo ""

ERRORS=0
WARNINGS=0

# Fonction de test
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}  ✓ $1${NC}"
        return 0
    else
        echo -e "${RED}  ✗ $1${NC}"
        ((ERRORS++))
        return 1
    fi
}

warn() {
    echo -e "${YELLOW}  ⚠ $1${NC}"
    ((WARNINGS++))
}

# =============================================================================
# 1. Services
# =============================================================================
echo -e "${YELLOW}[1/5] Vérification des services${NC}"

systemctl is-active --quiet postfix
check "Postfix actif"

systemctl is-active --quiet opendkim
check "OpenDKIM actif"

# Port 8891 (OpenDKIM)
netstat -tlnp 2>/dev/null | grep -q ":8891" || ss -tlnp | grep -q ":8891"
check "OpenDKIM écoute sur port 8891"

# =============================================================================
# 2. Configuration Postfix
# =============================================================================
echo ""
echo -e "${YELLOW}[2/5] Configuration Postfix${NC}"

grep -q "smtpd_milters.*8891" /etc/postfix/main.cf
check "Postfix configuré pour utiliser OpenDKIM"

grep -q "myhostname.*$DOMAIN" /etc/postfix/main.cf 2>/dev/null || \
grep -q "myhostname" /etc/postfix/main.cf
check "myhostname configuré"

# =============================================================================
# 3. Configuration OpenDKIM
# =============================================================================
echo ""
echo -e "${YELLOW}[3/5] Configuration OpenDKIM${NC}"

[ -f "/etc/opendkim/keys/$DOMAIN/${SELECTOR}.private" ]
check "Clé privée DKIM existe"

[ -f "/etc/opendkim/keys/$DOMAIN/${SELECTOR}.txt" ]
check "Clé publique DKIM existe"

[ -f "/etc/opendkim/key.table" ]
check "key.table existe"

grep -q "$DOMAIN" /etc/opendkim/signing.table 2>/dev/null
check "signing.table configuré pour $DOMAIN"

# Test de la clé DKIM
echo ""
echo -e "${BLUE}  Test de la clé DKIM...${NC}"
opendkim-testkey -d $DOMAIN -s $SELECTOR -vvv 2>&1 | head -5

# =============================================================================
# 4. Enregistrements DNS
# =============================================================================
echo ""
echo -e "${YELLOW}[4/5] Vérification DNS (peut échouer si pas encore propagé)${NC}"

# SPF
SPF=$(dig TXT $DOMAIN +short 2>/dev/null | grep "v=spf1")
if [ -n "$SPF" ]; then
    echo -e "${GREEN}  ✓ SPF trouvé: $SPF${NC}"
else
    warn "SPF non trouvé - À créer dans la zone DNS"
fi

# DKIM
DKIM=$(dig TXT ${SELECTOR}._domainkey.${DOMAIN} +short 2>/dev/null)
if [ -n "$DKIM" ]; then
    echo -e "${GREEN}  ✓ DKIM trouvé${NC}"
else
    warn "DKIM non trouvé - À créer dans la zone DNS"
fi

# DMARC
DMARC=$(dig TXT _dmarc.${DOMAIN} +short 2>/dev/null | grep "v=DMARC1")
if [ -n "$DMARC" ]; then
    echo -e "${GREEN}  ✓ DMARC trouvé: $DMARC${NC}"
else
    warn "DMARC non trouvé - À créer dans la zone DNS"
fi

# Reverse DNS
PUBLIC_IP=$(curl -s ifconfig.me 2>/dev/null)
if [ -n "$PUBLIC_IP" ]; then
    PTR=$(dig -x $PUBLIC_IP +short 2>/dev/null)
    if [ -n "$PTR" ]; then
        echo -e "${GREEN}  ✓ Reverse DNS: $PTR${NC}"
        if [[ "$PTR" == *"$DOMAIN"* ]]; then
            echo -e "${GREEN}    → Correspond au domaine ✓${NC}"
        else
            warn "Reverse DNS ne correspond pas à $DOMAIN"
        fi
    else
        warn "Reverse DNS non configuré pour $PUBLIC_IP"
    fi
fi

# =============================================================================
# 5. Test d'envoi
# =============================================================================
echo ""
echo -e "${YELLOW}[5/5] Test d'envoi (optionnel)${NC}"

read -p "  Envoyer un email de test ? (o/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Oo]$ ]]; then
    read -p "  Adresse de destination : " TEST_EMAIL
    
    # Envoi
    echo "Test diagnostic - $(date)" | mail -s "Test Diagnostic CFA" -r "noreply@$DOMAIN" "$TEST_EMAIL" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}  ✓ Email mis en queue${NC}"
        echo ""
        echo "  Vérifiez :"
        echo "  1. Votre boîte de réception (et spams)"
        echo "  2. Les logs : tail /var/log/mail.log"
        echo "  3. La queue : mailq"
        echo ""
        echo -e "${BLUE}  Pour un test complet, envoyez un email à :${NC}"
        echo "  → check-auth@verifier.port25.com (rapport automatique)"
        echo "  → https://www.mail-tester.com/ (score sur 10)"
    else
        echo -e "${RED}  ✗ Erreur lors de l'envoi${NC}"
    fi
fi

# =============================================================================
# Résumé
# =============================================================================
echo ""
echo -e "${BLUE}=============================================="
echo "RÉSUMÉ"
echo "==============================================${NC}"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}Tout est OK ! ✓${NC}"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}$WARNINGS avertissement(s) - Vérifiez la configuration DNS${NC}"
else
    echo -e "${RED}$ERRORS erreur(s) et $WARNINGS avertissement(s)${NC}"
    echo "Vérifiez les logs : journalctl -u postfix -u opendkim"
fi

echo ""

# Afficher la clé DKIM à copier si demandé
read -p "Afficher la clé DKIM à copier dans le DNS ? (o/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo ""
    echo -e "${YELLOW}Enregistrement DKIM à créer :${NC}"
    echo ""
    echo "Nom  : ${SELECTOR}._domainkey.${DOMAIN}"
    echo "Type : TXT"
    echo "Valeur :"
    cat /etc/opendkim/keys/$DOMAIN/${SELECTOR}.txt
    echo ""
fi
