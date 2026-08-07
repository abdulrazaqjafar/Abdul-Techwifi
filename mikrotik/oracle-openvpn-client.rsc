# ============================================================================
#  OpenVPN client: MikroTik → Oracle Cloud
#  Badilisha PUBLIC_IP hapa chini kabla ya /import
#
#  Kabla ya hii:
#    1. Pakia ca.crt, mikrotik.crt, mikrotik.key (au mikrotik.p12) kwenye Files
#    2. Import certificates (angalia deploy/ORACLE.md)
# ============================================================================

:local oracleIp "PUBLIC_IP"

# API — ruhusu LAN ya zamani + subnet ya VPN
/ip service
set api disabled=no port=8728 address=192.168.20.0/24,10.8.0.0/24

# Ondoa client wa zamani kama upo
/interface ovpn-client
remove [find name="ovpn-oracle"]

# Cheti lazima kiwe kimeimportwa kwa jina "mikrotik" (common-name)
/interface ovpn-client
add name=ovpn-oracle connect-to=$oracleIp port=1194 mode=ip protocol=tcp \
    user=mikrotik password="" certificate=mikrotik auth=sha1 cipher=aes256 \
    add-default-route=no disabled=no comment="Tech-Sphere Oracle VPN"

# Thibitisha baada ya sekunde chache:
#   /interface ovpn-client print
#   /ip address print where interface~"ovpn"
# MikroTik inapaswa kuwa na 10.8.0.2
