#!/usr/bin/env bash
#
# OpenVPN server kwa Oracle Cloud — MikroTik inaunganisha kama client (outbound).
# Subnet: 10.8.0.0/24   server=10.8.0.1   mikrotik=10.8.0.2
#
# Endesha kwenye VM ya Oracle:
#   sudo bash deploy/openvpn/setup-server.sh
#
# Baada ya hapo:
#   - Nakili /etc/openvpn/mikrotik/ kwenda PC yako
#   - Import certificates kwenye MikroTik
#   - Endesha mikrotik/oracle-openvpn-client.rsc (badilisha PUBLIC_IP)

set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Endesha kwa sudo." >&2
    exit 1
fi

VPN_DIR="/etc/openvpn"
EASY_DIR="/etc/openvpn/easy-rsa"
CLIENT_DIR="/etc/openvpn/mikrotik"
VPN_NET="10.8.0.0"
VPN_MASK="255.255.255.0"
SERVER_VPN_IP="10.8.0.1"
CLIENT_VPN_IP="10.8.0.2"
PORT="1194"
PROTO="tcp"   # MikroTik OpenVPN client inapendelea TCP

PUBLIC_IP="${PUBLIC_IP:-}"
if [[ -z "$PUBLIC_IP" ]]; then
    PUBLIC_IP="$(curl -fsS --connect-timeout 3 https://ifconfig.me 2>/dev/null || true)"
fi
if [[ -z "$PUBLIC_IP" ]]; then
    PUBLIC_IP="$(hostname -I | awk '{print $1}')"
fi

step() { echo -e "\n\033[1;36m==> $1\033[0m"; }

step "Kusakinisha OpenVPN na Easy-RSA"
apt-get update -y
apt-get install -y openvpn easy-rsa iptables

