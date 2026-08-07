# Malipo ya ClickPesa — tatizo na ufumbuzi

## Tatizo lililokuwepo

Malipo yalikuwa yanakubali mara chache na kugoma mara nyingi. Baada ya kuchunguza
kumbukumbu za miamala na kuuliza ClickPesa moja kwa moja, sababu zilikuwa mbili.

### 1. Kikomo cha maombi 100 kwa siku (sababu kubwa)

ClickPesa inarudisha ujumbe huu:

```
HTTP 429: Daily API limit reached. Complete your KYC to remove this limit. (100 calls per day)
```

Akaunti yako bado haijakamilisha **KYC**, hivyo ina kikomo cha maombi 100 kwa siku.

Mfumo ulikuwa unatumia takribani **maombi 49 kwa malipo moja** (1 ya kuanzisha
malipo, kisha 48 za kuuliza "mteja ameshalipa?" kila sekunde 2.5 kwa dakika mbili).
Kwa hesabu hiyo, **malipo mawili tu yalimaliza kikomo cha siku nzima** — ndiyo
maana ya mwanzo yalikubali, ya baadaye yakagoma hadi usiku wa manane.

### 2. M-Pesa na Halopesa hazijawashwa

Miamala yote iliyofanywa kwa namba za Vodacom (16) na Halotel (6) haikupokelewa
na ClickPesa hata mmoja. ClickPesa ilirudisha:

```
M-Pesa payment method is not active
```

Za Tigo/Yas pekee ndizo zilipokelewa. Kwa hiyo mteja wa Vodacom hakuweza kulipa
kabisa, hata siku kikomo hakijafika.

## Nilichorekebisha kwenye mfumo

| Nini | Kabla | Sasa |
|---|---|---|
| Maombi kwa malipo moja | ~49 | ~13 |
| Kuuliza hali ClickPesa | kila poll | jibu linahifadhiwa sekunde 10 |
| Poll kwenye simu ya mteja | kila sekunde 2.5 | kila sekunde 4 |
| Ombi lililokataliwa | linarudiwa mara 3 | halirudiwi |
| Malipo yakishindwa | yanabaki "pending" bila sababu | yanahifadhi sababu kamili |
| Ujumbe kwa mteja | "Malipo yameshindikana" | maelezo kamili na la kufanya |

Kwa mabadiliko haya, hata kwenye kikomo cha sasa cha 100, unaweza kuhudumia
malipo takribani **7 kwa siku** badala ya 2. Lakini hii bado haitoshi kwa
biashara — hatua za chini ni lazima.

## Unachotakiwa kufanya wewe (muhimu)

### Hatua ya 1 — Kamilisha KYC ClickPesa

Hii ndiyo inayoondoa kikomo cha 100.

1. Ingia kwenye dashibodi ya ClickPesa: <https://dashboard.clickpesa.com>
2. Nenda **Settings** > **KYC / Business Verification**
3. Pakia nyaraka: leseni ya biashara, TIN, kitambulisho cha mmiliki (NIDA),
   na taarifa za benki
4. Wasilisha na subiri idhini (kawaida siku 1–3 za kazi)

### Hatua ya 2 — Washa M-Pesa na Halopesa

Kwenye dashibodi hiyo hiyo, sehemu ya **Payment Methods** au **Collections**,
omba kuwashiwa:

- **M-Pesa (Vodacom)** — muhimu zaidi, ni mtandao mkubwa kuliko wote
- **Halopesa (Halotel)**
- **Airtel Money**

Kama hazionekani, wasiliana na ClickPesa moja kwa moja: support@clickpesa.com
au +255 736 000 000, useme unahitaji collection za M-Pesa ziwashwe kwa akaunti
yako ya biashara.

### Hatua ya 3 — Hakiki miamala 6 iliyokwama

Kuna miamala 6 iliyofika ClickPesa lakini hatujui kama mteja alilipa au la,
kwa sababu kikomo cha leo kimefika. **Kesho** endesha:

```bash
php scripts/reconcile-payments.php
```

Kama kuna aliyelipa, script itatengeneza voucher yake papo hapo na kuonyesha
`>>> MTEJA ALILIPA`.

## Zana za kuchunguza malipo

```bash
# Muhtasari: miamala kwa hali, ya mwisho 20, na makosa kwenye log
php scripts/diagnose-payments.php

# Mchanganuo kwa mtandao — inaonyesha mtandao upi unafanya kazi
php scripts/diagnose-payments-detail.php

# Safisha zilizokwama na hakiki zilizofika ClickPesa
php scripts/reconcile-payments.php

# Angalia njia zilizo hai kwa kila namba (tumia baada ya KYC kuthibitisha M-Pesa)
php scripts/test-clickpesa-methods.php
```

## Baada ya KYC — hatua ya kupunguza maombi zaidi

Ukishakuwa na anwani ya umma kupitia Cloudflare Tunnel (angalia `DOMAIN.md`),
tunaweza kusajili **webhook** ClickPesa. Hapo ClickPesa itatuarifu yenyewe malipo
yakikamilika, badala ya mfumo kuuliza mara kwa mara. Hiyo itapunguza maombi
kutoka ~13 hadi **2 kwa malipo moja**, na mteja atapata voucher haraka zaidi.
