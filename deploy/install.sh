#!/usr/bin/env bash
#
# Tech-Sphere Wi-Fi Billing — usakinishaji kwenye Ubuntu Server 24.04
#
# Endesha ukiwa ndani ya folda ya mfumo:
#   sudo bash deploy/install.sh
#
# Script hii inaweza kuendeshwa mara nyingi bila kuharibu kitu.

set -euo pipefail

APP_DIR="/var/www/techsphere"
APP_USER="www-data"
DB_NAME="techsphere_wifi"
DB_USER="techsphere"
PHP_VERSION="8.3"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo: sudo bash deploy/install.sh" >&2
    exit 1
fi

step() { echo -e "\n\033[1;36m==> $1\033[0m"; }

step "Kupakua orodha ya packages"
apt-get update -y

step "Kusakinisha Nginx, MariaDB, PHP $PHP_VERSION na zana nyingine"
apt-get install -y \
    nginx \
    mariadb-server \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-sockets \
    unzip curl git rsync ca-certificates

step "Kusakinisha Node.js 20"
if ! command -v node >/dev/null 2>&1 || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 18 ]]; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

step "Kusakinisha Composer"
if ! command -v composer >/dev/null 2>&1; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

step "Kunakili mfumo kwenda $APP_DIR"
mkdir -p "$APP_DIR"

if [[ "$SOURCE_DIR" != "$APP_DIR" ]]; then
    rsync -a --delete \
        --exclude '.git' \
        --exclude 'node_modules' \
        --exclude 'vendor' \
        --exclude '.env' \
        --exclude 'storage/logs/*' \
        "$SOURCE_DIR"/ "$APP_DIR"/
fi

cd "$APP_DIR"

step "Kuandaa database"
DB_PASSWORD_FILE="/root/.techsphere-db-password"

if [[ -f "$DB_PASSWORD_FILE" ]]; then
    DB_PASSWORD="$(cat "$DB_PASSWORD_FILE")"
else
    DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
    printf '%s' "$DB_PASSWORD" > "$DB_PASSWORD_FILE"
    chmod 600 "$DB_PASSWORD_FILE"
fi

systemctl enable --now mariadb

mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

DUMP_FILE="$APP_DIR/deploy/database-dump.sql"

if [[ -f "$DUMP_FILE" ]]; then
    TABLE_COUNT="$(mariadb -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")"

    if [[ "$TABLE_COUNT" -eq 0 ]]; then
        step "Kuingiza data kutoka database-dump.sql"
        mariadb "${DB_NAME}" < "$DUMP_FILE"
    else
        echo "Database tayari ina majedwali $TABLE_COUNT — dump imerukwa."
        echo "Ukitaka kuingiza upya: mariadb ${DB_NAME} < $DUMP_FILE"
    fi
fi

step "Kuandaa .env"
ENV_TEMPLATE="$APP_DIR/deploy/env.production.example"
if [[ "${DEPLOY_TARGET:-local}" == "oracle" ]]; then
    ENV_TEMPLATE="$APP_DIR/deploy/env.oracle.example"
fi

if [[ ! -f "$APP_DIR/.env" ]]; then
    cp "$ENV_TEMPLATE" "$APP_DIR/.env"
    echo "ONYO: .env mpya imetengenezwa. Weka credentials za ClickPesa ndani yake."
fi

sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" "$APP_DIR/.env"
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" "$APP_DIR/.env"
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" "$APP_DIR/.env"

# Oracle / cloud: APP_URL inawekwa kutoka APP_PUBLIC_URL (IP ya umma).
if [[ -n "${APP_PUBLIC_URL:-}" ]]; then
    sed -i "s|^APP_URL=.*|APP_URL=${APP_PUBLIC_URL}|" "$APP_DIR/.env"
    echo "APP_URL imewekwa: ${APP_PUBLIC_URL}"
fi

