# === HATUA 1: Washa API (Lazima) ===
# MUHIMU: ruhusu network zote mbili — hotspot (10.x) na ile ya PC (1.x).
# Ikiwa unaruhusu 192.168.10.0/24 tu, PC yenye 192.168.1.182 itakataliwa.
/ip service
set api disabled=no port=8728 address=192.168.10.0/24,192.168.1.0/24

# === HATUA 2: Walled garden kwa billing server (PC kwenye 192.168.1.182) ===
# Mteja hafanyi malipo kwa data yake — mfumo (PC) unatumia intaneti ya WiFi
/ip hotspot walled-garden
add dst-host=192.168.1.182 dst-port=8000 action=allow comment="Tech-Sphere billing HTTP"
add dst-host=192.168.1.182 action=allow comment="Tech-Sphere billing server"

# === HATUA 3: Hotspot profiles (puuza error kama zipo tayari) ===
/ip hotspot user profile
add name=serengeti-daily rate-limit=5M/5M shared-users=1
add name=ruaha-weekly rate-limit=5M/5M shared-users=1
add name=ngorongoro-fortnight rate-limit=5M/5M shared-users=1
add name=kilimanjaro-unlimited rate-limit=10M/10M shared-users=1

# === Thibitisha API imewashwa ===
# /ip service print where name=api
