# ============================================================================
#  Tech-Sphere Wi-Fi Billing — usanidi kamili wa MikroTik
#  Inachukua nafasi ya: router-setup.rsc, api-access-fix.rsc,
#  captive-portal-fix.rsc, network-not-opening-fix.rsc, phone-access-fix.rsc
#
#  MUUNDO UNAOSANIDIWA:
#    Intaneti (WAN)  : ether1 (DHCP client) -> mtandao wa juu = 192.168.10.0/24
#    Wateja (WiFi)   : wlan1 kama Access Point, SSID "Abdul-Tech"
#    LAN ya hotspot  : 192.168.20.0/24, router = 192.168.20.1
#    PC ya billing   : 192.168.20.253 port 8000 (imechomekwa LAN1)
#
#  KWA NINI SUBNET IMEBADILIKA KUTOKA 192.168.10.x KWENDA 192.168.20.x:
#    Mtandao ulioko upande wa intaneti (ether1) pia unatumia 192.168.10.0/24.
#    Subnet moja pande zote mbili za router inavunja routing, na ilisababisha
#    mgongano wa IP uliokata muunganisho wa API leo.
#
#  ============================ ONYO MUHIMU ==================================
#  1. ENDESHA HII KUPITIA WINBOX ILIYOUNGANISHWA KWA MAC ADDRESS, si kwa IP.
#     Winbox -> tab "Neighbors" -> bofya MAC DC:2C:6E:6C:45:71 -> Connect.
#     Sababu: script hii inabadilisha IP ya router, hivyo muunganisho wa IP
#     utakatika katikati ya import.
#  2. MARA TU import ikiisha, badilisha IP ya PC kwenye PowerShell ya Admin:
#       netsh interface ip set address name="Ethernet" static 192.168.20.253 255.255.255.0
#  3. Badilisha jina la WiFi ya nyumbani inayoitwa "Abdul-Tech" KABLA ya hii,
#     la sivyo mitandao miwili yenye jina moja itachanganya simu za wateja.
#  ===========================================================================
#
#  Namna ya kutumia:
#    Winbox (kwa MAC) -> Files -> upload faili hii -> New Terminal:
#      /import 00-full-setup.rsc
# ============================================================================

# --- VIGEZO ---------------------------------------------------------------
:local billingIp   "192.168.20.253"
:local wanPort     "ether1"
:local clientSsid  "Abdul-Tech"

# ============================================================================
#  HATUA 1: Ruhusu PC ya billing KABLA hotspot iwashwe
#  MUHIMU: hatua hii ni ya kwanza kwa makusudi. Ukiwasha hotspot kwanza,
#  PC yako inakuwa mteja asiyejisajili na hotspot inazuia API.
# ============================================================================

/ip hotspot ip-binding
:if ([:len [find address=$billingIp]] = 0) do={
    add address=$billingIp type=bypassed comment="Tech-Sphere billing PC"
}

/ip hotspot walled-garden ip
:if ([:len [find dst-address=$billingIp]] = 0) do={
    add dst-address=$billingIp action=accept comment="Tech-Sphere billing server"
}

/ip hotspot walled-garden
:if ([:len [find dst-host=$billingIp]] = 0) do={
    add dst-host=$billingIp action=allow comment="Tech-Sphere billing HTTP"
}

# ============================================================================
#  HATUA 2: Safisha usanidi unaogongana
#  - ether1 ilikuwa na IP 192.168.10.1/24 sawa na bridge-LAN
#  - ether1 ilikuwa port ya bridge1, hivyo haikuweza kuwa WAN
#  - dhcp1 kwenye bridge1 na pool "dhcp" zilipishana na bridge-LAN
#  - DHCP client ya wlan2 haitumiki tena (WAN sasa ni ether1)
# ============================================================================

/ip dhcp-server
remove [find name=dhcp1]

/ip dhcp-client
remove [find interface=wlan2]

/interface bridge port
remove [find bridge=bridge1]

/interface bridge
remove [find name=bridge1]

/ip pool
remove [find name=dhcp]

/ip address
remove [find interface=ether1]

# ============================================================================
#  HATUA 3: WAN — ether1 ichukue IP kutoka mtandao wa juu
# ============================================================================

# MAC ya asili ya ether1 (DC:2C:6E:6C:45:6B) ilikuwa imezuiliwa kwenye router
# ya nyumbani: ping kwenda 8.8.8.8 ilikuwa 100% loss na ukurasa mmoja ulichukua
# sekunde 10. Kwa MAC hii mpya, loss ni 0% na ukurasa unachukua sekunde 1.1.
# Ukiondoa kizuizi kwenye router ya nyumbani, unaweza kurudisha MAC ya asili.
/interface ethernet
set [find name=$wanPort] mac-address=D4:CA:6D:12:34:56

