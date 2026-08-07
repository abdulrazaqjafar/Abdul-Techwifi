# ============================================================
# FIX: Simu ionyeshe "Sign in to network" / captive portal
# Bandika amri MOJA MOJA kwenye MikroTik Terminal
# ============================================================

# --- 1. Thibitisha hotspot server inaendesha ---
/ip hotspot print
/ip hotspot profile print

# Lazima uone hotspot kwenye interface sahihi (mf. bridge-LAN au wlan)
# html-directory lazima iwe "hotspot" (folder uliopakia login.html)

# --- 2. Weka profile sahihi (badilisha jina ikiwa si "default") ---
/ip hotspot profile set [find] html-directory=hotspot login-by=http-chap,http-pap,cookie use-radius=no dns-name=wifi.abdul-tech.local

# --- 3. Hakikisha login.html iko kwenye folder sahihi ---
/file print where name~"login.html"

# Inapaswa kuonekana: hotspot/login.html
# Ikiwa haipo, pakia tena kupitia Winbox → Files → hotspot

# --- 4. Futa authorization ya zamani ya simu yako (jaribio) ---
# Badilisha MAC kwa MAC ya simu yako kutoka /ip hotspot host print
/ip hotspot host print
/ip hotspot cookie print
/ip hotspot active print

# Futa host/cookie za kifaa cha majaribio (badilisha MAC):
# /ip hotspot host remove [find mac-address=AA:BB:CC:DD:EE:FF]
# /ip hotspot cookie remove [find mac-address=AA:BB:CC:DD:EE:FF]
# /ip hotspot active remove [find mac-address=AA:BB:CC:DD:FF]

# --- 5. Thibitisha DNS inaelekeza kwenye router kwa wateja wasio na access ---
/ip dhcp-server print
/ip dhcp-server network print

# dns-server lazima iwe IP ya hotspot (mf. 192.168.10.1)

# --- 6. Walled garden — PC ya billing pekee (usiruhusu internet bila login) ---
/ip hotspot walled-garden print

# ============================================================
# KWENYE SIMU (MUHIMU SANA):
# 1. ZIMA data ya simu / mobile data kabisa
# 2. Settings → Wi-Fi → Sahau/Forget network ya hotspot
# 3. Zima Wi-Fi, washa tena
# 4. Unganisha Wi-Fi ya hotspot tena
# 5. Subiri sekunde 5–15 — alert inapaswa kuonekana
#
# Ikiwa alert haionekani, fungua Chrome/Safari na andika:
#   http://neverssl.com
#   au http://192.168.10.1
# ============================================================
