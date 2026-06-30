# bifrost-public-ui

Offentlige cupsider og deltakergrensesnitt i Bifrost-plattformen.

Bygget med samme MVC-mønster som `bifrost-admin-ui` og `jaktfeltnamdalen` (se `bifrost-shared/reference/mvc-standard-from-jaktfeltnamdalen.md`).

**Ikke** det samme som deploy-admin eller bifrost-admin-ui.

## Lokal URL

| Miljø | URL |
|-------|-----|
| XAMPP Apache (anbefalt) | http://jaktfeltcup.local (eller `slatlemcup.local`, `namdal.jaktfeltkarusell.local`) |
| PHP innebygd server | http://localhost:8084 |

### Miljøprofiler

Se [quality/README.md](quality/README.md) for `local-dev`, `local-quality`, `test`, `staging` og `production`.

- **Daglig utvikling:** `.env.local-dev.example` → `.env`
- **Playwright:** `.env.local-quality.example` → `.env.local-quality` + `npm run quality:local`

## Avhengigheter

- PHP 8.1+
- Composer
- **`bifrost-backend`** kjørende på http://api.bifrost.local
- Database seed fra **`bifrost-shared`** (`001_local_tenants.sql`)

## Oppsett

```bash
cd C:\xampp\htdocs\bifrost\bifrost-public-ui
composer install
copy .env.example .env
```

Konfigurer Apache virtual host med document root `bifrost-public-ui/public` og host som matcher en tenant i backend (f.eks. `jaktfeltcup.local` eller `namdal.jaktfeltkarusell.local`).

`BACKEND_API_URL` i `.env` peker til backend (standard: `http://api.bifrost.local`).

## Utvikling

```bash
composer serve
```

Åpne http://localhost:8084

## Cup Experience (managed config)

Hver cup kan ha egen profil, layout og sponsoroppsett via JSON i `config/cups/`.

| Lokal host | Config-fil |
|------------|------------|
| `namdal.jaktfeltkarusell.local` | `namdal-jaktfeltkarusell.json` |
| `jaktfeltcup.local` | `nasjonal-15m-jaktfeltcup.json` |
| `slatlemcup.local` | `slatlem-cup.json` |
| *(annen host)* | `default.json` |

`CupConfigLoader` (`app/06-support/CupConfigLoader.php`) leser config fra `HTTP_HOST` (uten port). I utviklingsmodus vises aktiv config i banner øverst på siden.

### Legge til ny managed cup

1. Kopier `config/cups/default.json` til `config/cups/{slug}.json`.
2. Fyll inn `brand`, `layout`, `features`, `sponsors` og `content`.
3. Legg host-mapping i `CupConfigLoader::HOST_MAP`.
4. Registrer domene i backend (tenant/domener).
5. Legg logoer i `public/assets/cups/{slug}/` og sponsorlogoer i `public/assets/sponsors/`.

Synk fra jaktfeltnamdalen (samme filer som jaktfeltkarusell bruker):

```powershell
.\scripts\sync-assets-from-jaktfeltnamdalen.ps1
```

Se `public/assets/sponsors/README.md` for hvilke filer som forventes.

### Sponsorflater

Konfigureres under `sponsors` i cup-JSON:

- **placements:** `hero`, `frontpage_top`, `frontpage_middle`, `sidebar`, `results`, `signup`, `footer`
- **tiers:** `hovedsponsor`, `gull`, `sølv`, `bronse`, `samarbeidspartner`
- **presentation_level:** `minimal`, `standard`, `prominent`

Renderer: `app/02-view/partials/_sponsors.php`.

### Fremtidig flytting til database/admin

Branding, meny, innhold og sponsorer kan senere lagres i backend og redigeres i bifrost-admin. Inntil da er JSON kilde til sannhet for managed cuper.

Se `bifrost-shared/reference/cup-experience.md` for full konseptbeskrivelse.

## Struktur

```
public/index.php          Front controller
routes/web.php            Ruter
app/02-view/              Templates (layout + innhold)
app/03-controller/        HTTP-innganger
app/04-services/          BackendApiClient
app/06-support/           Router, Response, PublicView, TenantContext
config/                   app, backend, navigation, cups/*.json
```

## Tenant-oppslag

Public-ui resolver aktiv cup fra HTTP-host via `GET /api/tenant/resolve?host=` på bifrost-backend.

## Dokumentasjon

- `docs/public-ui-analysis.md` — kartlegging, veikart og menystruktur

## Deploy

- **Release pipeline** (`.github/workflows/release-pipeline.yml`) – `main` → staging → quality → test; `v*` → prod
- **Manuell deploy** (`.github/workflows/deploy.yml`) – `workflow_dispatch` per miljø
- Deploy-Admin: `app_folder` = `bifrostpublicui/`, miljøer `hjellum-no-bifrostevents-public{-staging,-test,}`

Krever `PAT_TOKEN` repository secret for `repository_dispatch` til backend. Se [docs/deploy-staging-setup.md](docs/deploy-staging-setup.md) og [Deploy-Admin bifrost-miljøer](../../platformstandard/Deploy-Admin/docs/bifrost-deploy-environments.md).
