# Staging Playwright – database reset via HTTPS

Automatiske Playwright-tester mot sky-staging nullstiller databasen via et **lukket endepunkt på staging API-serveren**. GitHub Actions har **ikke** direkte MySQL-tilgang.

## Flyt

```
main push → deploy backend + public-ui til staging
         → POST https://staging.api.bifrostevents.no/deploy/reset-staging
         → Playwright mot staging.jaktfeltcup.no (osv.)
```

## Endepunkt (bifrost-backend)

| | |
|---|---|
| **URL** | `POST /deploy/reset-staging` |
| **Implementasjon** | `public/deploy/reset-staging.php` |
| **Service** | `app/04-services/StagingResetService.php` |

### Sikkerhet

Endepunktet svarer bare når **alle** er oppfylt:

1. `APP_ENV=staging`
2. `HTTP_HOST` inneholder `staging` (f.eks. `staging.api.bifrostevents.no`)
3. `Authorization: Bearer <STAGING_DEPLOY_SECRET>` matcher `.env` på serveren
4. `QUALITY_RESET_DATABASE=true` og `QUALITY_SEED_DATABASE=true`
5. Database-navn inneholder `staging` **eller** `ALLOW_STAGING_RESET=true`
6. Blokkerte databaser: `jaktfeltkarusell_prod`, `bifrost`

Ved brudd: **403 Forbidden**. Feil metode: **405**. Feil under reset: **500** (ingen stack trace til klient når `APP_DEBUG=false`).

### Hva reset gjør

1. File lock (`storage/framework/staging-reset.lock`)
2. `SET FOREIGN_KEY_CHECKS=0` → drop alle tabeller → `SET FOREIGN_KEY_CHECKS=1`
3. Greenfield-migreringer (`database/migrations/`, uten backfill-filer)
4. Seeds (`database/seeds/`): tenants, cup-data, admin-bruker, quality-hosts

## Server-oppsett

På ProISP (`bifrostbackend/`, staging webroot):

1. Deploy ny backend (inkluderer `database/`, `public/deploy/`, `storage/`)
2. I `.env` / `.env.staging` på server:

```env
APP_ENV=staging
STORAGE_DRIVER=pdo
DB_DSN=mysql:host=127.0.0.1;dbname=bifrost_quality_staging;charset=utf8mb4
DB_USER=...
DB_PASS=...

STAGING_DEPLOY_SECRET=<lang tilfeldig hemmelighet>
QUALITY_RESET_DATABASE=true
QUALITY_SEED_DATABASE=true
ALLOW_STAGING_RESET=true
```

3. Public UI staging trenger fortsatt egen `.env.staging` med `BACKEND_API_URL=https://staging.api.bifrostevents.no`

### Manuell test

```bash
curl -fsS --ipv4 --http1.1 --connect-timeout 30 --max-time 120 \
  -X POST "https://staging.api.bifrostevents.no/deploy/reset-staging" \
  -H "Authorization: Bearer <STAGING_DEPLOY_SECRET>" \
  -H "Accept: application/json"
```

Bruk `--ipv4` og `--http1.1` — fra GitHub Actions kan IPv6 eller HTTP/2 mot ProISP/Varnish henge eller gi `PROTOCOL_ERROR`.

Forventet:
{
  "status": "ok",
  "environment": "staging",
  "message": "Staging database reset, migrated and seeded",
  "database": "bifrost_quality_staging",
  "migrations": 12,
  "seeds": 4
}
```

## GitHub Actions (bifrost-public-ui)

Repository secrets:

| Secret | Eksempel |
|--------|----------|
| `STAGING_RESET_URL` | `https://staging.api.bifrostevents.no/deploy/reset-staging` |
| `STAGING_DEPLOY_SECRET` | Samme verdi som på staging-server |
| `PLAYWRIGHT_BASE_URL` | Valgfri (manifest bruker per-cup URLer når `QUALITY_APP=all`) |

`release-pipeline.yml` kaller quality med `skip_db_prepare: 'false'` for staging. Da kjører `quality.yml`:

```yaml
- name: Reset staging database via HTTPS deploy endpoint
  run: |
    curl -fsS --ipv4 --http1.1 --connect-timeout 30 --max-time 120 \
      -X POST "$STAGING_RESET_URL" \
      -H "Authorization: Bearer $STAGING_DEPLOY_SECRET" \
      -H "Accept: application/json"
```

Bruk `--ipv4` og `--http1.1` — ProISP/Varnish kan gi treg IPv6-tilkobling eller `curl: (92) HTTP/2 … PROTOCOL_ERROR` fra GitHub Actions.

Deretter Playwright (uten lokal `quality-db.php prepare`).

## Lokal utvikling

Bruk fortsatt `npm run quality:db:prepare` med `.env.local-quality` – ingen HTTP-reset nødvendig lokalt.

## Vedlikehold

SQL-filer i `bifrost-backend/database/` er kopi av `bifrost-shared`. Oppdater ved migrasjonsendringer (se `database/README.md`).
