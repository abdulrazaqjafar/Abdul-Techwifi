# Tech-Sphere kwenye Oracle Cloud Always Free (bila mini PC)

Mfumo unaishi kwenye VM ya Oracle. MikroTik inaunganisha **yenyewe** kwenda
Oracle kwa OpenVPN (outbound), hivyo laptop inaweza kuzimwa. Hakuna haja ya
IP ya umma kwenye Airtel.

```
Simu → MikroTik → (walled garden) → Oracle Laravel
                 ↘ OpenVPN client → Oracle (API 10.8.0.2 ← 10.8.0.1)
```

| Kipengele | Thamani |
|---|---|
| VPN subnet | `10.8.0.0/24` |
| Oracle (server) | `10.8.0.1` |
| MikroTik (client) | `10.8.0.2` |
| Laravel `router_ip` | `10.8.0.2` |
| OpenVPN | TCP `1194` |

Vocha zilizosajiliwa routerni **zinaendelea** hata VPN ikikatika. Kinachohitaji
Oracle: malipo (M-Pesa), vocha mpya, dashibodi.

---

## 0. Andaa data kwenye PC (kabla ya kuzima)

```powershell
cd "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM"
php scripts/prepare-oracle-migration.php
```

Utapata:

- `deploy/database-dump.sql`
- `deploy/app-key.txt` — **lazima**; password ya router inategemea APP_KEY
- `deploy/ORACLE-MIGRATION.txt` — orodha ya hatua

**Usipush faili hizi kwenye GitHub.** Bebe kwa `scp` au USB.

Nakili pia values za ClickPesa kutoka `.env` (CLIENT_ID, API_KEY, CHECKSUM_KEY).

---

## 1. Akaunti ya Oracle Cloud

1. Fungua <https://www.oracle.com/cloud/free/>
2. Sajili (kadi inahitajika kwa uthibitisho; Always Free haitozwi ndani ya kikomo)
3. Chagua region inayopatikana (mfano `eu-frankfurt-1`, `af-johannesburg-1` kama ipo)

### Unda VM

1. **Compute → Instances → Create instance**
2. Jina: `techsphere`
3. Image: **Ubuntu 24.04** (Minimal au Standard)
4. Shape (jaribu kwa mpangilio huu):
   - Kwanza: `VM.Standard.A1.Flex` — 2 OCPU, 12 GB (Always Free Ampere)
   - Ikiwa capacity imejaa: `VM.Standard.E2.1.Micro`
5. Networking: Public subnet + **Assign public IPv4**
6. Pakua SSH private key (`.key`) — hifadhi salama
7. Create

### Firewall ya Oracle (Security List / NSG)

Fungua ingress:

| Port | Protocol | Lengo |
|---|---|---|
| 22 | TCP | SSH |
| 80 | TCP | Portal HTTP |
| 443 | TCP | HTTPS (baadaye) |
| 1194 | TCP | OpenVPN (MikroTik) |

**Usifungue 8728** kwa internet. API inapita VPN pekee.

Andika **Public IP** ya instance — utahitaji kila mahali.

---

## 2. SSH na clone

Kutoka PC (PowerShell), badilisha IP na path ya key:

```powershell
ssh -i "$env:USERPROFILE\Downloads\ssh-key.key" ubuntu@PUBLIC_IP
```

Kwenye VM:

```bash
sudo apt-get update -y
sudo apt-get install -y git
git clone https://github.com/abdulrazaqjafar/Abdul-Techwifi.git ~/techsphere-source
cd ~/techsphere-source
```

Nakili dump + APP_KEY kutoka PC:

```powershell
scp -i "$env:USERPROFILE\Downloads\ssh-key.key" `
  "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM\deploy\database-dump.sql" `
  "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM\deploy\app-key.txt" `
  ubuntu@PUBLIC_IP:~/techsphere-source/deploy/
```

---

## 3. Sakinisha Laravel

```bash
cd ~/techsphere-source
sudo APP_PUBLIC_URL=http://PUBLIC_IP bash deploy/install-oracle.sh
```

Script inasakinisha Nginx, MariaDB, PHP, queue, scheduler — **bila** netplan ya
`192.168.20.253`.

Weka ClickPesa:

```bash
sudo nano /var/www/techsphere/.env
# CLICKPESA_CLIENT_ID=...
# CLICKPESA_API_KEY=...
# CLICKPESA_CHECKSUM_KEY=...
sudo -u www-data php /var/www/techsphere/artisan config:cache
```

Jaribu: fungua `http://PUBLIC_IP` kwenye browser.

---