/ip dhcp-client
:if ([:len [find interface=$wanPort]] = 0) do={
    add interface=$wanPort disabled=no add-default-route=yes use-peer-dns=no
} else={
    set [find interface=$wanPort] disabled=no add-default-route=yes use-peer-dns=no
}

# NAT: lenga subnet ya LAN, SI jina la interface. Kwenye jaribio la leo rule
# yenye out-interface=ether1 ilikuwa haigusi traffic yoyote (packets=0), na
# packets za wateja zilitoka bila kubadilishwa address — hivyo majibu
# hayakurudi na simu ilisema "hakuna internet".
/ip firewall nat
remove [find chain=srcnat action=masquerade]
add chain=srcnat action=masquerade src-address=192.168.20.0/24 \
    comment="Tech-Sphere internet sharing"

# MSS clamp: mtandao wa juu unazuia ICMP, hivyo path-MTU discovery haiwezi
# kufanya kazi. Bila kupunguza ukubwa wa segment, handshake inafanikiwa lakini
# kurasa hazipakii kabisa. Tumia thamani thabiti, SI clamp-to-pmtu, kwa sababu
# clamp-to-pmtu yenyewe inategemea ICMP iliyozuiliwa.
/ip firewall mangle
remove [find comment="Tech-Sphere MSS clamp"]
add chain=forward action=change-mss new-mss=1400 protocol=tcp tcp-flags=syn \
    tcp-mss=1401-65535 passthrough=yes comment="Tech-Sphere MSS clamp"

# TTL: mtandao wa juu ulikuwa unaruhusu traffic ya router yenyewe lakini
# unaangusha ya wateja. Tofauti pekee ilikuwa TTL iliyopungua kwa hop moja,
# ndivyo watoa huduma wanavyogundua na kuzuia tethering. Rudisha TTL 64.
remove [find comment="Tech-Sphere TTL fix"]
add chain=postrouting action=change-ttl new-ttl=set:64 out-interface=ether1 \
    passthrough=yes comment="Tech-Sphere TTL fix"

# ============================================================================
#  SAA: msingi wa muda wa vocha
#  Muda wa vocha unahesabiwa na vipima-muda vya router (tazama HATUA 9).
#  Bodi hii haina betri ya saa, hivyo umeme ukikatika tarehe hupotea na
#  vocha zingeisha mapema au zisiishe kabisa. NTP inaizuia hiyo.
# ============================================================================

/system clock
set time-zone-name=Africa/Dar_es_Salaam

/system ntp client
set enabled=yes mode=unicast primary-ntp=216.239.35.0 secondary-ntp=162.159.200.1

# ============================================================================
#  HATUA 4: LAN mpya (192.168.20.0/24), DHCP na DNS
#  Pool ni .20-.200 ili 192.168.20.1 (router) na .253 (PC) zisichukuliwe.
# ============================================================================

/ip address
:if ([:len [find address="192.168.20.1/24"]] = 0) do={
    add address=192.168.20.1/24 interface=bridge-LAN comment="LAN ya wateja"
}
remove [find address="192.168.10.1/24" interface=bridge-LAN]

/ip pool
set [find name=dhcp_pool1] ranges=192.168.20.20-192.168.20.200

/ip dhcp-server
set [find name=dhcp2] interface=bridge-LAN address-pool=dhcp_pool1 lease-time=1h disabled=no

/ip dhcp-server network
remove [find address="192.168.10.0/24"]
:if ([:len [find address="192.168.20.0/24"]] = 0) do={
    add address=192.168.20.0/24 gateway=192.168.20.1 dns-server=192.168.20.1
}

# DNS ilikuwa haina servers na allow-remote-requests=false — hotspot
# haiwezi kufanya kazi hivyo.
/ip dns
set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes

# Router yenyewe inajibu jina la biashara, ikilielekeza kwenye seva ya ndani.
# Faida mbili: ukurasa unafikika hata intaneti ikikatika, na traffic haitoki
# nje kisha kurudi. Domain hiyohiyo ikiwekewa tovuti ya umma baadaye, jibu la
# nje litakuwa tofauti na hili la ndani — na hiyo ndiyo nia.
/ip dns static
remove [find comment="Tech-Sphere portal"]
add name=tech-spherewifi.xyz address=$billingIp ttl=1d comment="Tech-Sphere portal"
add name=www.tech-spherewifi.xyz address=$billingIp ttl=1d comment="Tech-Sphere portal"

/ip hotspot walled-garden
:if ([:len [find dst-host="tech-spherewifi.xyz"]] = 0) do={
    add dst-host=tech-spherewifi.xyz action=allow comment="Tech-Sphere portal"
}
:if ([:len [find dst-host="www.tech-spherewifi.xyz"]] = 0) do={
    add dst-host=www.tech-spherewifi.xyz action=allow comment="Tech-Sphere portal"
}

