# ============================================================
# FIX: Mfumo hauwezi kuunganisha na MikroTik API
# Dalili: voucha inakubaliwa lakini simu haipati internet
# Bandika amri MOJA MOJA kwenye MikroTik Terminal
# ============================================================

# --- 1. Ruhusu PC ya billing kutumia API ---
# PC yako: 192.168.1.182  |  Hotspot: 192.168.10.0/24
/ip service set api disabled=no port=8728 address=192.168.10.0/24,192.168.1.0/24

# Thibitisha
/ip service print where name=api

# --- 2. Angalia IP za router (tumia mojawapo kwenye ukurasa /router) ---
/ip address print

# Hotspot LAN : 192.168.10.1
# WAN (nyumbani): 192.168.1.134   <-- hii ni salama zaidi kwa PC ya 192.168.1.182

# --- 3. Thibitisha user wa API ---
/user print
# Lazima kuwe na user "admin" (au uliyoweka kwenye /router) yenye group=full

# --- 4. Angalia vifaa vilivyounganishwa hotspot ---
/ip hotspot host print
/ip hotspot active print

# --- 5. Angalia hotspot users walioundwa na mfumo ---
/ip hotspot user print

# ============================================================
# BAADA YA HAPA:
# 1. Kwenye admin panel fungua /router
# 2. Weka Router IP = 192.168.1.134 (au 192.168.10.1)
#    Username = admin, Password = ya MikroTik
# 3. Bonyeza Test Connection — lazima ionyeshe "umefanikiwa"
# 4. Kwenye PC endesha: php scripts/diagnose-router-access.php
# ============================================================
