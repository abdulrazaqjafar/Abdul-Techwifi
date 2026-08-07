# Kuanza matumizi rasmi

## Kilichofanyika tarehe 07/08/2026

Data yote ya majaribio imeondolewa na mfumo umeanza upya kutoka sifuri.

**Kilichofutwa:**

| Kitu | Kilikuwa | Sasa |
|---|---|---|
| Miamala ya malipo | 43 | 0 |
| Vocha | 11 | 0 |
| Mauzo | 5 (TSh 29,000) | 0 (TSh 0) |
| Malipo | 5 | 0 |
| Wateja | 6 | 0 |
| Kumbukumbu za shughuli | 72 | 0 |
| Vocha kwenye router | 1 | 0 |

**Kilichobaki salama:** vifurushi 5, akaunti za watumiaji 3, mipangilio ya
biashara, mipangilio ya router, na profiles 6 za vifurushi kwenye router.

Namba za rekodi zimeanza upya, kwa hiyo mauzo ya kwanza halisi yatakuwa #1.

**Nakala ya usalama:** `storage/backups/kabla-ya-reset-20260807-014154.sql`
(usiifute — ndiyo njia pekee ya kurudisha data ya majaribio kama itahitajika).

## Mipangilio iliyorekebishwa kwa biashara halisi

| Mpangilio | Kilikuwa | Sasa | Kwa nini |
|---|---|---|---|
| Saa za eneo | UTC | Africa/Dar_es_Salaam | mauzo yalikuwa yanarekodiwa saa 3 nyuma, ripoti za kila siku zingekuwa si sahihi |
| Lugha ya awali | Kiingereza | Kiswahili | wateja waone Kiswahili wanapofungua ukurasa |
| Maelezo ya makosa | yanaonekana | yamezimwa | yalikuwa yanaweza kuonyesha password za database kwa mteja |

Angalia mipangilio hii wakati wowote:

```bash
php scripts/check-new-activity.php
```

## Kabla ya kuanza kuuza

### 1. Hakikisha hakuna intaneti ya bure

```bash
php scripts/check-free-access.php
```

Inatakiwa iishie na: `Hakuna njia ya bure. Kila mtu lazima alipe au atumie vocha.`

Script hii inaangalia kama "trial" imewashwa kwenye router, kama kuna mtu
anayeruhusiwa kupita bila kulipa, na kama kuna vocha za kudumu zisizo na
kikomo cha muda.

### 2. Malipo ya ClickPesa

**Hii bado haijakamilika.** Soma [MALIPO.md](MALIPO.md). Kwa muhtasari:

- Akaunti yako ina kikomo cha maombi 100 kwa siku kwa sababu KYC
  haijakamilishwa — kamilisha KYC kuondoa kikomo
- M-Pesa na Halopesa hazijawashwa — mteja wa Vodacom hawezi kulipa kabisa

Hadi hapo, tumia **vocha za muuzaji** kwa wateja wa Vodacom.

### 3. Ingia upya kwenye mfumo

Vipindi vya kuingia vimefutwa, kwa hiyo utahitaji kuingia upya kwa jina lako
la kawaida.

## Vocha zinafanya kazi hata PC ikizimwa

Awali, vocha ilikuwa inasajiliwa kwenye router **dakika ile mteja anapounganisha**,
na usajili huo unapitia mfumo wa Laravel. Maana yake: PC ikizimwa, hata vocha
uliyomuuzia mteja jana haikufanya kazi.

Sasa vocha inasajiliwa kwenye router **mara inapotengenezwa**. Mteja anaweza
kuiweka moja kwa moja kwenye ukurasa wa router na kuingia, bila mfumo kuhusika.

Kilichobadilika kiufundi: jina la mtumiaji kwenye router sasa ni **msimbo wa
vocha wenyewe**, si `voucher_xxxx` kama zamani. Sababu ni kwamba fomu ya router
inatuma msimbo ulioandikwa kama jina na pia kama nenosiri.

### Kinachofanya kazi PC ikizimwa