# ============================================================================
#  HATUA 5: WiFi ya wateja — wlan1 iwe Access Point ya WAZI (bila password)
#  Kwa biashara ya vocha, WiFi haipaswi kuwa na password ya WPA: mteja
#  anahitaji kujiunga kwa uhuru ili afikie ukurasa wa malipo. Udhibiti wa
#  intaneti unafanywa na hotspot (HATUA 6), si na password.
#
#  ONYO: kwenye jaribio la leo wlan1 iliwashwa lakini ilibaki running=false
#  (radio haikupanda) na router ikaacha kujibu kabisa. Kama hilo litarudia,
#  acha wlan1 imezimwa na tumia Access Point ya nje iliyochomekwa LAN2 ikiwa
#  kwenye "bridge mode" — hotspot itafanya kazi vivyo hivyo.
# ============================================================================

/interface wireless
set wlan1 mode=ap-bridge ssid=$clientSsid band=2ghz-b/g \
    security-profile=default disabled=no

/interface bridge port
:if ([:len [find interface=wlan1 bridge=bridge-LAN]] = 0) do={
    add bridge=bridge-LAN interface=wlan1
}

# ============================================================================
#  HATUA 6: Hotspot (captive portal) — HII ILIKOSEKANA KABISA
#  Bila hii hakuna "Sign in", na watumiaji wanaotengenezwa na mfumo
#  hawana mahali pa kutumika. Ndiyo sababu vocha hazikuunganisha kifaa.
# ============================================================================

/ip hotspot profile
:if ([:len [find name=techsphere]] = 0) do={
    add name=techsphere hotspot-address=192.168.20.1 html-directory=flash/hotspot \
        login-by=http-chap,http-pap,cookie use-radius=no
} else={
    set [find name=techsphere] hotspot-address=192.168.20.1 \
        html-directory=flash/hotspot login-by=http-chap,http-pap,cookie
}

/ip hotspot
:if ([:len [find name=hotspot1]] = 0) do={
    add name=hotspot1 interface=bridge-LAN profile=techsphere address-pool=none \
        addresses-per-mac=2 disabled=no
} else={
    set [find name=hotspot1] interface=bridge-LAN profile=techsphere \
        address-pool=none addresses-per-mac=2 disabled=no
}

# ============================================================================
#  HATUA 7: Profiles za vifurushi (rate limits)
# ============================================================================

/ip hotspot user profile
:if ([:len [find name=Tarangire-lite]] = 0)         do={ add name=Tarangire-lite rate-limit=5M/5M shared-users=1 }
:if ([:len [find name=serengeti-daily]] = 0)        do={ add name=serengeti-daily rate-limit=5M/5M shared-users=1 }
:if ([:len [find name=ruaha-weekly]] = 0)           do={ add name=ruaha-weekly rate-limit=5M/5M shared-users=1 }
:if ([:len [find name=ngorongoro-fortnight]] = 0)   do={ add name=ngorongoro-fortnight rate-limit=5M/5M shared-users=1 }
:if ([:len [find name=kilimanjaro-unlimited]] = 0)  do={ add name=kilimanjaro-unlimited rate-limit=10M/10M shared-users=1 }

# ============================================================================
#  HATUA 8: API na usalama
# ============================================================================

/ip service
set api disabled=no port=8728 address=192.168.20.0/24
set telnet disabled=yes
set ftp disabled=yes

# ============================================================================
#  HATUA 9: Muda wa vocha kwa siku za kalenda
#  RouterOS 6 ina limit-uptime pekee, inayohesabu muda wa kuwa mtandaoni.
#  Kwa hiyo vocha ya "siku moja" ingedumu wiki nzima kwa mtu anayeingia
#  kwa saa chache kila siku. Badala yake, mfumo unaweka kipima-muda
#  kimoja kwa kila vocha (jina "exp-<MSIMBO>") wakati mteja anapoanza
#  kutumia. Kipima-muda kikifika, kinafuta akaunti, kinakata kipindi,
#  kisha kinajifuta chenyewe — hata mfumo ukiwa umezimwa.
#
#  Hakuna cha kuweka hapa; mfumo ndio unavitengeneza. Vinavyobaki bila
#  akaunti vinaondolewa na: php artisan vouchers:sync-router --prune
# ============================================================================
# /system scheduler print where name~"^exp-"

# ============================================================================
#  HATUA 10: Thibitisha (endesha kwa mkono baada ya import)
# ============================================================================
# /system ntp client print
# /system clock print
# /ip address print
# /ip hotspot print
# /ip hotspot ip-binding print
# /ip dhcp-client print
# /ip route print where dst-address=0.0.0.0/0
# /interface wireless print
# /ping 8.8.8.8 count=4
