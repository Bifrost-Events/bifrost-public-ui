# Playwright-feilanalyse

Bruk denne malen for å analysere en feilet Bifrost quality-kjøring.

## Input

- `quality/reports/results.json` eller HTML-rapport
- `quality/reports/test-results/` (screenshots, traces)
- `quality/screenshots/{env}/{app}/` hvis tatt
- Console-output fra terminal / CI-logg
- `QUALITY_ENV`, `QUALITY_APP`, commit/branch

## Analyser i denne rekkefølgen

### 1. Tilkobling og miljø

- `net::ERR_CONNECTION_REFUSED` → domene/host ikke tilgjengelig fra runner
- Feil `baseURL` i `quality/apps/*.yml` for valgt miljø
- Lokal CI kan ikke nå `.local`-domener uten self-hosted runner

### 2. HTTP-status

- `>= 500` → serverfeil; sjekk PHP-logg og backend
- `404` → route finnes ikke i `routes/web.php` eller feil path i manifest
- `/health` med `503` → backend nede, public UI kan fortsatt være OK

### 3. Cup/domene

- `data-cup-key` eller `meta[name="bifrost:cup"]` matcher ikke manifest `expected.cupKey`
- Sjekk `CupConfigLoader::HOST_MAP` og `HTTP_HOST`
- Tenant ikke resolvet fra backend → synlig feilmelding i header

### 4. Console og page errors

- Sammenlign med `CONSOLE_ERROR_ALLOWLIST` i `console-checks.ts`
- Nye `console.error` → frontend JS eller manglende asset
- `pageerror` → uncaught exception i browser

### 5. Visuelt

- Screenshot ved feil i `quality/reports/test-results/`
- Sammenlign header, cup-navn, dev-banner (kun development)

## Vanlige årsaker i Bifrost

| Symptom | Sannsynlig årsak |
|---------|------------------|
| Alle cuper feiler | Apache/backend ikke startet |
| Én cup feiler | Manglende vhost eller hosts-entry |
| Kun `/results` feiler | Backend API timeout |
| `titleContains` feiler | Cup-config `name` endret |
| Console: Failed to fetch | Backend utilgjengelig (vurder allowlist kun for kjent dev-tilfelle) |

## Anbefalt output

1. **Root cause** (1–2 setninger)
2. **Berørt lag** (manifest / PHP / backend / infra)
3. **Forslag til fix** (minimal endring)
4. **Skal testen endres?** (manifest vs. allowlist vs. app-fix)

## Begrensninger

- AI skal ikke automatisk merge, deploye eller endre produksjonsdata
- Foreslå konkrete filendringer, ikke bare generelle råd