## 4. OpenVPN (Oracle ↔ MikroTik)

Kwenye VM:

```bash
cd /var/www/techsphere
sudo bash deploy/openvpn/setup-server.sh
```

Nakili certs kwenda PC:

```powershell
scp -i "$env:USERPROFILE\Downloads\ssh-key.key" -r `
  ubuntu@PUBLIC_IP:/etc/openvpn/mikrotik `
  "$env:USERPROFILE\Desktop\openvpn-mikrotik"
```

### Import kwenye MikroTik (Winbox)

1. **Files** → drag `ca.crt`, `mikrotik.crt`, `mikrotik.key`  
   (au `mikrotik.p12` — password: `techsphere`)
2. Terminal:

```
/certificate import file-name=ca.crt passphrase=""
/certificate import file-name=mikrotik.crt passphrase=""
/certificate import file-name=mikrotik.key passphrase=""
/certificate print
```

Cheti cha client kinapaswa kuwa na common-name `mikrotik` (KKT / Trusted).
Ikiwa jina si `mikrotik`:

```
/certificate set [find common-name=mikrotik] name=mikrotik
```

3. Hariri `mikrotik/oracle-openvpn-client.rsc` — badilisha `PUBLIC_IP`  
   au tumia faili iliyotengenezwa: `deploy/openvpn/generated/oracle-openvpn-client.rsc`
4. `/import file-name=oracle-openvpn-client.rsc`
5. Thibitisha:

```
/interface ovpn-client print
/ip address print where interface~"ovpn"
```

Unapaswa kuona `10.8.0.2`.

### Elekeza Laravel kwenye VPN IP

Kwenye Oracle VM:

```bash
cd /var/www/techsphere
sudo -u www-data php scripts/apply-oracle-router-ip.php
```

Inapaswa kusema muunganisho umefanikiwa.

---

## 5. Captive portal → Oracle

Bado VPN ikiwa hai (kutoka VM au PC):

```bash
cd /var/www/techsphere
sudo -u www-data php scripts/configure-oracle-portal.php PUBLIC_IP
```

Hii inafanya:

- kusasisha `login.html` (domain + public IP)
- walled garden + DNS static kwenye MikroTik
- kupakia `login.html` kwenye router
- kuweka `APP_URL`

Au import kwa mkono: `mikrotik/oracle-portal-cutover.rsc` (badilisha PUBLIC_IP).

---

## 6. Ukaguzi (kabla ya kuzima PC)

Kwenye VM:

```bash
sudo bash deploy/verify-oracle.sh
```

Kwa mkono:

| Jaribio | Matokeo yanayotarajiwa |
|---|---|
| `curl -I http://PUBLIC_IP` | HTTP 200 |
| `php scripts/apply-oracle-router-ip.php` | Muunganisho OK |
| `php artisan vouchers:sync-router` | Hakuna kosa la router |
| Simu → Abdul-Tech → Sign in | Ukurasa wa vifurushi (Oracle) |
| Nunua / weka vocha | Device inapata intaneti |
| Dashibodi `http://PUBLIC_IP/login` | Unaingia |

Kisha kwenye PC: **Ctrl+C** kwenye `php artisan serve`. Laptop inaweza kuzimwa.

---

## 7. Matatizo ya kawaida

| Dalili | Sababu / suluhisho |
|---|---|
| `Out of capacity` Ampere | Tumia `E2.1.Micro` au region nyingine |
| OVPN `connecting` milele | Security List 1194/tcp? cert imported? `PUBLIC_IP` sahihi? |
| Laravel haiwezi API | `router_ip=10.8.0.2`? api address ina `10.8.0.0/24`? |
| Simu haioni portal | walled garden + DNS static? `login.html` imepakuliwa? |
| Password ya router haifanyi kazi | `deploy/app-key.txt` haikutumika — weka password upya kwenye Settings |
| Free tier warning | Kaa ndani ya Always Free; usiongeze block volumes za kulipia |

---

## Amri muhimu

```bash
# Sasisha code
cd /home/ubuntu/techsphere-source && git pull
sudo bash deploy/update.sh

# Magogo
tail -f /var/www/techsphere/storage/logs/laravel.log
sudo journalctl -u openvpn@server -f

# Hali ya VPN
/interface ovpn-client print   # (MikroTik)
```

---

## Related

- Mini PC (badala ya Oracle): [README.md](README.md)
- Domain / Cloudflare: [DOMAIN.md](DOMAIN.md)
- Malipo ClickPesa: [MALIPO.md](MALIPO.md)
- Go-live: [ANZA-RASMI.md](ANZA-RASMI.md)
