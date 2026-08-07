# Kutumia tech-spherewifi.xyz na mfumo

## Mpango kwa ufupi

| Anwani | Inatumika na nani | Inafikaje |
|---|---|---|
| `tech-spherewifi.xyz` | Wateja wa Abdul-Tech | MikroTik inajibu yenyewe → `192.168.20.253` |
| `admin.tech-spherewifi.xyz` | Wewe ukiwa mbali na ofisi | Cloudflare Tunnel → seva |
| `192.168.20.253` | Dharura | Moja kwa moja kwenye LAN |

Kiini cha mpango huu: **jina moja linaweza kutoa majibu tofauti kutegemea uko
wapi.** Mteja aliyeunganishwa na Abdul-Tech anapoandika
`tech-spherewifi.xyz`, MikroTik inamjibu kwa IP ya ndani, hivyo hafiki
intaneti kabisa — ni haraka, na inafanya kazi hata intaneti ikikatika.

Hii tayari **imeshawekwa** kwenye router yako:

```
php scripts/setup-domain-dns.php
```

---

## Hatua ya 1: Kuhamishia DNS Cloudflare

Cloudflare inahitajika kwa mambo matatu: cheti cha HTTPS bila kufungua seva,
tunnel ya ufikiaji wa mbali, na ulinzi wa mlango wa admin. Yote ni bure.

Domain yako ipo **Namecheap** (nameservers ni `dns1.registrar-servers.com`).

1. Fungua akaunti: <https://dash.cloudflare.com/sign-up>
2. Bonyeza **Add a site**, andika `tech-spherewifi.xyz`, chagua mpango wa
   **Free** (uko chini kabisa ya orodha)
3. Cloudflare itasoma rekodi zilizopo. Utaona rekodi ya A ikielekea
   `192.64.119.230` — huo ni ukurasa wa maegesho wa Namecheap. Iache kama
   ilivyo; tutaibadilisha tutakapotengeneza tovuti ya biashara
4. Utaonyeshwa **nameservers mbili**, mfano:

   ```
   dana.ns.cloudflare.com
   rick.ns.cloudflare.com
   ```

5. Ingia <https://namecheap.com> → **Domain List** → bonyeza **Manage** kando
   ya `tech-spherewifi.xyz` → sehemu ya **Nameservers** badilisha kutoka
   **Namecheap BasicDNS** kwenda **Custom DNS**, kisha weka hizo mbili za
   Cloudflare na bonyeza alama ya kijani ya kuhifadhi
6. Rudi Cloudflare bonyeza **Check nameservers now**

Kubadilika huchukua dakika chache hadi saa 24. Cloudflare itakutumia barua
pepe ikikamilika.

Kufuatilia bila kubahatisha:

```powershell
php scripts/check-domain-dns.php
```

> Kama unatumia barua pepe yenye jina hili (mfano `info@tech-spherewifi.xyz`),
> hakikisha rekodi za **MX** zimeingia Cloudflare wakati wa uchambuzi.
> Zisipoingia, barua pepe itakatika.

> **Usiweke rekodi ya A ya `tech-spherewifi.xyz` kwenda `192.168.20.253`.**
> Hiyo ni IP ya ndani; Cloudflare haitaikubali, na haitasaidia kwa sababu
> MikroTik tayari inajibu jina hilo kwa wateja wako.

---

## Hatua ya 2: API token kwa ajili ya cheti

Baada ya Cloudflare kuwa hai:

1. Bonyeza picha yako juu kulia → **My Profile** → **API Tokens**
2. **Create Token** → tumia template **Edit zone DNS**
3. Chini kwenye **Zone Resources** chagua **Include** → **Specific zone** →
   `tech-spherewifi.xyz`
4. **Continue** → **Create Token** → nakili token (inaonekana mara moja tu)

Hifadhi token hiyo mahali salama. Utaihitaji kwenye hatua ya HTTPS.

Njia rahisi ya kuihifadhi hapa:

```powershell
notepad deploy\cloudflare-token.txt
```

Faili hilo lipo kwenye `.gitignore`, kwa hiyo halitaingia kwenye git.

---

## Hatua ya 3: HTTPS (baada ya seva kusakinishwa)

Kwenye seva:

```bash
sudo bash /var/www/techsphere/deploy/enable-https.sh
```

Script itakuuliza token na barua pepe, kisha itaomba cheti kwa njia ya
**DNS-01**. Njia hii inathibitisha umiliki kwa kuandika rekodi ya muda kwenye
Cloudflare, badala ya kutaka Let's Encrypt ifikie seva yako. Ndiyo maana
inawezekana kupata cheti halisi kwa seva iliyo ndani ya nyumba.

Cheti kinajipyaisha chenyewe kila baada ya siku 60.

Baada ya hapo, badilisha `mikrotik/login.html` iwe `https://tech-spherewifi.xyz`
(bila `:8000`), kisha:

```powershell
php scripts/upload-login-page.php
```

---

## Hatua ya 4: Ufikiaji wa mbali

Kwenye seva:

```bash
sudo bash /var/www/techsphere/deploy/enable-remote-admin.sh
```

Kisha **lazima** uweke kizuizi cha Cloudflare Access, vinginevyo mlango wa
admin unakuwa wazi kwa dunia:

```
Cloudflare → Zero Trust → Access → Applications → Add an application
  Type      : Self-hosted
  Subdomain : admin
  Domain    : tech-spherewifi.xyz
  Policy    : Allow → Emails → barua pepe yako
```

Baada ya hapo, kufungua `admin.tech-spherewifi.xyz` kutakutaka uthibitishe
barua pepe kwanza — hata kabla ya kuona ukurasa wa kuingia wa mfumo. Hiyo ni
tabaka mbili za ulinzi.

---

## Hatua ya 5: Tovuti ya biashara (baadaye)

Tutakapofika hapo, tovuti itakaa **Cloudflare Pages** — si kwenye seva yako.
Sababu ni moja kubwa: itabaki hewani hata umeme ukikatika ofisini.

Mpangilio utakuwa hivi:

- Mtu yeyote duniani akiandika `tech-spherewifi.xyz` → anaona tovuti ya
  biashara
- Mteja aliyeunganishwa na Abdul-Tech akiandika jina hilohilo → anaona
  ukurasa wa kununua vifurushi, kwa sababu MikroTik inamjibu kwa IP ya ndani

Jina moja, majibu mawili, kutegemea mtu yuko wapi.

---

## Kuthibitisha kuwa jina linafanya kazi

Kwenye simu iliyounganishwa na Abdul-Tech, fungua:

```
http://tech-spherewifi.xyz:8000
```

(Baada ya kuhamia seva mpya, `:8000` haitahitajika tena.)

Kwenye router, kuona rekodi zilizopo:

```powershell
php scripts/setup-domain-dns.php
```

Kama jina halisomeki kwenye simu fulani, mara nyingi sababu ni **Private DNS**
ya Android. Ukurasa wa kuingia wa router una njia mbadala ya IP, hivyo mteja
huyo bado ataingia — lakini ataona namba badala ya jina.
