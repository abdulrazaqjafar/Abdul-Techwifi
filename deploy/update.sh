#!/usr/bin/env bash
#
# Kusasisha mfumo baada ya kubadilisha code.
#
# Nakili folda mpya ya mfumo kwenye seva (mfano /home/techsphere/upload),
# kisha endesha:
#   sudo bash /home/techsphere/upload/deploy/update.sh
#
# .env, storage na database havitaguswa.

set -euo pipefail

APP_DIR="/var/www/techsphere"
APP_USER="www-data"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo." >&2
    exit 1
fi

step() { echo -e "\n\033[1;36m==> $1\033[0m"; }

step "Kuzima queue worker kwa muda"
systemctl stop techsphere-queue || true

cd "$APP_DIR"
php artisan down --retry=15 || true

if [[ "$SOURCE_DIR" != "$APP_DIR" ]]; then
    step "Kunakili code mpya"
    rsync -a --delete \
        --exclude '.git' \
        --exclude 'node_modules' \
        --exclude 'vendor' \
        --exclude '.env' \
        --exclude 'storage' \
        --exclude 'deploy/database-dump.sql' \
        "$SOURCE_DIR"/ "$APP_DIR"/
fi

cd "$APP_DIR"

step "Dependencies na assets"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
npm ci || npm install
npm run build

step "Migrations"
php artisan migrate --force

step "Ruhusa na cache"
chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up

step "Kuwasha huduma tena"
systemctl start techsphere-queue
systemctl reload nginx

step "Imekamilika"
systemctl --no-pager --lines=0 status techsphere-queue || true
