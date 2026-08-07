# Kuweka mfumo utumike muda wote (bila kuacha PC imewaka)

Kuna njia **mbili**:

| Njia | Gharama | Mwongozo |
|---|---|---|
| **Oracle Cloud Always Free** (bila kununua kifaa) | Bure (Always Free) | **[ORACLE.md](ORACLE.md)** ← anza hapa ukiwa huna mini PC |
| Mini PC ofisini (IP `192.168.20.253`) | ~TSh 250k–500k | Sehemu zilizo chini |

Mwongozo huu (chini) ni wa **mini PC**. Kwa Oracle, MikroTik inaunganisha
kwenda cloud kwa OpenVPN — soma [ORACLE.md](ORACLE.md).

Kwa matumizi ya domain `tech-spherewifi.xyz` (HTTPS na ufikiaji wa mbali),
soma [DOMAIN.md](DOMAIN.md) baada ya kumaliza mwongozo huu.

Kwa malipo ya ClickPesa — kikomo cha maombi, KYC, na kuwasha M-Pesa —
soma [MALIPO.md](MALIPO.md).

Kwa kuanza matumizi rasmi na ukaguzi wa kabla ya kuuza,
soma [ANZA-RASMI.md](ANZA-RASMI.md).

---

## 1. Kifaa cha kununua

Mfumo wako ni mdogo kwa kiwango cha ajabu: database nzima ina rows 191 tu.
Hivyo hauhitaji kifaa cha nguvu.

### Chaguo la kwanza: mini PC iliyotumika (napendekeza)

Tafuta mojawapo ya hizi (zinapatikana Kariakoo na madukani mtandaoni):

- Dell OptiPlex 3050 / 7050 Micro
- HP EliteDesk 800 G3 Mini au ProDesk 400 G3 Mini
- Lenovo ThinkCentre M710q / M720q Tiny

Kiwango cha chini kinachotosha: **Core i3, RAM 4GB, SSD 128GB**.
Bei ya kawaida: TSh 250,000 – 500,000. Umeme: wati 10 – 20 tu.

Kwa nini napendekeza hii kuliko Raspberry Pi: ni x86, hivyo Ubuntu
inasakinishwa moja kwa moja bila hatua za ziada; inakuja na SSD, RAM na
casing tayari; na mara nyingi ni rahisi zaidi kuipata Tanzania kuliko Pi
yenye vifaa vyake vyote.

### Chaguo la pili: Raspberry Pi 5 (4GB)

Inafanya kazi vizuri, lakini utahitaji kununua kando: PSU rasmi, casing yenye
feni, na SSD au microSD ya ubora. Jumla mara nyingi inakaribia bei ya mini PC.

### Kifaa cha ziada: UPS ndogo

Umeme ukikatika, router na seva vinazimika, na biashara inasimama. Mini PC
(wati 20) pamoja na MikroTik (wati 5) ni mzigo mdogo sana, hivyo UPS ya
650VA – 1000VA inaweza kuviendesha vyote viwili kwa dakika 30 hadi saa moja.
Bei: TSh 150,000 – 350,000. Unganisha **router na seva** kwenye UPS moja.

---

## 2. Kusakinisha Ubuntu Server

