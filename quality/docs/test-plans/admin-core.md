# Testplan: bifrost-admin-core

Verifisere at **Bifrost Admin Core** (V3 admin shell + core-CRUD) fungerer lokalt mot quality-profil:

- Innlogging og dashboard
- Navigasjon til hovedområder under `/core/*`
- Opprettelse av applikasjon, domene og organisasjon

Suiten kjøres som lokal staging (`QUALITY_ENV=local-quality`, app `admin`).

## Omfang

**Innenfor**

| Område | Stier |
|--------|--------|
| Auth | `/login` |
| Dashboard | `/` |
| Core lister | `/core/organizations`, `/core/persons`, `/core/users`, `/core/memberships`, `/core/applications`, `/core/domains`, `/core/roles` |
| Write-flyter | Opprett portal-apper/domener, cup-organisasjoner, eier-/admin-brukere, medlemskap og event spaces |

**Utenfor scope (egne planer senere)**

- Events-modul i admin (`/events/*`) — driftsflyt i arrangør (se [arrangor-portal.md](arrangor-portal.md))
- Sesongoppretting — arrangørportal AP-01
- Public-ui auth / påmelding
- Negativtesting (ugyldig login, valideringsfeil) — ikke i første runde
- Produksjonsauth uten `ADMIN_AUTH_BYPASS`

## Forutsetninger