| Kitu | Bila PC |
|---|---|
| Mteja aliyekwisha kuunganishwa | Anaendelea |
| Mteja mwenye vocha kuingia upya | Anaingia |
| Cashier kuuza vocha aliyonayo mkononi | Inafanya kazi |
| Kununua kifurushi kwa M-Pesa | Haifanyi kazi |
| Kutengeneza vocha mpya | Haifanyi kazi |
| Dashibodi na ripoti | Hazipatikani |

### Kusawazisha PC ikirudi

Mteja akitumia vocha bila mfumo, database haijui mara moja. Amri hii
inarudisha taarifa hizo:

```bash
php artisan vouchers:sync-router
```

Inafanya mambo matano: inarudisha vocha zilizotumika bila mfumo, inasukuma
vocha zilizotengenezwa router ikiwa imezimwa, inahamisha vocha za zamani ziwe
na majina ya msimbo (bila kukata mtu anayetumia sasa), inaweka vipima-muda kwa
vocha zilizoanza kutumika bila mfumo, na inafunga zile zilizoisha muda.

Kuna pia chaguo la kusafisha:

```bash
php artisan vouchers:sync-router --prune
```

Hii inaondoa routerni vocha zilizoghairiwa au zilizoisha muda, pamoja na
vipima-muda visivyo na akaunti. **Haiondoi** vocha inayotumika sasa.

> **Muhimu:** ratiba inafanya kazi tu kama `php artisan schedule:work`
> inaendeshwa kwenye dirisha lingine. Ukihamia seva ya Ubuntu, cron inawekwa
> yenyewe na hutahitaji kufanya hivi.

## Muda wa vocha unahesabiwaje

Hili ni jambo la muhimu kwa mapato yako, na lilihitaji marekebisho.

RouterOS 6 ina kipimo kimoja tu, `limit-uptime`, kinachohesabu **muda wa kuwa
mtandaoni** badala ya siku za kalenda. Kwa kipimo hicho pekee, mteja
anayeingia saa tatu kila siku angefanya vocha ya "siku moja" idumu **siku
nane**, na ya siku 30 idumu **miezi miwili au mitatu**.

Sasa mteja anapoanza kutumia vocha, mfumo unaweka **kipima-muda kwenye router
yenyewe** (jina lake `exp-<MSIMBO>`). Muda ukifika, router inafuta akaunti,
inakata kipindi, kisha kipima-muda kinajifuta. Hili linatokea **hata mfumo
ukiwa umezimwa**, kwa sababu ni router inayohesabu.

Mteja akianza kutumia vocha wakati PC imezimwa, kipima-muda kinawekwa PC
ikirudi, na muda unaanza kuhesabiwa kuanzia hapo. Vilevile, vocha
zilizoisha muda wakati PC ilipokuwa imezimwa zinafungwa mara moja PC ikirudi.

Kuona hali ya sasa:

```bash
php scripts/check-expiry-status.php
```

### Saa ya router

Mpango huu wote unategemea saa ya router, na hAP ac lite haina betri ya saa.
Kwa hiyo NTP imewashwa ili saa ijirekebishe yenyewe umeme ukikatika. Router
ikijengwa upya au saa ikionekana kupotea:

```bash
php scripts/fix-router-clock.php
```

## Kazi za kila siku

```bash
# Angalia hali ya hotspot na router
php scripts/verify-hotspot.php

# Sawazisha vocha na router (endesha PC ikirudi baada ya kuzimwa)
php artisan vouchers:sync-router

# Angalia muda uliobaki kwa kila vocha inayotumika
php scripts/check-expiry-status.php

# Angalia malipo: yaliyofanikiwa, yaliyoshindwa, na sababu zake
php scripts/diagnose-payments.php

# Hakiki malipo yaliyokwama na tengeneza vocha kwa waliolipa
php scripts/reconcile-payments.php
```

## Zana nyingine

```bash
# Data iliyopo kwenye kila jedwali
php scripts/inspect-data.php

# Wateja na vifaa vilivyounganishwa sasa
php scripts/inspect-customers-router.php

# Mchanganuo wa malipo kwa mtandao
php scripts/diagnose-payments-detail.php
```

## Kuanzisha upya tena (kama itahitajika)

```bash
php scripts/export-database.php          # chukua nakala kwanza
php scripts/reset-system.php --force     # kisha futa
```

Bila `--force` script haifuti chochote — ni kinga ya kuzuia ajali.
