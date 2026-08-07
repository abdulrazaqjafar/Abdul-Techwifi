#!/usr/bin/env bash
#
# Ukaguzi baada ya deploy ya Oracle — endesha kwenye VM:
#   sudo bash deploy/verify-oracle.sh
#

set -euo pipefail

APP_DIR="/var/www/techsphere"
PASS=0
FAIL=0

ok()   { echo "  SAWA   $1"; PASS=$((PASS + 1)); }
bad()  { echo "  HAPANA $1"; FAIL=$((FAIL + 1)); }
info() { echo "  --     $1"; }

echo "=== Ukaguzi wa Tech-Sphere (Oracle) ==="
echo

if [[ -f "$APP_DIR/.env" ]]; then
    ok ".env ipo"
    APP_URL="$(grep -E '^APP_URL=' "$APP_DIR/.env" | head -1 | cut -d= -f2-)"
    info "APP_URL=${APP_URL}"
else
    bad ".env haipo"
fi

if systemctl is-active --quiet nginx; then ok "nginx"; else bad "nginx"; fi
if systemctl is-active --quiet php*-fpm 2>/dev/null || systemctl is-active --quiet php8.3-fpm; then
    ok "php-fpm"
else
    bad "php-fpm"
fi
if systemctl is-active --quiet techsphere-queue; then ok "queue worker"; else bad "queue worker"; fi
if systemctl is-active --quiet mariadb || systemctl is-active --quiet mysql; then
    ok "mariadb"
else
    bad "mariadb"
fi

if systemctl is-active --quiet openvpn@server 2>/dev/null \
    || systemctl is-active --quiet openvpn-server@server 2>/dev/null \
    || ip link show tun0 >/dev/null 2>&1; then
    ok "OpenVPN / tun0"
else
    bad "OpenVPN (endesha deploy/openvpn/setup-server.sh)"
fi

if curl -fsS -o /dev/null -w "%{http_code}" --connect-timeout 5 http://127.0.0.1/ | grep -qE '200|302'; then
    ok "HTTP localhost"
else
    bad "HTTP localhost"
fi

if [[ -d "$APP_DIR" ]]; then
    echo
    echo "=== Muunganisho wa MikroTik (VPN) ==="
    if sudo -u www-data php "$APP_DIR/scripts/apply-oracle-router-ip.php" 2>/dev/null | tee /tmp/mik-test.txt | grep -q 'SAWA'; then
        ok "API via 10.8.0.2"
    else
        bad "API via VPN — angalia OVPN client kwenye MikroTik"
        cat /tmp/mik-test.txt 2>/dev/null || true
    fi
fi

CRON="$(crontab -u www-data -l 2>/dev/null || true)"
if printf '%s' "$CRON" | grep -q 'schedule:run'; then
    ok "scheduler (cron)"
else
    bad "scheduler (cron)"
fi

echo
echo "=== Checklist ya mkono (simu) ==="
info "1. Unganisha WiFi Abdul-Tech"
info "2. Sign in → ukurasa wa vifurushi (APP_URL)"
info "3. Nunua kifurushi au weka vocha → intaneti inakuja"
info "4. Ingia dashibodi: ${APP_URL:-http://PUBLIC_IP}/login"
info "5. PC: simamisha php artisan serve, zima laptop"

echo
echo "Matokeo: SAWA=${PASS}  HAPANA=${FAIL}"
if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi

echo
echo "Ukaguzi wa seva umepita. Malizia jaribio la simu kisha uzime PC."
