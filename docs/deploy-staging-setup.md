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

## 7. GitHub Secrets for staging reset (CI)

Quality nullstiller staging-DB via **HTTPS** – ikke direkte MySQL fra Actions. Se [staging-playwright.md](staging-playwright.md).

| Secret | Innhold |
|--------|---------|
| `STAGING_RESET_URL` | `https://staging.api.bifrostevents.no/deploy/reset-staging` |
| `STAGING_DEPLOY_SECRET` | Samme som `STAGING_DEPLOY_SECRET` i backend `.env` på server |
| `PLAYWRIGHT_BASE_URL` | Valgfri override (standard: hosts fra `quality/apps/*.yml`) |

På staging-server: sett `STAGING_DEPLOY_SECRET`, `QUALITY_RESET_DATABASE=true`, `QUALITY_SEED_DATABASE=true` i backend `.env`.

## 8. Verifiser manuelt

GitHub → Actions → **Quality (Playwright)** → `workflow_dispatch` med `staging` / `test` før du stoler på release-pipeline.