step "Kuandaa CA na cheti"
if [[ ! -d "$EASY_DIR" ]]; then
    if command -v make-cadir >/dev/null 2>&1; then
        make-cadir "$EASY_DIR"
    else
        mkdir -p "$EASY_DIR"
        cp -a /usr/share/easy-rsa/* "$EASY_DIR"/
    fi
fi

cd "$EASY_DIR"

if [[ ! -x ./easyrsa && -f /usr/share/easy-rsa/easyrsa ]]; then
    cp /usr/share/easy-rsa/easyrsa .
    chmod +x ./easyrsa
fi

# easy-rsa 3 vars
# RSA: RouterOS 6 OpenVPN client inasaidia RSA vizuri zaidi kuliko EC.
cat > vars <<'EOF'
set_var EASYRSA_REQ_COUNTRY    "TZ"
set_var EASYRSA_REQ_PROVINCE   "Dar"
set_var EASYRSA_REQ_CITY       "Dar"
set_var EASYRSA_REQ_ORG        "TechSphere"
set_var EASYRSA_REQ_EMAIL      "admin@tech-spherewifi.xyz"
set_var EASYRSA_REQ_OU         "WiFi"
set_var EASYRSA_ALGO           "rsa"
set_var EASYRSA_KEY_SIZE       2048
set_var EASYRSA_DIGEST         "sha256"
set_var EASYRSA_BATCH          "1"
EOF

if [[ ! -f pki/ca.crt ]]; then
    ./easyrsa init-pki
    ./easyrsa build-ca nopass
fi

if [[ ! -f pki/issued/server.crt ]]; then
    ./easyrsa build-server-full server nopass
fi

if [[ ! -f pki/issued/mikrotik.crt ]]; then
    ./easyrsa build-client-full mikrotik nopass
fi

if [[ ! -f pki/dh.pem ]]; then
    ./easyrsa gen-dh
fi

# tls-auth haijawekwa: MikroTik RouterOS 6 OpenVPN client inasaidia
# cheti vizuri zaidi bila tls-auth/tls-crypt.

step "Kuandika server.conf"
mkdir -p "$VPN_DIR/ccd" "$CLIENT_DIR"

# Fixed VPN IP for MikroTik
echo "ifconfig-push ${CLIENT_VPN_IP} 255.255.255.0" > "$VPN_DIR/ccd/mikrotik"

cp pki/ca.crt "$VPN_DIR/ca.crt"
cp pki/issued/server.crt "$VPN_DIR/server.crt"
cp pki/private/server.key "$VPN_DIR/server.key"
cp pki/dh.pem "$VPN_DIR/dh.pem"
chmod 600 "$VPN_DIR/server.key"

cat > "$VPN_DIR/server.conf" <<EOF
port ${PORT}
proto ${PROTO}
dev tun
topology subnet
server ${VPN_NET} ${VPN_MASK}
ifconfig-pool-persist /var/log/openvpn/ipp.txt
client-config-dir ccd
keepalive 10 60
persist-key
persist-tun
cipher AES-256-CBC
auth SHA1
user nobody
group nogroup
status /var/log/openvpn/status.log
log-append /var/log/openvpn/openvpn.log
verb 3
explicit-exit-notify 0
ca ca.crt
cert server.crt
key server.key
dh dh.pem
# Verify client certificate (MikroTik presents mikrotik.crt)
verify-client-cert require
push "route 10.8.0.0 255.255.255.0"
EOF

mkdir -p /var/log/openvpn

step "IP forwarding na NAT (VPN → internet si lazima; API inabaki ndani)"
if ! grep -q '^net.ipv4.ip_forward=1' /etc/sysctl.conf 2>/dev/null; then
    echo 'net.ipv4.ip_forward=1' >> /etc/sysctl.conf
fi
sysctl -w net.ipv4.ip_forward=1 >/dev/null

step "Firewall"
if command -v ufw >/dev/null 2>&1; then
    ufw allow "${PORT}/${PROTO}" || true
fi

# Allow API from this host to MikroTik VPN IP (local)
iptables -C INPUT -i tun0 -j ACCEPT 2>/dev/null || iptables -A INPUT -i tun0 -j ACCEPT
iptables -C FORWARD -i tun0 -j ACCEPT 2>/dev/null || iptables -A FORWARD -i tun0 -j ACCEPT
iptables -C FORWARD -o tun0 -j ACCEPT 2>/dev/null || iptables -A FORWARD -o tun0 -j ACCEPT

step "Kuanza OpenVPN"
systemctl enable openvpn@server 2>/dev/null || systemctl enable openvpn-server@server 2>/dev/null || true

# Ubuntu 24 may use openvpn@server when conf is /etc/openvpn/server.conf
if systemctl list-unit-files | grep -q 'openvpn@'; then
    systemctl restart openvpn@server || systemctl start openvpn@server
elif systemctl list-unit-files | grep -q 'openvpn-server@'; then
    # Move to server/server.conf layout if needed
    mkdir -p /etc/openvpn/server
    cp -n "$VPN_DIR/server.conf" /etc/openvpn/server/server.conf 2>/dev/null || \
        cp "$VPN_DIR/server.conf" /etc/openvpn/server/server.conf
    cp -n "$VPN_DIR/ca.crt" /etc/openvpn/server/ 2>/dev/null || true
    cp -n "$VPN_DIR/server.crt" /etc/openvpn/server/ 2>/dev/null || true
    cp -n "$VPN_DIR/server.key" /etc/openvpn/server/ 2>/dev/null || true
    cp -n "$VPN_DIR/dh.pem" /etc/openvpn/server/ 2>/dev/null || true
    cp -n "$VPN_DIR/ta.key" /etc/openvpn/server/ 2>/dev/null || true
    mkdir -p /etc/openvpn/server/ccd
    cp "$VPN_DIR/ccd/mikrotik" /etc/openvpn/server/ccd/
    systemctl restart openvpn-server@server
else
    openvpn --config "$VPN_DIR/server.conf" --daemon
fi

step "Kuandaa faili za MikroTik"
# MikroTik imports PEM/CRT; also provide PKCS12 for convenience
openssl pkcs12 -export \
    -inkey pki/private/mikrotik.key \
    -in pki/issued/mikrotik.crt \
    -certfile pki/ca.crt \
    -out "$CLIENT_DIR/mikrotik.p12" \
    -passout pass:techsphere

cp pki/ca.crt "$CLIENT_DIR/ca.crt"
cp pki/issued/mikrotik.crt "$CLIENT_DIR/mikrotik.crt"
cp pki/private/mikrotik.key "$CLIENT_DIR/mikrotik.key"

# Client ovpn (reference; MikroTik uses certificates + rsc)
cat > "$CLIENT_DIR/mikrotik.ovpn" <<EOF
client
dev tun
proto ${PROTO}
remote ${PUBLIC_IP} ${PORT}
resolv-retry infinite
nobind
persist-key
persist-tun
remote-cert-tls server
cipher AES-256-CBC
auth SHA1
verb 3
<ca>
$(cat pki/ca.crt)
</ca>
<cert>
$(cat pki/issued/mikrotik.crt)
</cert>
<key>
$(cat pki/private/mikrotik.key)
</key>
EOF

# Fill PUBLIC_IP into a ready .rsc snippet
cat > "$CLIENT_DIR/README.txt" <<EOF
OpenVPN kwa MikroTik — Tech-Sphere
===================================
Oracle public IP : ${PUBLIC_IP}
Server VPN IP    : ${SERVER_VPN_IP}
MikroTik VPN IP  : ${CLIENT_VPN_IP}
Port / proto     : ${PORT}/${PROTO}

Hatua:
1. Nakili folda hii (/etc/openvpn/mikrotik) kwenda PC yako (scp).
2. Winbox → Files: pakia ca.crt, mikrotik.crt, mikrotik.key (au mikrotik.p12).
3. /certificate import file-name=ca.crt passphrase=""
   /certificate import file-name=mikrotik.crt passphrase=""
   /certificate import file-name=mikrotik.key passphrase=""
   (au import mikrotik.p12 passphrase=techsphere)
4. Badilisha PUBLIC_IP katika mikrotik/oracle-openvpn-client.rsc kisha /import
5. Thibitisha: /interface ovpn-client print
   Laravel inapaswa kufikia router kwa 10.8.0.2:8728

Password ya mikrotik.p12: techsphere
EOF

# Also copy into the app tree if present
APP_COPY="/var/www/techsphere/deploy/openvpn/client-files"
if [[ -d /var/www/techsphere/deploy ]]; then
    mkdir -p "$APP_COPY"
    cp -a "$CLIENT_DIR"/. "$APP_COPY"/
    echo "${PUBLIC_IP}" > "$APP_COPY/PUBLIC_IP.txt"
fi

step "Imekamilika"
echo
echo "  OpenVPN: ${PUBLIC_IP}:${PORT}/${PROTO}"
echo "  Faili za client: ${CLIENT_DIR}"
echo "  Nakili: scp -r ubuntu@${PUBLIC_IP}:${CLIENT_DIR} ."
echo
ip -brief addr show tun0 2>/dev/null || echo "  (tun0 itaonekana MikroTik ikishaunganisha)"
