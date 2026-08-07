# ============================================================================
#  Captive portal → Oracle Cloud
#  Badilisha PUBLIC_IP kabla ya /import
#
#  Hii inaongeza walled garden + DNS static kwa IP ya umma ya Oracle.
#  Vocha zilizopo routerni haziguswi.
# ============================================================================

:local oracleIp "PUBLIC_IP"
:local domain "tech-spherewifi.xyz"

# Walled garden — ruhusu portal ya cloud kabla ya login
/ip hotspot walled-garden ip
:if ([:len [find dst-address=$oracleIp]] = 0) do={
    add dst-address=$oracleIp action=accept comment="Tech-Sphere Oracle billing"
}

/ip hotspot walled-garden
:if ([:len [find dst-host=$oracleIp]] = 0) do={
    add dst-host=$oracleIp action=allow comment="Tech-Sphere Oracle HTTP"
}
:if ([:len [find dst-host=$domain]] = 0) do={
    add dst-host=$domain action=allow comment="Tech-Sphere portal domain"
}
:if ([:len [find dst-host=("www." . $domain)]] = 0) do={
    add dst-host=("www." . $domain) action=allow comment="Tech-Sphere portal domain"
}

# DNS ya ndani — jina la biashara linaelekeza kwenye Oracle
/ip dns static
remove [find comment="Tech-Sphere portal"]
add name=$domain address=$oracleIp ttl=1d comment="Tech-Sphere portal"
add name=("www." . $domain) address=$oracleIp ttl=1d comment="Tech-Sphere portal"

# API bado inaruhusu LAN + VPN (usifungue 8728 kwa internet)
/ip service
set api disabled=no port=8728 address=192.168.20.0/24,10.8.0.0/24

# PC ya zamani (.253) inaweza kubaki bypassed kwa dharura, si seva kuu tena.
# Usiondoe ip-binding ya .253 hadi umethibitisha Oracle inafanya kazi.

:put ("Portal cutover: DNS + walled garden → " . $oracleIp)