step "Kusakinisha dependencies za PHP"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# APP_KEY lazima iwe ileile ya PC ya zamani. router_settings.router_password
# imehifadhiwa kwa encryption inayoitegemea; key mpya ingeifanya isisomeke na
# mfumo usingeweza kuunganishwa na MikroTik tena.
if ! grep -q '^APP_KEY=base64:' "$APP_DIR/.env"; then
    if [[ -s "$APP_DIR/deploy/app-key.txt" ]]; then
        EXISTING_KEY="$(tr -d '[:space:]' < "$APP_DIR/deploy/app-key.txt")"
        sed -i "s|^APP_KEY=.*|APP_KEY=${EXISTING_KEY}|" "$APP_DIR/.env"
        echo "APP_KEY ya zamani imetumika."
    else
        php artisan key:generate --force
        echo "ONYO: APP_KEY mpya imetengenezwa kwa sababu deploy/app-key.txt haipo."
        echo "      Itabidi uweke upya password ya router kwenye mfumo."
    fi
fi

step "Kujenga assets (CSS/JS)"
npm ci || npm install
npm run build

step "Kuendesha migrations"
php artisan migrate --force

step "Kuboresha kasi (cache za Laravel)"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ruhusa zinawekwa BAADA ya cache, kwa sababu amri za hapo juu zinaandika
# mafaili ndani ya bootstrap/cache na storage zikiwa na umiliki wa root.
step "Kuweka ruhusa za mafaili"
chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

step "Kusanidi Nginx"
cp "$APP_DIR/deploy/nginx-techsphere.conf" /etc/nginx/sites-available/techsphere
sed -i "s|PHP_VERSION_PLACEHOLDER|${PHP_VERSION}|g" /etc/nginx/sites-available/techsphere
ln -sf /etc/nginx/sites-available/techsphere /etc/nginx/sites-enabled/techsphere
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

step "Kusanidi queue worker"
cp "$APP_DIR/deploy/techsphere-queue.service" /etc/systemd/system/techsphere-queue.service
systemctl daemon-reload
systemctl enable --now techsphere-queue
systemctl restart techsphere-queue

step "Kusanidi scheduler (cron)"
CRON_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
CRON_CURRENT="$(crontab -u "$APP_USER" -l 2>/dev/null || true)"

if ! printf '%s' "$CRON_CURRENT" | grep -qF "schedule:run"; then
    printf '%s\n%s\n' "$CRON_CURRENT" "$CRON_LINE" | sed '/^$/d' | crontab -u "$APP_USER" -
fi

step "Kufungua firewall"
if command -v ufw >/dev/null 2>&1; then
    ufw allow 22/tcp || true
    ufw allow 80/tcp || true
    ufw allow 443/tcp || true
    ufw allow 8000/tcp || true
    if [[ "${DEPLOY_TARGET:-local}" == "oracle" ]]; then
        ufw allow 1194/udp || true
        ufw allow 1194/tcp || true
    fi
fi

systemctl enable --now php${PHP_VERSION}-fpm nginx

step "Imekamilika"
IP_ADDRESS="$(hostname -I | awk '{print $1}')"
PUBLIC_HINT="${APP_PUBLIC_URL:-http://${IP_ADDRESS}}"
cat <<INFO

  Mfumo unapatikana hapa:
    ${PUBLIC_HINT}
    http://${IP_ADDRESS}:8000

  Database:
    jina=${DB_NAME}  mtumiaji=${DB_USER}
    password imehifadhiwa: ${DB_PASSWORD_FILE}

  Kuangalia huduma:
    systemctl status techsphere-queue
    tail -f ${APP_DIR}/storage/logs/laravel.log

INFO

if [[ "${DEPLOY_TARGET:-local}" == "oracle" ]]; then
    cat <<ORACLE

  Hatua za Oracle zilizobaki:
    1. sudo bash deploy/openvpn/setup-server.sh
    2. Pakia certs kwenye MikroTik + import oracle-openvpn-client.rsc
    3. php scripts/apply-oracle-router-ip.php
    4. php scripts/configure-oracle-portal.php <PUBLIC_IP>
    5. Soma deploy/ORACLE.md sehemu ya ukaguzi

ORACLE
fi
