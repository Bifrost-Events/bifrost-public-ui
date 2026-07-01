# Staging Playwright – database reset

Automatiske Playwright-tester mot sky-staging trenger fersk database. GitHub Actions har **ikke** direkte MySQL-tilgang, og **HTTPS fra GitHub til ProISP er ustabilt** (timeout / HTTP/2-feil).

Løsningen er **FTP-trigger ved backend-deploy** + **server-side prosessering** (ProISP cron).

## Flyt (release-pipeline)

```
main push
  → backend staging deploy (FTP)
      → laster opp storage/framework/staging-reset.trigger
      → forsøker HTTPS reset (best effort, feiler ikke deploy)
  → vent 90s (cron / trigger)
  → public-ui staging deploy
  → quality (valgfri HTTPS-verifisering, fortsetter ved feil)
  → Playwright mot staging.jaktfeltcup.no
```

## Endepunkter (bifrost-backend)

| | |
|---|---|
| **Direkte reset** | `POST /deploy/reset-staging` |
| **FTP-trigger** | `GET /deploy/process-reset-trigger` |
| **Auto på health** | `GET /api/health` prosesserer pending trigger i staging |

Implementasjon: `StagingResetService`, `StagingResetTriggerService`, `public/deploy/*.php`.

### Sikkerhet (POST reset)

1. `APP_ENV=staging`
2. `HTTP_HOST` inneholder `staging`
3. `Authorization: Bearer <STAGING_DEPLOY_SECRET>`
4. `QUALITY_RESET_DATABASE=true` og `QUALITY_SEED_DATABASE=true`
5. Database-navn inneholder `staging` eller `ALLOW_STAGING_RESET=true`

FTP-trigger valideres med HMAC (`token` = `hash_hmac('sha256', queued_at, STAGING_DEPLOY_SECRET)`).

## Server-oppsett (ProISP)

1. Deploy backend til `staging.api.bifrostevents.no`
2. `.env` på server:

```env
APP_ENV=staging
STORAGE_DRIVER=pdo
DB_DSN=mysql:host=127.0.0.1;dbname=...;charset=utf8mb4
STAGING_DEPLOY_SECRET=<lang tilfeldig hemmelighet>
QUALITY_RESET_DATABASE=true
QUALITY_SEED_DATABASE=true
ALLOW_STAGING_RESET=true
```

3. **ProISP cron** (anbefalt — server-til-server, ikke avhengig av GitHub):

```cron
* * * * * curl -fsS --ipv4 --http1.1 https://staging.api.bifrostevents.no/deploy/process-reset-trigger
```

4. Public UI staging: `BACKEND_API_URL=https://staging.api.bifrostevents.no`

### Manuell test

```bash
# Direkte reset
curl -fsS --ipv4 --http1.1 -X POST "https://staging.api.bifrostevents.no/deploy/reset-staging" \
  -H "Authorization: Bearer <STAGING_DEPLOY_SECRET>" \
  -H "Accept: application/json"

# Trigger (etter FTP-upload eller manuell trigger-fil)
curl -fsS --ipv4 --http1.1 "https://staging.api.bifrostevents.no/deploy/process-reset-trigger"

# CLI på server
php bin/console process-reset-trigger
php bin/console staging-reset
```

## GitHub secrets

| Repo | Secret | Formål |
|------|--------|--------|
| `bifrost-backend` | `STAGING_DEPLOY_SECRET` | FTP-trigger + HTTPS reset etter deploy |
| `bifrost-public-ui` | `STAGING_DEPLOY_SECRET` | Valgfri verifisering i quality |
| `bifrost-public-ui` | `STAGING_RESET_URL` | Valgfri (default i workflow) |

## Lokal utvikling

`npm run quality:db:prepare` med `.env.local-quality` — ingen HTTP-reset nødvendig.

## Vedlikehold

SQL-filer i `bifrost-backend/database/` er kopi av `bifrost-shared`. Se `database/README.md`.