| Krav | Detalj |
|------|--------|
| Host | `http://admin.bifrost.local` → DocumentRoot `bifrost-admin-core/public` |
| Profil | `APP_ENV=local-quality`, `.env.local-quality` i admin-core |
| Database | `bifrost_quality_local` via `npm run quality:db:prepare` (migrate admin-core + events; seed kun roller + én admin) |
| Auth bypass | `ADMIN_AUTH_BYPASS=true` (kun local-quality/dev) |
| Testbruker | Se [Testdata](#testdata) — opprettes av quality-seed, ikke demo-apps/orgs |
| Playwright-home | `bifrost-public-ui` (quality-suite), project `admin` |

## Kjøring

```powershell
cd bifrost-public-ui
npm run quality:db:prepare   # ved behov
cross-env QUALITY_ENV=local-quality QUALITY_APP=admin npx playwright test quality/tests/staging/admin-core.spec.ts
```

Eller via `npm run quality:flows` / `quality:local` (kjører alle staging-apper).

## Testtilfeller

### AC-01 — Innlogging og dashboard

**Mål:** Admin kan logge inn og lander på dashboard.

**Steg**

1. Åpne `/login`
2. Fyll inn e-post og passord (testdata)
3. Klikk **Logg inn**

**Forventet resultat**

- URL er `/` (ikke lenger `/login`)
- Sidetittel / toppfelt viser **Bifrost Admin**
- Hovedoverskrift **Dashboard** er synlig
- Dashboard-tall reflekterer seed: **Roller ≥ 7**, **Personer ≥ 1**, **Brukere ≥ 1**

**Playwright:** `admin-core.spec.ts` — «login leads to dashboard»

---

### AC-02 — Core-navigasjon (lesesmoke)

**Mål:** Alle hovedmenyer under core laster uten serverfeil og viser forventet overskrift.

**Steg**

1. Logg inn (som AC-01)
2. Besøk hver URL under og kontroller overskrift:

| URL | Forventet overskrift |
|-----|----------------------|
| `/core/organizations` | Organisasjoner |
| `/core/persons` | Personer |
| `/core/users` | Brukere |
| `/core/applications` | Applikasjoner |
| `/core/domains` | Domener |
| `/core/roles` | Roller |

3. På `/core/roles`: bekreft at minst én standardrolle er synlig (f.eks. nøkkel `org_owner` eller etikett **Organisasjonseier**)

**Forventet resultat**

- HTTP-status &lt; 500 for alle sider
- Overskrifter som over
- Roller-siden viser seedet rolle

**Playwright:** `admin-core.spec.ts` — «core navigation pages load»

---

### AC-03 — Bootstrap cuper (apper, domener, org, eier/admin)

**Mål:** Admin oppretter de tre public-portalene med eierorganisasjon og brukere som eier/administrator.

**Steg**

1. Logg inn
2. For hver cup under:
   - Opprett applikasjon + public-domene + arrangør-domene
   - Opprett eierorganisasjon
   - Opprett eier-bruker (`org_owner`) og admin-bruker (`org_admin`)
   - Gi plattform-admin `org_admin` på cup-orgen (kreves for event space-skjema)
   - Opprett event space: status **active**, synlighet **public**, UI-labels Cup/Sesong/Runde/Stevne

| Cup | `application_key` | Public-domene | Arrangør-domene | Organisasjon | Event space slug |
|-----|-------------------|---------------|-----------------|--------------|------------------|
| Jaktfeltcup | `jaktfeltcup` | `jaktfeltcup.local` | `arrangor.jaktfeltcup.local` | Nasjonal 15m Jaktfeltcup | `portal-jaktfeltcup` |
| Namdal | `jaktfeltkarusell-namdal` | `namdal.jaktfeltkarusell.local` | `arrangor.namdal.jaktfeltkarusell.local` | Jaktfeltkarusell Namdal | `portal-namdal` |
| Slatlem | `slatlem` | `slatlemcup.local` | `arrangor.slatlemcup.local` | Slatlem Cup | `portal-slatlem` |

Eier/admin-e-poster: `owner.*` / `admin.*` @ `bifrost.test` (se helpers). Passord: `QualityCup123!`

**Forventet resultat**

- Apper/domener/orgs/brukere/medlemskap/event spaces opprettes med suksessmeldinger
- `/events/spaces` viser alle tre spaces
- Dashboard: Applikasjoner=3, Domener=6, Organisasjoner=3, Medlemskap=9, Brukere/Personer ≥ 7

**Playwright:** `admin-core.spec.ts` — «admin can bootstrap cups with orgs and owners»

**Merk:** Sesonger opprettes i arrangørportalen (AP-01), ikke her.

---

### AC-04 / AC-05 — (dekket av AC-03)

Domene-, organisasjons-, bruker-, medlemskaps- og event space-opprettelse dekkes i AC-03.

---

## Testdata

| Felt | Verdi |
|------|--------|
| Plattform-admin | `quality.admin@bifrost.test` / `QualityAdmin123!` (quality-seed) |
| Cup-brukere | Se AC-03 / `PORTAL_CUPS` — opprettes via UI |
| Passord cup | `QualityCup123!` |
| Event space labels | Cup/Cuper, Sesong/Sesonger, Runde/Runder, Stevne/Stevner |

**Seed-prinsipp:** Quality seedet kun standardroller + plattform-admin. Cup-apper, organisasjoner, eier/admin og event spaces opprettes via UI. Sesonger: se [arrangor-portal.md](arrangor-portal.md).

**Merk:** Legacy `admin@bifrost.local` / `public.demo@bifrost.test` brukes ikke i quality-staging.

## Kjente begrensninger

- `ADMIN_AUTH_BYPASS` forenkler lokal auth; tester ikke passordvalidering i produksjon
- Sesonger/runder/stevner opprettes ikke her — arrangør-staging (AP-01+)
- Ingen opprydding mellom kjøringer (`quality:db:prepare`)

## Sporbarhet

| ID | Playwright-fil | Testnavn |
|----|----------------|----------|
| AC-01 | `quality/tests/staging/admin-core.spec.ts` | login leads to dashboard |
| AC-02 | samme | core navigation pages load |
| AC-03 | samme | admin can bootstrap cups with orgs and owners |
| AC-04/05 | — | dekket av AC-03 |

Erstatter gammel V2-test `admin-cup.spec.ts` (`/platform/cuper/new`).
