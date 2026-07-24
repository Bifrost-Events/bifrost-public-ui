# Testplan: bifrost-arrangor-ui (Portal V3)

Verifisere at **arrangørportalen** kan opprette sesonger og sette sesongstruktur for cuper bootstrapet via admin-core.

Suiten kjøres som lokal staging (`QUALITY_ENV=local-quality`, app `arrangor-jaktfeltcup` / `arrangor-namdal`).

## Omfang

**Innenfor**

| Område | Stier |
|--------|--------|
| Auth | `/login`, `/logout` |
| Sesong opprett | `/sesonger/ny` → POST `/sesonger` → `/sesonger/{id}/struktur` |
| Sesongstruktur | GET/POST `/sesonger/{id}/struktur` |
| Runder | `/sesonger/{id}/undersoner/ny` (kun ved `structure_type=rounds`) |
| Arrangørsøknad | `/mine-organisasjoner/ny`, `/arrangor-soknader/ny`, send-inn |
| Godkjenning | `/sesonger/{id}/arrangor-soknader` (+ innstillinger / godkjenn) |
| Cup-oversikt | `/cup` |
| Public registrering | `{public-host}/auth/register` |

**Utenfor scope (egne planer senere)**

- Oppretting av stevner / påmeldinger etter godkjenning
- Sammenlagtregler
- Negativtesting

## Forutsetninger

| Krav | Detalj |
|------|--------|
| Host | f.eks. `http://arrangor.jaktfeltcup.local` → DocumentRoot `bifrost-arrangor-ui/public` |
| Database | Samme `bifrost_quality_local` som admin-core |
| Admin bootstrap | AC-03 må ha kjørt (apper, org, eier/admin, event spaces) — **uten** sesonger |
| Playwright | Kjør `npm run quality:flows:portal` (admin AC-03 + arrangør AP-01/02, `--workers=1`) |
| Testbruker | Cup-eier fra `PORTAL_CUPS` (f.eks. `owner.jaktfeltcup@bifrost.test` / `QualityCup123!`) |

## Kjøring

```powershell
cd bifrost-public-ui
npm run quality:db:prepare   # ved behov
npm run quality:flows:portal
# eller headed: npm run quality:flows:portal:headed
```

Kjører **kun** `admin-core.spec.ts` + `arrangor-portal.spec.ts` (AC-03 → AP-01/02).

Full staging-mappe (inkl. gamle public V2-flyter som ofte feiler):

```powershell
npm run quality:flows:headed
```

## Testtilfeller

### AP-01 — Cup-eier oppretter sesong

**Mål:** Innlogget cup-eier oppretter aktiv, offentlig sesong i arrangørportalen.

**Steg**

1. Logg inn på arrangør-host som cup-eier
2. Åpne `/sesonger/ny`
3. Fyll navn `{kortnavn} 2027`, kortnavn/sesongetikett `2027`, status **active**, synlighet **public**
4. Klikk **Lagre**

**Forventet resultat**

- Redirect til `/sesonger/{id}/struktur`
- Flash `.flash.success` med **Serie opprettet.**
- Overskrift **Sesongstruktur** synlig
- `/cup` viser sesongkort med **Sett struktur**

| App | Eier | Sesong |
|-----|------|--------|
| `arrangor-jaktfeltcup` | `owner.jaktfeltcup@bifrost.test` | `Jaktfeltcup 2027` |
| `arrangor-namdal` | `owner.namdal@bifrost.test` | `Namdal 2027` |

**Playwright:** `arrangor-portal.spec.ts` — «cup-eier kan opprette sesong»

---

### AP-02 — Cup-eier setter opp sesongstruktur

**Mål:** Etter sesongoppretting settes struktur tilpasset cup-typen.

**Felles:** Opprett sesong (som AP-01), deretter strukturvalg.

#### A — Jaktfeltcup (`arrangor-jaktfeltcup`)

1. Velg **Stevner gruppert i runder** (`#struct-rounds`) → **Lagre struktur**
2. Flash **Sesongstruktur lagret.** → `/cup`
3. Opprett **5 runder**: `Runde 1` … `Runde 5` via **Ny runde**

**Forventet**

- `/cup` viser sesong + «gruppert i runder»
- Alle fem runder synlige
- Lenke **Ny runde** synlig

#### B — Jaktfeltkarusell Namdal (`arrangor-namdal`)

1. Velg **Stevner direkte i sesongen** (`#struct-events`) → **Lagre struktur**
2. Flash **Sesongstruktur lagret.** → `/cup`

**Forventet**

- `/cup` viser «Stevner direkte i sesong»
- Lenke **Nytt stevne** synlig
- Lenke **Ny runde** finnes ikke

| App | Struktur |
|-----|----------|
| `arrangor-jaktfeltcup` | `rounds` + 5 runder |
| `arrangor-namdal` | `events` (stevner direkte) |

**Playwright:** `arrangor-portal.spec.ts` — «cup-eier setter opp sesongstruktur»

---

### AP-03 — Registrering, arrangørsøknad og godkjenning

**Mål:** Ny bruker registrerer seg (public-ui), søker som arrangør, og cup-eier godkjenner.

**Steg**

1. Cup-eier oppretter sesong + struktur (som AP-02) og åpner sesongen for søknader (`approval_required`) under `/sesonger/{id}/arrangor-soknader`
2. Ny bruker registrerer seg på public-host (`/auth/register`) → lander på `/min-side/profil`
3. Samme bruker logger inn i arrangørportal → oppretter organisasjon → ny søknad → **Send inn**
4. Cup-eier åpner søknaden og klikker **Godkjenn**

**Forventet**

- Flash **Utkast til søknad er lagret.** / **Søknaden er sendt inn.**
- Flash **Søknaden er godkjent.** og status **Godkjent**

**Playwright:** `arrangor-portal.spec.ts` — «bruker kan registrere seg, søke og få søknad godkjent»

---

## Testdata

| Felt | Verdi |
|------|--------|
| Cup-eiere | Se `PORTAL_CUPS` i `staging-helpers.ts` |
| Passord cup | `QualityCup123!` |
| Passord søker | `QualityArr123!` (unik e-post per kjøring) |
| Sesongår | `2027` (`CUP_SEASON_YEAR`) |
| Jaktfeltcup-runder | `Runde 1`…`Runde 5` (`JAKTFELTCUP_ROUND_COUNT`) |

**Seed-prinsipp:** Quality seedet kun roller + plattform-admin. Sesonger/struktur/søknader opprettes via UI.

## Kjente begrensninger

- Krever at admin AC-03 har opprettet event space for cupen
- Selve stevne-oppretting etter godkjenning dekkes ikke her
- Ingen opprydding mellom kjøringer (`quality:db:prepare`)

## Sporbarhet

| ID | Playwright-fil | Testnavn |
|----|----------------|----------|
| AP-01 | `quality/tests/staging/arrangor-portal.spec.ts` | cup-eier kan opprette sesong |
| AP-02 | samme | cup-eier setter opp sesongstruktur |
| AP-03 | samme | bruker kan registrere seg, søke og få søknad godkjent |
