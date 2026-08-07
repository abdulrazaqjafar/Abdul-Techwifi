#!/usr/bin/env bash
#
# Usakinishaji wa Tech-Sphere kwenye Oracle Cloud Always Free (Ubuntu 24.04).
# Hakuna netplan ya LAN — seva inaishi kwenye intaneti ya umma.
#
# Matumizi:
#   export APP_PUBLIC_URL=http://132.145.x.x
#   sudo -E bash deploy/install-oracle.sh
#
# Au:
#   sudo APP_PUBLIC_URL=http://132.145.x.x bash deploy/install-oracle.sh
#
# Kisha endesha OpenVPN: sudo bash deploy/openvpn/setup-server.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo: sudo -E bash deploy/install-oracle.sh" >&2
    exit 1
fi

if [[ -z "${APP_PUBLIC_URL:-}" ]]; then
    # Jaribu kupata IP ya umma kutoka metadata ya Oracle (OCI).
    META_IP="$(curl -fsS --connect-timeout 2 \
        -H 'Authorization: Bearer Oracle' \
        http://169.254.169.254/opc/v2/vnics/ 2>/dev/null \
        | sed -n 's/.*"publicIp"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' \
        | head -n1 || true)"

    if [[ -z "$META_IP" ]]; then
        META_IP="$(curl -fsS --connect-timeout 3 https://ifconfig.me 2>/dev/null || true)"
    fi

    if [[ -n "$META_IP" ]]; then
        APP_PUBLIC_URL="http://${META_IP}"
        echo "APP_PUBLIC_URL imegunduliwa: ${APP_PUBLIC_URL}"
    else
        echo "ONYO: APP_PUBLIC_URL haijawekwa. Weka kabla ya kusakinisha:" >&2
        echo "  export APP_PUBLIC_URL=http://PUBLIC_IP_YAKO" >&2
        echo "  sudo -E bash deploy/install-oracle.sh" >&2
        exit 1
    fi
fi

export DEPLOY_TARGET=oracle
export APP_PUBLIC_URL

echo "Deploy target : oracle"
echo "APP_PUBLIC_URL: ${APP_PUBLIC_URL}"
echo

exec bash "${SCRIPT_DIR}/install.sh"
