#!/usr/bin/env bash
#
# HTTPS yenye cheti halisi cha Let's Encrypt kwa tech-spherewifi.xyz.
#
# Seva iko nyuma ya NAT na haifikiki kutoka intaneti, hivyo uthibitisho wa
# kawaida (HTTP-01) hauwezi kufanya kazi. Badala yake tunatumia DNS-01:
# certbot inaandika rekodi ya muda kwenye Cloudflare, Let's Encrypt inaisoma,
# na cheti kinatolewa bila kufungua seva kwa dunia.
#
# Kabla ya kuendesha, tengeneza API token ya Cloudflare:
#   Cloudflare -> My Profile -> API Tokens -> Create Token
#   Template: "Edit zone DNS", Zone Resources: tech-spherewifi.xyz
#
# Endesha:
#   sudo bash deploy/enable-https.sh

set -euo pipefail

DOMAIN="tech-spherewifi.xyz"
EMAIL=""
CREDENTIALS="/root/.cloudflare.ini"
APP_DIR="/var/www/techsphere"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo." >&2
    exit 1
fi

step() { echo -e "\n\033[1;36m==> $1\033[0m"; }

step "Kusakinisha certbot na plugin ya Cloudflare"
apt-get update -y
apt-get install -y certbot python3-certbot-dns-cloudflare

if [[ ! -f "$CREDENTIALS" ]]; then
    step "Cloudflare API token"
    read -rsp "Bandika API token yako ya Cloudflare: " CF_TOKEN
    echo

    printf 'dns_cloudflare_api_token = %s\n' "$CF_TOKEN" > "$CREDENTIALS"
    chmod 600 "$CREDENTIALS"
fi

if [[ -z "$EMAIL" ]]; then
    read -rp "Barua pepe yako (kwa taarifa za cheti): " EMAIL
fi

step "Kuomba cheti kwa njia ya DNS"
certbot certonly \
    --dns-cloudflare \
    --dns-cloudflare-credentials "$CREDENTIALS" \
    --dns-cloudflare-propagation-seconds 30 \
    -d "$DOMAIN" \
    -d "www.$DOMAIN" \
    --agree-tos \
    --non-interactive \
    --email "$EMAIL" \
    --deploy-hook "systemctl reload nginx"

step "Kusanidi Nginx kwa HTTPS"
cp "$APP_DIR/deploy/nginx-techsphere-ssl.conf" /etc/nginx/sites-available/techsphere
sed -i "s|PHP_VERSION_PLACEHOLDER|8.3|g" /etc/nginx/sites-available/techsphere
nginx -t
systemctl reload nginx

step "Kufungua port 443"
if command -v ufw >/dev/null 2>&1; then
    ufw allow 443/tcp || true
fi

step "Kuhakikisha upyaishaji wa kiotomatiki"
systemctl enable --now certbot.timer || true
certbot renew --dry-run

step "Kubadilisha APP_URL"
sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" "$APP_DIR/.env"
cd "$APP_DIR"
sudo -u www-data php artisan config:cache

step "Imekamilika"
cat <<INFO

  Sasa mfumo unapatikana:
    https://${DOMAIN}
    http://${DOMAIN}        (bado inafanya kazi kwa ajili ya captive portal)
    http://192.168.20.253   (kwa dharura)

  Cheti kitajipyaisha chenyewe kila baada ya siku 60.

  HATUA YA MWISHO: badilisha login.html ya router itumie https, kisha:
    php scripts/upload-login-page.php

INFO
