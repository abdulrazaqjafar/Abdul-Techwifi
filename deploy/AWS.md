# Tech-Sphere kwenye AWS Free Tier (bila mini PC)

Mfumo unaishi kwenye **EC2** (Ubuntu). MikroTik inaunganisha **yenyewe** kwenda
AWS kwa OpenVPN (outbound), hivyo laptop inaweza kuzimwa.

> **Muhimu:** AWS Free Tier ni bure kwa **miezi 12** kutoka siku ya kusajili
> akaunti mpya (kwa `t2.micro` / `t3.micro` ndani ya kikomo cha saa). Baada ya
> mwaka, instance inaanza kulipwa ~$8–12/mwezi isipokuwa uizime. Weka kikumbusho
> kwenye kalenda yako.

```
Simu → MikroTik → (walled garden) → AWS Laravel
                 ↘ OpenVPN client → AWS (API 10.8.0.2 ← 10.8.0.1)
```

| Kipengele | Thamani |
|---|---|
| Instance | `t2.micro` au `t3.micro` (Free Tier) |
| OS | Ubuntu Server 24.04 LTS |
| VPN subnet | `10.8.0.0/24` |
| AWS (server) | `10.8.0.1` |
| MikroTik (client) | `10.8.0.2` |
| Laravel `router_ip` | `10.8.0.2` |
| OpenVPN | TCP `1194` |

Vocha zilizosajiliwa routerni **zinaendelea** hata VPN ikikatika. Kinachohitaji
AWS: malipo (M-Pesa), vocha mpya, dashibodi.

---

## 0. Andaa data kwenye PC

```powershell
cd "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM"
php scripts/prepare-aws-migration.php
```

Utapata:

- `deploy/database-dump.sql`
- `deploy/app-key.txt` — **lazima** (password ya router inategemea APP_KEY)
- `deploy/AWS-MIGRATION.txt` — orodha ya hatua

**Usipush faili hizi kwenye GitHub.** Bebe kwa `scp`.

Nakili ClickPesa kutoka `.env`: `CLIENT_ID`, `API_KEY`, `CHECKSUM_KEY`.

---

## 1. Sajili AWS

1. Fungua <https://aws.amazon.com/free/>
2. **Create a free account**
3. Weka email, nenosiri, anwani, namba ya simu
4. Weka kadi (Visa/Mastercard) — AWS inaweza kuweka hold kidogo kisha kurudisha
5. Chagua mpango wa **Basic / Free**

Ikiwa “Start” inagoma kama Oracle: jaribu Incognito, zima VPN, kadi yenye
international payments, au chat ya AWS Support.

---

## 2. Unda EC2 (seva)

1. Ingia <https://console.aws.amazon.com/>
2. Juu kulia: chagua **Region** — pendekeza `eu-west-1` (Ireland) au
   `eu-central-1` (Frankfurt). Epuka regions zilizo mbali sana.
3. Tafuta **EC2** → **Launch instance**

### Mipangilio

| Sehemu | Thamani |
|---|---|
| Name | `techsphere` |
| AMI | **Ubuntu Server 24.04 LTS** (Free tier eligible) — epuka “25.04/26.04/resolute” ikiwa unaona; installer sasa inachagua PHP yenyewe |
| Instance type | **t2.micro** au **t3.micro** (Free tier eligible) |
| Key pair | Create new → jina `techsphere` → `.pem` → **pakua na hifadhi** |
| Network | Allow SSH from your IP (au Anywhere 0.0.0.0/0 ukiwa unajaribu) |
| Storage | 8–20 GB gp3 (Free Tier inaruhusu hadi 30 GB) |

### Security Group (firewall) — Ongeza rules:

| Type | Port | Source |
|---|---|---|
| SSH | 22 | My IP (au 0.0.0.0/0 kwa jaribio) |
| HTTP | 80 | 0.0.0.0/0 |
| HTTPS | 443 | 0.0.0.0/0 |
| Custom TCP | **1194** | 0.0.0.0/0 (OpenVPN) |

**Usifungue 8728.** API inapita VPN pekee.

4. **Launch instance**
5. Fungua instance → andika **Public IPv4 address** (mfano `18.203.x.x`)

> IP ya umma ya Free Tier inaweza **kubadilika** ukizima instance.
> Baadaye: Allocate **Elastic IP** (bure ukiwa imeunganishwa na instance
> inayoendesha) ili IP isibadilike.

---

## 3. SSH na clone

