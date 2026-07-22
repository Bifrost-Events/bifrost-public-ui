# Bifrost Quality

Playwright-basert kvalitetssikring for **hele Bifrost-plattformen** – public cup-UI, admin og arrangør – mot **bifrost-admin-core**.

Endringer i admin-core API kan påvirke alle lag; `quality:local` kjører derfor smoke mot flere apper i samme suite.

## Miljøprofiler

Bifrost skiller tydelig mellom fem applikasjonsmiljøer. **Public-ui har ingen database** – den snakker med **bifrost-admin-core** via API. Database (`DB_DSN`, `DB_USER`, `DB_PASS`) konfigureres i admin-core; public-ui peker dit med `BACKEND_DOTENV`.

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
# Daglig utvikling (prod-kopi DB):
copy .env.local-dev.example .env
copy .env.local-dev.example ..\bifrost-admin-core\.env
# Valgfritt personlig overlay (gitignored):
copy .env.local-dev.example .env.local

# Playwright (resetbar quality-DB – rør ikke .env):
copy .env.local-quality.example .env.local-quality
copy .env.local-quality.example ..\bifrost-admin-core\.env.local-quality
# Events-modul bruker admin-core .env.local-quality (fallback) — egen fil i modules/events er valgfri
```

| Fil | Beskrivelse |
|-----|-------------|
| `.env.local-dev.example` | Daglig utvikling |
| `.env.local-quality.example` | Lokal Playwright |
| `.env.test.example` | Sky `test.*` |
| `.env.staging.example` | Sky staging |
| `.env.production.example` | Produksjon |

`local-dev` og `local-quality` deler **samme lokale hostnames** men **forskjellig database**.

| Lag | Daglig utvikling | Playwright |
|-----|------------------|------------|
| PHP (Apache) | `.env` + `.env.local` | `.env.local-quality` (midlertidig via activate) |
| Playwright CLI / quality-db | — | `.env.local-quality` via `BIFROST_DOTENV` |

`npm run quality:local` aktiverer Apache-profil midlertidig og **gjenoppretter dev-.env** når testene er ferdige.

```powershell
# Anbefalt: én kommando (activate → test → deactivate)
npm run quality:local

# Manuell activate/deactivate (f.eks. feilsøking i nettleser etter test):
npm run quality:activate
# Restart Apache/XAMPP
npm run quality:deactivate
```

Alternativt: `SetEnv BIFROST_DOTENV .env.local-quality` i Apache vhost (da bruker også manuell surfing quality-DB).

Global setup sjekker at `http://api.bifrost.local/api/health` rapporterer `app_env=local-quality` og `database_name=bifrost_quality_local` før tester starter.

Legacy: `APP_ENV=development` behandles som `local-dev`.

### Domener per miljø

| Cup | local-dev / local-quality | test | staging | production |
|-----|---------------------------|------|---------|------------|
| Slatlem | `slatlemcup.local` | `test.slatlemcup.no` | `staging.slatlemcup.no` | `slatlemcup.no` |
| Jaktfeltcup | `jaktfeltcup.local` | `test.jaktfeltcup.no` | `staging.jaktfeltcup.no` | `jaktfeltcup.no` |
| Namdal | `namdal.jaktfeltkarusell.local` | `test.namdal.jaktfeltkarusell.no` | `staging.namdal.jaktfeltkarusell.no` | `namdal.jaktfeltkarusell.no` |

Hosts-fil (typisk allerede på plass lokalt):

```
127.0.0.1 slatlemcup.local jaktfeltcup.local namdal.jaktfeltkarusell.local admin.bifrost.local arrangor.jaktfeltcup.local arrangor.namdal.jaktfeltkarusell.local arrangor.slatlemcup.local
```

### Plattform-portaler (admin / arrangør)

| App | Manifest | Lokal URL | Smoke |
|-----|----------|-----------|-------|
| Admin | `quality/apps/admin.yml` | `admin.bifrost.local` | `/health`, `/login` |
| Arrangør Jaktfeltcup | `quality/apps/arrangor-jaktfeltcup.yml` | `arrangor.jaktfeltcup.local` | Login, bli-arrangor, stevner |
| Arrangør Namdal | `quality/apps/arrangor-namdal.yml` | `arrangor.namdal.jaktfeltkarusell.local` | Login, bli-arrangor, stevner |

**Moduler oppe:** `npm run quality:smoke` kjører `quality/tests/smoke/modules-up.spec.ts` mot public (`public_ui`), admin (`admin_ui`) og arrangør (`arrangor_ui`) – `/health` + entry-side. Samme sjekk inngår i `quality:test` / `quality:prod-smoke`.

Admin-ui trenger egen `.env` med `BACKEND_API_URL` mot samme backend som quality (f.eks. `http://api.bifrost.local`).

Cup-manifester bruker `kind: cup` (standard); admin/arrangør bruker `kind: portal` uten `cupKey`.

`CupConfigLoader::HOST_MAP` støtter også forenklede alias (`slatlem.local`, `namdal.local`) om du vil bruke dem senere.

### Miljøvariabler (public-ui)

| Variabel | Beskrivelse |
|----------|-------------|
| `APP_ENV` | Profil (se tabell) |
| `APP_DEBUG` | Verbose feil |
| `APP_BASE_URL` / `APP_URL` | Base-URL for appen |
| `BACKEND_API_URL` | Backend API |
| `BACKEND_PATH` | Sti til `bifrost-admin-core` (quality-scripts) |
| `BACKEND_DOTENV` | Admin-core env-fil (f.eks. `.env.local-quality`) – **database ligger her** |
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
| `npm run quality:smoke` | Rask sjekk: public, admin og arrangør er oppe (`/health` + entry) |
| `npm run quality:flows` | Staging-flyter lokalt (bruker, påmelding, admin-core) |
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