1. Pakua **Ubuntu Server 24.04 LTS** kutoka <https://ubuntu.com/download/server>
2. Tengeneza USB ya kuboot kwa [Rufus](https://rufus.ie) au balenaEtcher
3. Boot kifaa kwa USB hiyo, kisha fuata maswali:
   - Chagua **Ubuntu Server** (siyo minimized)
   - Weka jina la mtumiaji unalolikumbuka, mfano `techsphere`
   - Kwenye hatua ya **SSH Setup**, washa **Install OpenSSH server**
   - Ruka snaps zote zinazopendekezwa
4. Baada ya kumaliza, ondoa USB na uwashe upya

---

## 3. Kuweka IP ya kudumu 192.168.20.253

Unganisha kifaa kwenye MikroTik kwa kebo (port LAN1 hadi LAN4), kisha:

```bash
ip -brief address          # tafuta jina la interface, mfano enp1s0
```

Hariri `deploy/netplan-static-ip.yaml` ubadilishe `enp1s0` kuwa jina lako,
kisha:

```bash
sudo cp deploy/netplan-static-ip.yaml /etc/netplan/01-techsphere.yaml
sudo chmod 600 /etc/netplan/01-techsphere.yaml
sudo netplan apply
```

> **Muhimu:** kabla ya hatua hii, badilisha IP ya PC yako ya sasa ili
> `192.168.20.253` isiwe inatumiwa na vifaa viwili:
>
> ```powershell
> netsh interface ip set address name="Ethernet" dhcp
> ```

---

## 4. Kunakili mfumo kwenda kwenye seva

Kwenye PC, hakikisha umeendesha:

```powershell
php scripts/export-database.php
```

Hiyo inatengeneza mafaili mawili:

- `deploy/database-dump.sql` — data yako yote: vocha, mauzo, wateja,
  mipangilio ya router
- `deploy/app-key.txt` — `APP_KEY` ya mfumo

**Faili ya pili ni muhimu sana.** Password ya router imehifadhiwa kwa
encryption inayotegemea `APP_KEY`. Seva mpya ikitengeneza key nyingine,
password hiyo haitasomeka na mfumo hautaweza kuunganishwa na MikroTik.
`install.sh` inatumia faili hiyo yenyewe, hivyo hakikisha inasafiri pamoja
na mfumo.

Kisha nakili folda nzima ya mfumo kwenda kwenye seva. Njia rahisi ni USB, au
kwa mtandao:

```powershell
scp -r "C:\Users\TECH SPHERE\Desktop\MY FOLDER\LATEST WIFI SYSTEM" techsphere@192.168.20.253:~/techsphere-source
```

---

## 5. Kuendesha usakinishaji

Kwenye seva:

```bash
cd ~/techsphere-source
sudo bash deploy/install.sh
```

Script inasakinisha Nginx, MariaDB, PHP 8.3, Node.js na Composer; inatengeneza
database na password yake; inaingiza data yako; inajenga assets; inaweka queue
worker na scheduler; na inafungua firewall. Inaweza kuendeshwa mara nyingi bila
kuharibu kitu.

Baada ya kumaliza, weka credentials za ClickPesa:

```bash
sudo nano /var/www/techsphere/.env
```

Nakili `CLICKPESA_CLIENT_ID`, `CLICKPESA_API_KEY` na `CLICKPESA_CHECKSUM_KEY`
kutoka `.env` ya PC yako, kisha:

```bash
cd /var/www/techsphere
sudo -u www-data php artisan config:cache
```

---

## 6. Kuzima seva ya PC

Kwenye PC, simamisha `php artisan serve` (Ctrl+C kwenye terminal yake). Tangu
sasa mfumo unaendeshwa na seva mpya.

Fungua kwenye browser: <http://192.168.20.253>

Anwani ya zamani <http://192.168.20.253:8000> pia inaendelea kufanya kazi, kwa
sababu Nginx imesanidiwa kusikiliza port zote mbili. Ndiyo maana `login.html`
ya router haihitaji kubadilishwa.

---

## 7. Uthibitisho

Kwenye seva:

```bash
systemctl status techsphere-queue      # queue worker inafanya kazi?
sudo -u www-data crontab -l            # scheduler ipo?
curl -I http://localhost               # mfumo unajibu?
tail -f /var/www/techsphere/storage/logs/laravel.log
```

Kutoka PC yako (au simu iliyoruhusiwa):

```powershell
php scripts/verify-hotspot.php
```

Jaribio la mwisho: unganisha simu kwenye Abdul-Tech, nunua kifurushi, na
hakikisha inaunganishwa.

---

## 8. Kusasisha mfumo baadaye

Ukibadilisha code kwenye PC, nakili folda mpya kwenye seva kisha:

```bash
sudo bash ~/techsphere-source/deploy/update.sh
```

Hiyo inasasisha code, dependencies, assets na migrations, halafu inawasha
huduma tena. `.env`, storage na database havitaguswa.

---

## 9. Nakala rudufu (backup)

Weka hii kwenye cron ya root ili database ihifadhiwe kila usiku:

```bash
sudo crontab -e
```

Ongeza:

```
0 2 * * * mariadb-dump techsphere_wifi | gzip > /var/backups/techsphere-$(date +\%F).sql.gz
5 2 * * * find /var/backups -name 'techsphere-*.sql.gz' -mtime +30 -delete
```

Kumbuka kutengeneza folda: `sudo mkdir -p /var/backups`

---

## Maswali ya kawaida

**Je, nitaweza kuufikia mfumo nikiwa nje ya ofisi?**
Kwa mpangilio huu, hapana — mfumo unapatikana ndani ya mtandao wako tu. Hilo
ndilo linalouweka salama. Ukihitaji ufikiaji wa mbali baadaye, tunaweza
kuongeza VPN au Cloudflare Tunnel bila kubadilisha kitu kingine.

**Je, malipo yatafanya kazi bila URL ya umma?**
Ndiyo. Mfumo unauliza ClickPesa hali ya malipo wenyewe (`queryPaymentStatus`),
hivyo hauhitaji kupokea webhook kutoka nje.

**Intaneti ikikatika, je wateja wataweza kununua?**
Wataona ukurasa wa vifurushi kwa sababu upo ndani ya mtandao, lakini malipo
yenyewe yanahitaji intaneti ili kufika ClickPesa. Wateja walio na vocha hai
wataendelea kutumia mtandao bila tatizo.