Kwenye PowerShell (badilisha path ya `.pem` na IP):

```powershell
# Ruhusa ya key (PowerShell)
icacls "$env:USERPROFILE\Downloads\techsphere.pem" /inheritance:r
icacls "$env:USERPROFILE\Downloads\techsphere.pem" /grant:r "$env:USERNAME`:R"

ssh -i "$env:USERPROFILE\Downloads\techsphere.pem" ubuntu@PUBLIC_IP
```

Kwenye EC2:

```bash
sudo apt-get update -y
sudo apt-get install -y git
git clone https://github.com/abdulrazaqjafar/Abdul-Techwifi.git ~/techsphere-source
cd ~/techsphere-source
```

Nakili dump + APP_KEY kutoka PC:

```powershell
scp -i "$env:USERPROFILE\Downloads\techsphere.pem" `
  "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM\deploy\database-dump.sql" `
  "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM\deploy\app-key.txt" `
  ubuntu@PUBLIC_IP:~/techsphere-source/deploy/
```

---

## 4. Sakinisha Laravel

```bash
cd ~/techsphere-source
sudo APP_PUBLIC_URL=http://PUBLIC_IP bash deploy/install-aws.sh
```

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

## 5. OpenVPN (AWS ↔ MikroTik)

Kwenye EC2:

```bash
cd /var/www/techsphere
sudo PUBLIC_IP=PUBLIC_IP bash deploy/openvpn/setup-server.sh
```

Nakili certs kwenda PC:

```powershell
scp -i "$env:USERPROFILE\Downloads\techsphere.pem" -r `
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
/certificate set [find common-name=mikrotik] name=mikrotik
/certificate print
```

3. Hariri `mikrotik/oracle-openvpn-client.rsc` — badilisha `PUBLIC_IP` kuwa IP ya AWS  
   (jina la faili lina “oracle” lakini linafaa AWS pia — ni OpenVPN sawa)
4. `/import file-name=oracle-openvpn-client.rsc`
5. Thibitisha:

```
/interface ovpn-client print
/ip address print where interface~"ovpn"
```

Unapaswa kuona `10.8.0.2`.

### Elekeza Laravel kwenye VPN IP

```bash
cd /var/www/techsphere
sudo -u www-data php scripts/apply-oracle-router-ip.php
```

---

## 6. Captive portal → AWS

```bash
cd /var/www/techsphere
sudo -u www-data php scripts/configure-oracle-portal.php PUBLIC_IP
```

(Hii script inafanya kazi kwa cloud yoyote — inasasisha DNS, walled garden, `login.html`.)

---

## 7. Ukaguzi — kisha zima PC

```bash
sudo bash deploy/verify-oracle.sh
```

| Jaribio | Matokeo |
|---|---|
| `curl -I http://PUBLIC_IP` | HTTP 200 |
| `php scripts/apply-oracle-router-ip.php` | Muunganisho OK |
| Simu → Abdul-Tech → Sign in | Ukurasa wa vifurushi |
| Nunua / weka vocha | Intaneti |
| `http://PUBLIC_IP/login` | Dashibodi |

Kisha kwenye PC: **Ctrl+C** kwenye `php artisan serve`. Laptop inaweza kuzimwa.

---

## 8. Epuka bili baada ya Free Tier

1. **Billing → Budgets** — weka budget alert (mfano $1)
2. **Elastic IP** — unganisha na instance ili IP isibadilike; IP isiyotumika **inalipwa**
3. Usichague instance kubwa kuliko `t2.micro` / `t3.micro` bila kujua
4. Miezi 11 baada ya kusajili: amua — lipa ~$10/mwezi, hamia Hetzner, au mini PC

---

## Matatizo ya kawaida

| Dalili | Suluhisho |
|---|---|
| SSH timeout | Security Group port 22? IP sahihi? |
| `Permission denied (publickey)` | Tumia user `ubuntu` + faili `.pem` sahihi |
| OVPN haungi | Port 1194/tcp kwenye Security Group? |
| Portal haifunguki kwenye simu | `configure-oracle-portal.php`? walled garden? |
| APP_KEY / password ya router | Hakikisha `app-key.txt` ilinakiliwa kabla ya install |

---

## Related

- Oracle (ikiwa utajaribu tena): [ORACLE.md](ORACLE.md)
- Mini PC: [README.md](README.md)
- Domain: [DOMAIN.md](DOMAIN.md)
- Malipo: [MALIPO.md](MALIPO.md)
