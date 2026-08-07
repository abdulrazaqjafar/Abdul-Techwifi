#!/usr/bin/env bash
#
# Ufikiaji wa mfumo ukiwa mbali na ofisi, kupitia admin.tech-spherewifi.xyz.
#
# Seva haifikiki kutoka intaneti, na hilo ni jambo jema kwa usalama. Badala ya
# kufungua port kwenye router, cloudflared inatengeneza njia inayotoka ndani
# kwenda Cloudflare. Hakuna port inayofunguliwa popote.
#
# Endesha:
#   sudo bash deploy/enable-remote-admin.sh

set -euo pipefail

DOMAIN="tech-spherewifi.xyz"
SUBDOMAIN="admin.${DOMAIN}"
TUNNEL_NAME="techsphere"

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo." >&2
    exit 1
fi

step() { echo -e "\n\033[1;36m==> $1\033[0m"; }

step "Kusakinisha cloudflared"
if ! command -v cloudflared >/dev/null 2>&1; then
    ARCH="$(dpkg --print-architecture)"
    curl -fsSL "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-${ARCH}.deb" \
        -o /tmp/cloudflared.deb
    dpkg -i /tmp/cloudflared.deb
    rm -f /tmp/cloudflared.deb
fi

step "Kuingia kwenye Cloudflare"
echo "Kiungo kitaonekana hapa chini. Kifungue kwenye browser, kisha chagua"
echo "${DOMAIN} ili kuruhusu seva hii."
echo

if [[ ! -f /root/.cloudflared/cert.pem ]]; then
    cloudflared tunnel login
fi

step "Kutengeneza tunnel"
if ! cloudflared tunnel list | grep -q "$TUNNEL_NAME"; then
    cloudflared tunnel create "$TUNNEL_NAME"
fi

TUNNEL_ID="$(cloudflared tunnel list | awk -v n="$TUNNEL_NAME" '$2==n {print $1}')"

if [[ -z "$TUNNEL_ID" ]]; then
    echo "Imeshindwa kupata tunnel ID." >&2
    exit 1
fi

step "Kuunganisha jina na tunnel"
cloudflared tunnel route dns "$TUNNEL_NAME" "$SUBDOMAIN" || true

step "Kuandika config"
mkdir -p /etc/cloudflared

cat > /etc/cloudflared/config.yml <<CONFIG
tunnel: ${TUNNEL_ID}
credentials-file: /root/.cloudflared/${TUNNEL_ID}.json

ingress:
  - hostname: ${SUBDOMAIN}
    service: http://localhost:80
    originRequest:
      httpHostHeader: ${DOMAIN}
  - service: http_status:404
CONFIG

step "Kuwasha huduma"
cloudflared service install || true
systemctl enable --now cloudflared
systemctl restart cloudflared

step "Imekamilika"
cat <<INFO

  Mfumo sasa unapatikana popote ulipo:
    https://${SUBDOMAIN}

  HATUA YA USALAMA — IFANYE SASA:
  Bila kizuizi, mlango huu uko wazi kwa dunia nzima. Kwenye Cloudflare nenda:

    Zero Trust -> Access -> Applications -> Add an application
      Type      : Self-hosted
      Subdomain : admin
      Domain    : ${DOMAIN}
      Policy    : Allow -> Emails -> weka barua pepe yako

  Baada ya hapo, kila anayefungua ${SUBDOMAIN} atatakiwa kuthibitisha
  barua pepe kwanza kabla hata hajaona ukurasa wa kuingia wa mfumo.

  Kuangalia hali:
    systemctl status cloudflared
    cloudflared tunnel info ${TUNNEL_NAME}

INFO
