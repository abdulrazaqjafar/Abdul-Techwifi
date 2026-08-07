# ============================================================
# FIX: Simu ifikie billing server
# Bandika amri MOJA MOJA kwenye Terminal (usibandike zote pamoja)
# ============================================================

# --- A. Walled garden (ruhusu simu kufikia PC) ---
/ip hotspot walled-garden add dst-host=192.168.1.182 dst-port=8000 action=allow comment=TechSphere-HTTP
/ip hotspot walled-garden add dst-host=192.168.1.182 action=allow comment=TechSphere-PC

# --- B. Firewall: ruhusu hotspot (10.x) kufikia PC (1.x) ---
/ip firewall filter add chain=forward action=accept protocol=tcp src-address=192.168.10.0/24 dst-address=192.168.1.182 dst-port=8000 place-before=0 comment=Hotspot-to-Billing-HTTP
/ip firewall filter add chain=forward action=accept connection-state=established,related place-before=1 comment=Allow-established-reply

# --- C. Thibitisha ---
/ip hotspot walled-garden print
/ip address print

# Angalia IP ya WAN ya MikroTik — lazima iwe 192.168.1.x (network ile ile na PC)
# PC yako: 192.168.1.182

# ============================================================
# NJIA BORA (inapendekezwa): Unganisha PC kwa kebo kwenye MikroTik LAN
# Weka PC IP: 192.168.10.254, gateway: 192.168.10.1
# Badilisha login.html kurudi 192.168.10.254:8000
# ============================================================
