# Bifrost Quality

Playwright-basert kvalitetssikring for **bifrost-public-ui** – samme testsuite mot flere cup-domener og miljøprofiler.

## Miljøprofiler

Bifrost skiller tydelig mellom fem applikasjonsmiljøer. **Public-ui har ingen database** – den snakker med **bifrost-backend** via API. Database (`DB_DSN`, `DB_USER`, `DB_PASS`) konfigureres kun i backend; public-ui peker dit med `BACKEND_DOTENV`.

| Profil | `APP_ENV` | Database | Auto-reset | Playwright `QUALITY_ENV` | Bruk |
|--------|-----------|----------|------------|--------------------------|------|
| Lokal utvikling | `local-dev` | `jaktfeltkarusell_prod` (typisk prod-kopi) | Nei | — | Daglig manuell utvikling |
| Lokal quality | `local-quality` | `bifrost_quality_local` | Ja | `local-quality` | Playwright lokalt |
| Test (sky) | `test` | Egen, prod-lignende | Nei | `test` | Manuell demo på `test.*` |
| Staging (sky) | `staging` | `bifrost_quality_staging` | Ja | `staging` | CI / automatisk quality |
| Produksjon | `production` | Driftsdatabase | **Aldri** | `production` | Kun read-only smoke |

**Sikkerhetsregel:** `APP_ENV=production` nekter database-reset uansett `QUALITY_RESET_DATABASE`. Samme for `APP_ENV=test`.

### Env-filer

Kopier eksempelprofil til aktiv fil, eller pek med `BIFROST_DOTENV`:

```powershell
copy .env.local-dev.example .env
copy .env.local-dev.example ..\bifrost-backend\.env

# quality-kjøring (begge lag):
copy .env.local-quality.example .env.local-quality
copy .env.local-quality.example ..\bifrost-backend\.env.local-quality
```

| Fil | Beskrivelse |
|-----|-------------|
| `.env.local-dev.example` | Daglig utvikling |
| `.env.local-quality.example` | Lokal Playwright |
| `.env.test.example` | Sky `test.*` |
| `.env.staging.example` | Sky staging |
| `.env.production.example` | Produksjon |

`local-dev` og `local-quality` deler **samme lokale hostnames** men **forskjellig database**. Bytt `.env` (eller Apache `SetEnv BIFROST_DOTENV`) før quality-kjøring – ikke kjør begge samtidig mot samme Apache-instans uten vhost-separasjon.

Legacy: `APP_ENV=development` behandles som `local-dev`.

### Domener per miljø

| Cup | local-dev / local-quality | test | staging | production |
|-----|---------------------------|------|---------|------------|
| Slatlem | `slatlemcup.local` | `test.slatlemcup.no` | `staging.slatlemcup.no` | `slatlemcup.no` |
| Jaktfeltcup | `jaktfeltcup.local` | `test.jaktfeltcup.no` | `staging.jaktfeltcup.no` | `jaktfeltcup.no` |
| Namdal | `namdal.jaktfeltkarusell.local` | `test.namdal.jaktfeltkarusell.no` | `staging.namdal.jaktfeltkarusell.no` | `namdal.jaktfeltkarusell.no` |

Hosts-fil (typisk allerede på plass lokalt):

```
127.0.0.1 slatlemcup.local jaktfeltcup.local namdal.jaktfeltkarusell.local
```

`CupConfigLoader::HOST_MAP` støtter også forenklede alias (`slatlem.local`, `namdal.local`) om du vil bruke dem senere.

### Miljøvariabler (public-ui)

| Variabel | Beskrivelse |
|----------|-------------|
| `APP_ENV` | Profil (se tabell) |
| `APP_DEBUG` | Verbose feil |
| `APP_BASE_URL` / `APP_URL` | Base-URL for appen |
| `BACKEND_API_URL` | Backend API |
| `BACKEND_PATH` | Sti til `bifrost-backend` (quality-scripts) |
| `BACKEND_DOTENV` | Backend env-fil (f.eks. `.env.local-quality`) – **database ligger her** |
| `MAIL_MODE` | `log` / `off` / `real` |
| `PAYMENT_MODE` | `off` / `test` / `real` |
| `ALLOW_WRITES` | Skrivetilgang i appen |
| `ROBOTS_MODE` | `noindex` / `index` (meta robots i layout) |
| `QUALITY_RESET_DATABASE` | Tillat reset (kun local-quality/staging) |
| `QUALITY_SEED_DATABASE` | Tillat seed (kun local-quality/staging) |

PHP-sperrer: `App\Support\DatabaseResetGuard`  
CLI: `php quality/bin/quality-db.php status`

---

## Installasjon

```bash
cd bifrost-public-ui
npm install
npm run quality:install
```

## Kjøre tester

| Script | Beskrivelse |
|--------|-------------|
| `npm run quality:local` | Full suite mot `local-quality` |
| `npm run quality:staging` | Full suite mot staging |
| `npm run quality:test` | Smoke mot `test.*` |
| `npm run quality:prod-smoke` | Smoke mot produksjon |
| `npm run quality:screenshots` | Lokalt med suksess-screenshots |
| `npm run quality:report` | Åpne HTML-rapport |