# Staging-flyter (bruker, påmelding, admin-core)
npm run quality:flows
```

### Staging-flyter (`quality/tests/staging/`)

Kjøres lokalt mot `local-quality`. Krever Apache/vhosts og `npm run quality:db:prepare` (minimal seed: roller + admin; øvrig data via UI der det er omskrevet).

| Spec | Dekker |
|------|--------|
| `participant-user.spec.ts` | Registrering, bruker+deltaker, flere deltakere, innlogging |
| `competition-signup.spec.ts` | Påmelding (V2 — krever fortsatt fixture/seed til omskriving) |
| `admin-core.spec.ts` | Admin-core V3: login, core-nav, bootstrap cuper (apper/domener/org + eier/admin) ([testplan](docs/test-plans/admin-core.md)) |
| `arrangor-portal.spec.ts` | AP-01 sesong; AP-02 struktur; AP-03 registrering/søknad/godkjenning |

Arrangør testes med `npm run quality:local` mot `arrangor.jaktfeltcup.local` og `arrangor.namdal.jaktfeltkarusell.local`.

```powershell
# Kun bruker/deltaker mot namdal
cross-env QUALITY_ENV=local-quality QUALITY_APP=namdal npx playwright test quality/tests/staging/participant-user.spec.ts
```

| Variabel | Standard | Verdier |
|----------|----------|---------|
| `QUALITY_ENV` | `local-quality` | `local-quality`, `test`, `staging`, `production` |
| `QUALITY_APP` | `all` | `jaktfeltcup`, `namdal`, `admin`, `arrangor-jaktfeltcup`, `arrangor-namdal`, `all` |
| `QUALITY_SCREENSHOTS` | `false` | `true` for suksess-screenshots |

---

## Database (quality-scripts)

Krever `bifrost-admin-core`. Quality-scripts leser `DB_DSN` fra **admin-core** env (via `BACKEND_DOTENV` i public-ui).

### Automatisk ved testkjøring

`npm run quality:local` og `quality:staging` kjører **global setup** før tester:

1. `reset` – drop/create quality-database  
2. `migrate` – `php bin/console migrate` i admin-core **og** `modules/events` (tomt schema; ingen demo-data)  
3. `seed` – minimal quality-seed via `SEEDS_PATH=quality/database/seeds` (standardroller + én admin-bruker). **Ikke** admin-core demo-apps/orgs og **ikke** events-demo (cuper/stevner).

Styres av `database.prepareBeforeRun: true` i `quality/manifests/local-quality.yml` og `staging.yml`.

**Prinsipp:** Staging oppretter organisasjoner, applikasjoner, cuper osv. via UI. Seed er kun oppstartsgrunndata.

**Admin-core må bruke samme profil** (f.eks. kopier `.env.local-quality` i admin-core og restart Apache). Uten det feiler `database @database`-testen.

Hopp over prepare ved rask re-kjøring (database allerede seedet):

```powershell
cross-env QUALITY_SKIP_DB_PREPARE=true npm run quality:local
```

### Manuelt
```powershell
# 1. Aktiver quality-env
copy .env.local-quality.example .env.local-quality
copy .env.local-quality.example ..\bifrost-admin-core\.env.local-quality
# Events-modul bruker admin-core .env.local-quality (fallback) — egen fil i modules/events er valgfri

# 2. Sjekk sperrer (viser admin-core DB fra DB_DSN)
npm run quality:db:status

# 3. Reset + migrate + seed (kun bifrost_quality_local)
npm run quality:db:prepare
```

| Script | Handling |
|--------|----------|
| `quality:db:status` | Vis profil og om reset/seed er tillatt |
| `quality:db:reset` | DROP + CREATE database |
| `quality:db:migrate` | `php bin/console migrate` i admin-core + events |
| `quality:db:seed` | Minimal seed: roller + `quality.admin@bifrost.test` |
| `quality:db:prepare` | reset → migrate → seed (samme som global setup) |

**Sperret** for `production` og `test`. Ekstra stopp for database-navn `jaktfeltkarusell_prod` og `bifrost`.

Smoke-test `database.spec.ts` verifiserer at `/health` rapporterer `database: ok` etter seed.

Seeds inkluderer `003_quality_local_hosts.sql` for `slatlem.local`, `namdal.local` og test/staging-domener.

---

## Struktur

```
quality/
  manifests/          # local-quality.yml, test.yml, staging.yml, production.yml
  apps/               # jaktfeltcup, namdal, admin, arrangor-*.yml
  docs/test-plans/    # Tekstlige testplaner (admin-core, …)
  bin/quality-db.php  # Database CLI med sperrer
  tests/              # smoke, domain, staging
  support/            # manifest-loader, fixtures, staging-helpers
  .ai/prompts/        # AI-maler
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

- **Deploy release** (`.github/workflows/deploy-release.yml`) – kun `workflow_dispatch` via `npm run release:deploy`
- **Quality** (`.github/workflows/quality.yml`) – manuell `workflow_dispatch` mot test/staging/production ved behov

Lokal quality (`npm run quality:local`) er standard før test-deploy. Sky-staging brukes ikke i release-flyten.
