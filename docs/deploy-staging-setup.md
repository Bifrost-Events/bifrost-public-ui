# Server-oppsett: staging og test (ProISP / bifrostevents)

Sjekkliste for sky-miljøer før release-pipeline kan kjøre quality mot live URL-er.

## 1. ProISP webroots

Opprett filområder på webhotellet og oppdater rotstier i [Deploy-Admin](../../platformstandard/Deploy-Admin/docs/bifrost-deploy-environments.md) (`r1464900`–`r1464904` er placeholders).

## 2. Backend `.env` på server

Per filområde (beskyttes av `remoteProtect` i deploy-manifest):

| Miljø | Fil | `APP_ENV` | Database |
|-------|-----|-----------|----------|
| staging | `.env.staging` | `staging` | `bifrost_quality_staging` |
| test | `.env.test` | `test` | prod-lignende, ingen auto-reset |
| prod | `.env` | `production` | drift |

Mal: [bifrost-backend/.env.staging.example](../bifrost-backend/.env.staging.example), [.env.test.example](../bifrost-backend/.env.test.example)

## 3. Public UI `.env` på server

| Miljø | `BACKEND_API_URL` (eksempel) |
|-------|------------------------------|
| staging | `https://staging.api.bifrostevents.no` |
| test | `https://test.api.bifrostevents.no` |
| prod | `https://api.bifrostevents.no` |

Mal: [.env.staging.example](.env.staging.example), [.env.test.example](.env.test.example)

## 4. DNS og cup-domener

Pek staging/test-hosts til riktig miljø (se [quality/apps/](quality/apps/)):

- `staging.jaktfeltcup.no`, `staging.slatlemcup.no`, `staging.namdal.jaktfeltkarusell.no`
- `test.jaktfeltcup.no`, osv.

## 5. Database seed (tenant domains)

Etter migrasjon mot staging/test-DB:

```bash
# Fra bifrost-public-ui med BACKEND_DOTENV pekende på riktig backend-env
npm run quality:db:seed
# eller kjør manuelt: bifrost-shared/database/seeds/003_quality_local_hosts.sql
```

## 6. Synk Deploy-Admin secrets

```bash
cd Deploy-Admin
php scripts/sync-github-secrets.php Bifrost-Events/bifrost-backend
php scripts/sync-github-secrets.php Bifrost-Events/bifrost-public-ui
```

## 7. GitHub Secrets for staging database prepare (CI)

Quality resetter og seeder `bifrost_quality_staging` fra GitHub Actions før staging-tester. Legg inn **repository secrets** i `bifrost-public-ui`:

| Secret | Innhold |
|--------|---------|
| `STAGING_DB_HOST` | ProISP MySQL-host (fra kontrollpanel – ofte ikke `127.0.0.1` fra utsiden) |
| `STAGING_DB_USER` | Databasebruker med DROP/CREATE/INSERT på staging-DB |
| `STAGING_DB_PASS` | Passord |
| `STAGING_DB_NAME` | Valgfri (default `bifrost_quality_staging`) |

ProISP må tillate **remote MySQL** fra GitHub Actions (eller bruk tillatt IP-range / `%`-host for quality-brukeren). Uten dette feiler `quality:db:prepare` i CI.

`PAT_TOKEN` må også finnes (for checkout av privat `bifrost-shared`).

## 8. Verifiser manuelt

GitHub → Actions → **Quality (Playwright)** → `workflow_dispatch` med `staging` / `test` før du stoler på release-pipeline.
