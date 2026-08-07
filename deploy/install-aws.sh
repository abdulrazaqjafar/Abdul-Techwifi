#!/usr/bin/env bash
#
# Usakinishaji wa Tech-Sphere kwenye AWS EC2 Free Tier (Ubuntu 24.04).
#
# Matumizi:
#   sudo APP_PUBLIC_URL=http://18.203.x.x bash deploy/install-aws.sh
#
# Kisha: sudo bash deploy/openvpn/setup-server.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo: sudo -E bash deploy/install-aws.sh" >&2
    exit 1
fi

if [[ -z "${APP_PUBLIC_URL:-}" ]]; then
    # EC2 instance metadata (IMDSv2)
    TOKEN="$(curl -fsS --connect-timeout 2 -X PUT \
        'http://169.254.169.254/latest/api/token' \
        -H 'X-aws-ec2-metadata-token-ttl-seconds: 60' 2>/dev/null || true)"

    META_IP=""
    if [[ -n "$TOKEN" ]]; then
        META_IP="$(curl -fsS --connect-timeout 2 \
            -H "X-aws-ec2-metadata-token: ${TOKEN}" \
            http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || true)"
    fi

    if [[ -z "$META_IP" ]]; then
        META_IP="$(curl -fsS --connect-timeout 3 https://ifconfig.me 2>/dev/null || true)"
    fi

    if [[ -n "$META_IP" ]]; then
        APP_PUBLIC_URL="http://${META_IP}"
        echo "APP_PUBLIC_URL imegunduliwa: ${APP_PUBLIC_URL}"
    else
        echo "ONYO: APP_PUBLIC_URL haijawekwa." >&2
        echo "  export APP_PUBLIC_URL=http://PUBLIC_IP_YAKO" >&2
        echo "  sudo -E bash deploy/install-aws.sh" >&2
        exit 1
    fi
fi

export DEPLOY_TARGET=aws
export APP_PUBLIC_URL

echo "Deploy target : aws"
echo "APP_PUBLIC_URL: ${APP_PUBLIC_URL}"
echo

exec bash "${SCRIPT_DIR}/install.sh"
