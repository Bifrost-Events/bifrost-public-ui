# Bifrost Release

Multi-repo release-flyt for Bifrost – manifest, gates og deploy via GitHub Actions.

Speilet etter [quality/](../quality): scripts og config i `release/`, npm-wrappers i rot-`package.json`.

## Release-sett (v1)

| Repo | Rolle |
|------|-------|
| bifrost-admin-core | API (+ Events-modul) – deployes først |
| bifrost-public-ui | Public cup-UI |
| bifrost-admin-ui | Admin – bruker backend |
| bifrost-homepage | Markedsføringsside – kun production |
| bifrost-arrangor-ui | Arrangørportal – `trackOnly` inntil FTP/DNS er klart (test-domener under) |
| bifrost-shared | Migrasjoner/seeds – track-only commit-pin |

## Staging = lokalt (ikke sky-deploy)

Sky-staging var ustabilt for Playwright i GitHub Actions. **Quality kjøres lokalt** mot `local-quality`-profilen (`.env.local-quality`, resetbar DB).

| Steg | Hvor | Kommando |
|------|------|----------|
| Quality | Lokalt | `npm run quality:local` (public + admin + arrangor*) |
| Test-demo | Sky (ProISP) | `release:deploy test` (backend → public-ui → admin-ui → arrangor-ui når `trackOnly` fjernes) |
| Produksjon | Sky (ProISP) | `release:deploy production` (+ homepage) |

\* Arrangør er `trackOnly` i `repos.yml` til ProISP/DNS/secrets er på plass. Kjør `quality:local` med cup-spesifikke arrangør-manifests.

### Arrangørportal (test, multi-cup)

Én FTP-deploy betjener begge cup-tenants via host-oppløsning:

| Cup | Arrangør test |
|-----|---------------|
| Jaktfeltcup | `https://test.arrangor.jaktfeltcup.no` |
| Namdal | `https://test.arrangor.namdal.jaktfeltkarusell.no` |

Fjern `trackOnly: true` under `arrangor-ui` i `release/config/repos.yml` når FTP-path, DNS og server-`.env` er verifisert.

Ingen FTP-deploy til staging i release-flyten. Staging brukes ikke i den nye flyten.

## Deploy-miljøer (GitHub)

| Miljø | Formål | Manuell godkjenning |
|-------|--------|---------------------|
| `test` | Manuell demo på `test.*` | Ja (før prod) |
| `production` | Drift | Ja (test må være godkjent) |

FTP-credentials synkes til GitHub Environments `test` og `production` per repo.

## Flyt

```text
release:create
  → npm run quality:local   # public-ui + admin (+ arrangor når klar)
  → release:approve quality
  → release:deploy test     # backend, public-ui, admin-ui
  → release:mark-smoke test
  → release:approve test
  → release:deploy production
  → release:mark-smoke production
```

Sjekk status når som helst:

```powershell
npm run release:check
npm run release:check -- -ReleaseId 2026-07-02-001
```

## Kommandoer

| npm script | Beskrivelse |
|------------|-------------|
| `release:create` | Opprett manifest med commit fra alle repo |
| `release:check` | Status, gates, neste steg |
| `release:approve` | Manuell godkjenning (quality / test) |
| `release:deploy` | Trigger `deploy-release.yml` (test eller production) |
| `release:mark-smoke` | Marker smoke OK i manifest etter grønn CI |
| `release:sync-secrets` | Synk `deploy-secrets.local.yml` → GitHub |

### Eksempler

```powershell
npm run release:create

npm run quality:local

npm run release:approve -- -ReleaseId 2026-07-02-001 -Type quality -By "Sjur" -Reason "Playwright local-quality OK"

npm run release:deploy -- -ReleaseId 2026-07-02-001 -Environment test

npm run release:approve -- -ReleaseId 2026-07-02-001 -Type test -By "Sjur" -Reason "Manuell test OK"

npm run release:deploy -- -ReleaseId 2026-07-02-001 -Environment production
```

## Lokal quality-oppsett

`npm run quality:local` kjører **public-ui + admin** (og arrangør når klar) mot samme backend.

| App | Lokal URL | Manifest |
|-----|-----------|----------|
| Jaktfeltcup / Namdal | `*.local` cup-domener | `quality/apps/*.yml` |
| Admin | `admin.bifrost.local` | `quality/apps/admin.yml` |
| Arrangør | `arrangor.jaktfeltcup.local`, `arrangor.namdal.jaktfeltkarusell.local` | `quality/apps/arrangor-jaktfeltcup.yml`, `arrangor-namdal.yml` |

```powershell
copy .env.local-quality.example .env.local-quality
copy .env.local-quality.example ..\bifrost-admin-core\.env.local-quality
# admin-ui: .env med BACKEND_API_URL=http://api.bifrost.local
npm run quality:db:prepare
npm run quality:local
```

## Secrets

1. Kopier [config/deploy-secrets.example.yml](config/deploy-secrets.example.yml) → `config/deploy-secrets.local.yml`
2. Fyll inn FTP og APP_URL for **test** og **production** (committes **ikke**)
3. `npm run release:sync-secrets`

Krever `gh auth login` med repo/admin-tilgang til **Bifrost-Events/** og **sjurivar/bifrost-homepage**.

## Manifest som kvittering (git)

Release-manifester under `release/releases/<id>/manifest.json` **committes til git** som sporbar kvittering:

- hvilke commits som inngikk i releasen
- hvem som godkjente quality og test
- status for deploy og smoke

Etter viktige steg, commit manifestet i `bifrost-public-ui`:

```powershell
git add release/releases/<release-id>/
git commit -m "release: <id> quality godkjent"
```

Manifestfiler telles ikke som «ulagrede kildekode-endringer» i `release:create` / `release:check`.

`release:deploy` **stopper** hvis manifestet ikke er committet og pushet til `origin` — deploy leser gates lokalt, men kvitteringen skal ligge på GitHub før sky-deploy starter.

## GitHub Actions

Workflow per repo: `.github/workflows/deploy-release.yml`

Inputs: `environment` (test|production), `release_id`, `ref`

## Eksempel output (`release:check`)

```text
Release: 2026-07-02-001

Repos:
  ✅ public-ui abc1234
  ✅ backend def5678
  ✅ shared 789abcd (track-only)

Status:
  ❌ lokal quality mangler
  ⏸ test deploy ikke startet
  ⛔ production ikke tillatt

Neste steg:
  npm run quality:local
  npm run release:approve -- -ReleaseId 2026-07-02-001 -Type quality ...
```

## E2E-verifisering

Se [docs/e2e-verification.md](docs/e2e-verification.md).