### Velge miljø og cup

```powershell
# Én cup
cross-env QUALITY_ENV=local-quality QUALITY_APP=namdal npx playwright test

# Smoke mot test-miljø
npm run quality:test
```

| Variabel | Standard | Verdier |
|----------|----------|---------|
| `QUALITY_ENV` | `local-quality` | `local-quality`, `test`, `staging`, `production` |
| `QUALITY_APP` | `all` | `slatlem`, `jaktfeltcup`, `namdal`, `all` |
| `QUALITY_SCREENSHOTS` | `false` | `true` for suksess-screenshots |

---

## Database (quality-scripts)

Krever `bifrost-backend` og `bifrost-shared`. Quality-scripts leser `DB_DSN` fra **backend** env (via `BACKEND_DOTENV` i public-ui).

### Automatisk ved testkjøring

`npm run quality:local` og `quality:staging` kjører **global setup** før tester:

1. `reset` – drop/create quality-database  
2. `migrate` – `php bin/console migrate --greenfield` i backend  
3. `seed` – SQL fra `bifrost-shared/database/seeds/`

Styres av `database.prepareBeforeRun: true` i `quality/manifests/local-quality.yml` og `staging.yml`.

**Backend må bruke samme profil** (f.eks. kopier `.env.local-quality` i backend og restart API). Uten det feiler `database @database`-testen.

Hopp over prepare ved rask re-kjøring (database allerede seedet):

```powershell
cross-env QUALITY_SKIP_DB_PREPARE=true npm run quality:local
```

### Manuelt
```powershell
# 1. Aktiver quality-env i begge repos
copy .env.local-quality.example .env.local-quality
copy .env.local-quality.example ..\bifrost-backend\.env.local-quality

# 2. Sjekk sperrer (viser backend DB fra DB_DSN)
npm run quality:db:status

# 3. Reset + migrate + seed (kun bifrost_quality_local)
npm run quality:db:prepare
```

| Script | Handling |
|--------|----------|
| `quality:db:status` | Vis profil og om reset/seed er tillatt |
| `quality:db:reset` | DROP + CREATE database |
| `quality:db:migrate` | `php bin/console migrate --greenfield` i backend |
| `quality:db:seed` | Kjør seeds fra `bifrost-shared` |
| `quality:db:prepare` | reset → migrate → seed (samme som global setup) |

**Sperret** for `production` og `test`. Ekstra stopp for database-navn `jaktfeltkarusell_prod` og `bifrost`.

Smoke-test `database.spec.ts` verifiserer at `/health` rapporterer `database: ok` etter seed.

Seeds inkluderer `003_quality_local_hosts.sql` for `slatlem.local`, `namdal.local` og test/staging-domener.

---

## Struktur

```
quality/
  manifests/          # local-quality.yml, test.yml, staging.yml, production.yml
  apps/               # slatlem.yml, jaktfeltcup.yml, namdal.yml
  bin/quality-db.php  # Database CLI med sperrer
  tests/              # smoke, domain, visual
  support/            # manifest-loader, fixtures, console-checks
  .ai/prompts/        # AI-maler (senere)
```

## Legge til ny cup

1. `config/cups/{slug}.json` + `CupConfigLoader::HOST_MAP`
2. `quality/apps/{key}.yml` med `hosts` per miljø
3. `npm run quality:local` med `QUALITY_APP={key}`

## Legge til ny route

I `quality/apps/*.yml`:

```yaml
routes:
  - path: /calendar
    name: Stevnekalender
```

## Skjermbilder

Ved `local-quality` og `staging` tas **fullside-skjermbilder** automatisk for hver rute og domene-test.

| Hvor | Innhold |
|------|---------|
| HTML-rapport | Vedlegg per test (`npm run quality:report`) – klikk testen → **Attachments** |
| Filsystem | `quality/screenshots/{miljø}/{cup}/{rute}.png` |

Tving skjermbilder i andre miljøer:

```powershell
cross-env QUALITY_SCREENSHOTS=true npm run quality:local
```

Produksjon (`quality:prod-smoke`) tar **ikke** suksess-skjermbilder – kun ved feil (Playwright standard).


## CI

- **Release pipeline** (`.github/workflows/release-pipeline.yml`) – `main` → staging deploy → quality → test; `v*` → prod smoke
- **Quality** (`.github/workflows/quality.yml`) – `workflow_call` + manuell `workflow_dispatch`
- **Deploy (manual)** (`.github/workflows/deploy.yml`) – enkelt-miljø ved behov

Staging/test-domener må være deployet og nåbare fra GitHub runners.

**Staging CI:** `release-pipeline` kaller `POST /deploy/reset-staging` på staging API før Playwright (se [docs/staging-playwright.md](../docs/staging-playwright.md)). Secrets: `STAGING_RESET_URL`, `STAGING_DEPLOY_SECRET`.
