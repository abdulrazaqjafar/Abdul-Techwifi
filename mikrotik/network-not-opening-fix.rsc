# ============================================================
# FIX: Ukurasa haifunguki kwenye simu
# Tatizo: simu (192.168.10.x) haiwezi kufikia PC (192.168.1.182)
# Bandika amri MOJA MOJA kwenye MikroTik Terminal
# ============================================================

# --- 1. Thibitisha MikroTik inaweza kufikia PC ---
/ping 192.168.1.182 count=3

# Ikiwa ping inashindwa, PC na MikroTik lazima wawe kwenye network moja (192.168.1.x)

# --- 2. Walled garden — ruhusu simu kufikia PC bila login ---
/ip hotspot walled-garden remove [find comment~"Tech-Sphere"]
/ip hotspot walled-garden remove [find comment~"TechSphere"]
/ip hotspot walled-garden add dst-host=192.168.1.182 dst-port=8000 action=allow comment="Tech-Sphere billing HTTP"
/ip hotspot walled-garden add dst-host=192.168.1.182 action=allow comment="Tech-Sphere billing server"

# --- 3. Firewall — MUHIMU: ruhusu hotspot kufikia PC ---
/ip firewall filter add chain=forward action=accept protocol=tcp src-address=192.168.10.0/24 dst-address=192.168.1.182 dst-port=8000 place-before=0 comment="Hotspot-to-Billing-HTTP"

# Ruhusu kurudi (reply) kutoka PC kwenda simu
/ip firewall filter add chain=forward action=accept connection-state=established,related place-before=1 comment="Allow-established"

# --- 4. Thibitisha hotspot profile ---
/ip hotspot profile set [find] html-directory=hotspot login-by=http-chap,http-pap,cookie

# --- 5. Angalia IP za interfaces ---
/ip address print

# Hotspot: 192.168.10.1  |  WAN: 192.168.1.x  |  PC: 192.168.1.182

# --- 6. Futa authorization ya simu (jaribio upya) ---
/ip hotspot host print
/ip hotspot cookie print
# /ip hotspot host remove [find]
# /ip hotspot cookie remove [find]

# ============================================================
# KWENYE PC (Windows) — endesha kama Administrator:
#   scripts\allow-hotspot-firewall.bat
# Server lazima iwe: php artisan serve --host=0.0.0.0 --port=8000
# ============================================================

# ============================================================
# NJIA BORA (inapendekezwa sana):
# Unganisha PC kwa kebo kwenye LAN ya MikroTik (si WiFi ya nyumbani)
# Weka PC IP: 192.168.10.254, gateway: 192.168.10.1
# Badilisha login.html: 192.168.1.182 → 192.168.10.254
# ============================================================

# --- 7. Jaribu kwenye simu (data ZIMA) ---
# http://192.168.1.182:8000
# http://neverssl.com
