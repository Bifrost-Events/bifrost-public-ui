# Public-UI — analyse og veikart

**Dato:** 2026-06-25  
**Status:** Kartlegging av eksisterende kodebase og erstatningsstrategi for jaktfeltnamdalen  
**Viktig:** Public-UI er **ikke** deploy-admin og **ikke** bifrost-admin-ui. Det er den offentlige og deltakerrettede brukerflaten i Bifrost-plattformen.

---

## 1. Sammendrag

| Område | Status i dag |
|--------|--------------|
| `bifrost-public-ui` | Tomt repo — kun `README.md` og `.gitignore` |
| Funksjonell referanse | `jaktfeltnamdalen` (PHP MVC, server-rendret) |
| Arkitekturreferanse | `bifrost-admin-ui` (MVC-skjelett, backend-klient) |
| Backend-API for publikum/deltaker | Nesten ikke bygget — kun tenant-oppslag og health |
| Domenemodell | Definert i `bifrost-shared` (auth, deltakerprofil, påmelding) |

**Konklusjon:** Public-UI skal bygges som nytt prosjekt i `bifrost-public-ui`, med jaktfeltnamdalen som funksjonell mal og admin-ui som teknisk mal. Ingen stor omskriving av eksisterende kode nå — neste steg er å bootstrappe MVC-skjelettet og bygge ut backend-API parallelt.

---

## 2. Bifrost-plattformen — kontekst

Bifrost er et workspace med separate git-repos:

| Repo | Rolle | Relevans for public-ui |
|------|-------|------------------------|
| `bifrost-public-ui` | Offentlig cup- og deltaker-UI | **Målprosjekt** |
| `bifrost-admin-ui` | Cup-/plattformadmin | Ikke public-ui; teknisk mal |
| `bifrost-backend` | JSON API | Datakilde (må utvides) |
| `bifrost-shared` | Migrasjoner, domenedok, MVC-standard | Felles kontrakter |
| `bifrost-homepage` | Statisk markedsføringsside | Branding-referanse, ikke cup-UI |
| `jaktfeltnamdalen` | Dagens publikums- og deltakerløsning | Funksjonell mal å erstatte |

Auth-klienten `bifrost-public` er registrert i auth-databasen (`bifrost-shared/database/migrations/auth_001_core_schema.sql`).

---

## 3. Eksisterende public-ui (`bifrost-public-ui`)

### 3.1 Innhold i repoet

```
bifrost-public-ui/
├── README.md          # «Offentlige cupsider og deltakergrensesnitt»
├── .gitignore         # Forventer PHP/Composer-prosjekt
└── docs/
    └── public-ui-analysis.md   # Dette dokumentet
```

**Mangler:** `app/`, `public/`, `routes/`, `config/`, `composer.json`, `.env.example`, deploy-manifest, CI.

### 3.2 Hva som kan brukes videre

Fra repoet selv: ingenting utover intensjonen i README og `.gitignore`-konvensjoner.

Fra resten av Bifrost-plattformen (gjenbruk, ikke kopier blindt):

| Kilde | Hva | Hvordan |
|-------|-----|---------|
| `bifrost-admin-ui` | MVC-skjelett | `public/index.php`, `bootstrap.php`, `Router`, `Response`, `EnvLoader`, `Config`, `Session` |
| `bifrost-admin-ui` | `BackendApiClient` | HTTP-klient med cookie-håndtering — trim admin-metoder, legg til public |
| `bifrost-admin-ui` | Deploy-mønster | `deploy-manifest.json` + GitHub Actions FTP-workflow |
| `bifrost-shared` | MVC-standard | `reference/mvc-standard-from-jaktfeltnamdalen.md` |
| `bifrost-shared` | Auth/deltaker-modell | `reference/auth-design.md`, migrasjoner `bifrost_008+` |
| `jaktfeltnamdalen` | Funksjonell UX | Views, flyter, navigasjon, påmelding, resultater |

---

## 4. Referansearkitektur: bifrost-admin-ui

Admin-ui er **ikke** public-ui, men viser målarkitekturen for Bifrost PHP-apper.

### 4.1 Request-flyt

```
public/index.php
  → app/06-support/bootstrap.php
  → routes/web.php → Router::dispatch()
  → Controller → BackendApiClient / View-helper
  → Response::view() | Response::redirect() | Response::json()
```

### 4.2 Lagdeling

| Lag | Plassering | Ansvar |
|-----|------------|--------|
| View | `app/02-view/` | PHP-templates |
| Controller | `app/03-controller/` | HTTP-innganger |
| Service | `app/04-services/` | Backend-klient, use cases |
| Support | `app/06-support/` | Router, Response, Auth, Config |
| Config | `config/` | App, backend-URL, meny |

### 4.3 Viktige forskjeller public-ui må ha

| Admin-ui | Public-ui |
|----------|-----------|
| `AdminView` med sidebar + tenant-velger | Offentlig layout med cup-branding |
| `AdminMenu` fra `config/admin-menu.php` | Public-nav fra tenant/cup-konfig |
| `AuthService::canAccessAdmin()` | Participant-auth, ikke admin-gate |
| `LoginController` → backend avviser ikke-admins | Backend må tillate deltaker-innlogging |
| Server-side kall til `/api/admin/*` | Kall til `/api/public/*` og `/api/participant/*` (må bygges) |

---

## 5. Referansefunksjonalitet: jaktfeltnamdalen

Jaktfeltnamdalen er dagens komplette publikums- og deltakerløsning. Public-ui skal etter hvert dekke samme behov, men mot Bifrost-backend og `event_*`-tabeller.

### 5.1 Mappestruktur (relevant del)

```
jaktfeltnamdalen/
├── public/index.php              # Front controller
├── routes/web.php                # Alle HTML-ruter
├── api/v2/routes.php             # JSON API (token, ikke session)
├── app/
│   ├── 02-view/                  # ★ Primært erstatningsmål for public-ui
│   │   ├── layout.php            # Master layout + inline CSS/JS
│   │   ├── partials/             # SEO, hero, kort
│   │   └── admin/                # Eget admin-layout (ikke public-ui)
│   ├── 03-controller/
│   ├── 04-services/
│   ├── 05-repositories/
│   └── 06-support/               # InstallationProfile, Router, Auth, Session
├── config/
│   ├── installations/namdal.php  # Multi-tenant profil
│   └── navigation/namdal.json    # Menystruktur
└── storage/images/               # Logoer, sponsorer
```

### 5.2 Routing — gruppering

#### Offentlig (ingen innlogging)

| Rute | Funksjon |
|------|----------|
| `/` | Hjem — velkomst, statistikk, sponsorer |
| `/om` | Om cupen (faner: arrangør, publikum, deltaker) |
| `/sponsor` | Sponsorside |
| `/calendar` | Stevnekalender (liste) |
| `/results`, `/results/{id}` | Resultatoversikt og stevneresultat |
| `/sammenlagt` | Cup-sammenlagt |
| `/arkiv/*` | Historiske sesonger og stevner |
| `/avtaler/*` | Vilkår og personvern |
| `/auth/login`, `/auth/register` | Innlogging og registrering |
| `/api/geocode`, `/api/postnummer` | Hjelpe-API for adresse |

#### Krever innlogging

| Rute | Funksjon |
|------|----------|
| `/dashboard` | Min side (profil, oppgaver, arrangør, cup-admin) |
| `/onboarding` | Valg etter registrering |
| `/participants` | Mine deltakere/skyttere |
| `/calendar/{id}` | Stevnedetalj + påmelding |
| `POST /calendar/{id}/register*` | Påmelding, avmelding, flytting |
| `/organizers` | Arrangøradministrasjon (innlogget) |

#### Admin/privilegert (ikke public-ui v1)

| Rute | Funksjon |
|------|----------|
| `/admin/*` | Plattformadmin |
| `/okonomi` | Økonomi (arrangør/cup-admin) |
| `/apps/stevneadmin/*` | Stevneadmin på stedet |
| `/apps/offline/*` | Offline PWA |
| `/resultkontroll/*` | Resultatgodkjenning |

### 5.3 Views — hva som bør overføres til public-ui

**Offentlig visning (høy prioritet):**

| View | Beskrivelse |
|------|-------------|
| `layout.php` | Header, nav, brand CSS, auth-modal |
| `home-content.php` | Forside |
| `about-content.php` | Om-siden |
| `sponsor-content.php` | Sponsorer |
| `calendar-content.php` | Stevnekalender |
| `calendar-show-content.php` | Stevnedetalj og påmelding |
| `results-content.php` | Resultatliste |
| `results-show-content.php` | Stevneresultat |
| `season-standings-content.php` | Sammenlagt |
| `archive-*.php` | Arkiv |
| `auth-login/register-*.php` | Auth (full side + modal-fragmenter) |
| `404-content.php` | Feilside |

**Innlogget deltaker (medium prioritet):**

| View | Beskrivelse |
|------|-------------|
| `dashboard-content.php` | Min side (profil-fane) |
| `onboarding-content.php` | Onboarding etter registrering |
| `participants-content.php` | Deltakerprofiler |

**Utenfor public-ui v1:** `admin/*`, `apps-stevneadmin`, `economy-*`, `cupadmin-*`, `stevner-*` (arrangørverktøy flyttes til admin-ui eller egne apper).

### 5.4 CSS/JS-tilnærming i jaktfeltnamdalen

- **Ingen frontend-build** for hovedsiden
- ~400 linjer inline CSS i `layout.php` med CSS-variabler fra `InstallationProfile::cssVariables()`
- ~200 linjer inline JS for mobilmeny og auth-modal (fetch mot `/auth/*`)
- Font Awesome CDN når cup-profil krever ikoner
- Separate mini-apper: `public/apps-stevneadmin/`, `public/offline/` (egne CSS/JS)

**Anbefaling for public-ui:** Start med samme mønster (server-rendret + inline/minimal JS) for rask porting. Vurder gradvis uttrekk av CSS til egen fil når layout stabiliseres.

### 5.5 Auth-flyt (jaktfeltnamdalen)

```
Registrering: GET/POST /auth/register → auth-service → /onboarding
Innlogging:   Modal eller /auth/login → POST /auth/login → session
OAuth:        /auth/callback → token exchange → redirect
Profil:       POST /dashboard/profile/update → auth-service + lokal deltaker
Session:      Lazy start på offentlige GET; alltid på POST og auth-ruter
```

Drivere: `oauth` (prod), `local`, `fake` (test).

### 5.6 Påmeldingsflyt (jaktfeltnamdalen)

```
1. Bruker ser /calendar (offentlig liste)
2. Klikk stevne → /calendar/{id} (krever innlogging)
3. Velg deltaker (egen eller barn) + slot/figur
4. POST /calendar/{id}/register | register-new | register-existing-modal
5. Vis egen påmelding på stevnesiden og i dashboard
```

Forretningsregler: `AdvanceRegistration::isOpenForPublic()`, publisert-flagg, reserverte plasser, klasse/klubb.

### 5.7 Resultatvisning (jaktfeltnamdalen)

```
/results          → stevner med resultater
/results/{id}     → rangert tabell, hold-for-hold scoring
/sammenlagt       → cup-sammenlagt for aktiv sesong
/arkiv/*          → frosne JSON-arkiver fra tidligere sesonger
```

---

## 6. Backend-API i dag (`bifrost-backend`)

### 6.1 Tilgjengelig nå (uten auth)

| Metode | Path | Formål |
|--------|------|--------|
| GET | `/api/health` | Health + DB-status |
| GET | `/api/tenants` | Alle tenants |
| GET | `/api/tenants/{id}` | Én tenant |
| GET | `/api/tenant/resolve?host=` | Tenant fra hostname |

`GET /api/tenant/resolve` er **nøkkel** for multi-tenant cup-sider (samme kodebase, ulike domener).

### 6.2 Auth i dag — ikke egnet for deltakere

| Metode | Path | Problem |
|--------|------|---------|
| POST | `/api/auth/login` | Krever `SystemAdmin` eller `CupAdmin` — deltakere avvises |
| GET | `/api/auth/me` | Fungerer for innlogget bruker, men deltakere kan ikke logge inn |

### 6.3 Mangler (må bygges for public-ui)

- `GET /api/public/seasons`, `/competitions`, `/results`, `/standings`
- `POST/GET /api/participant/profile` (`event_participant_profiles`)
- `POST /api/participant/signups` (`event_signups`)
- `GET /api/participant/my-signups`
- Participant-vennlig login (eller eget auth-endepunkt)
- Branding/bootstrap-endepunkt (tema, logo, features per tenant)

Databasen har allerede `event_participant_profiles` (migrasjon `bifrost_008`). Påmeldingstabell `event_signups` er planlagt i `auth-design.md`, men ikke implementert i API.

---

## 7. Domenemodell (Bifrost)

Fra `bifrost-shared/reference/auth-design.md`:

| Konsept | Tabell | Beskrivelse |
|---------|--------|-------------|
| Global bruker | `auth_users` | Én konto på tvers av cuper |
| Deltakerprofil | `event_participant_profiles` | Bruker som deltaker i én tenant/cup |
| Påmelding | `event_signups` (mål) | Påmelding til ett stevne |
| CupAdmin | `auth_tenant_admin_access` | Admin — **ikke** public-ui |
| Organizer | `organization_*` | Arrangør via org-medlemskap |

**Viktig:** Participant er ikke en adminrolle. Deltakelse = profil + påmelding, ikke rolle i auth-systemet.

Mapping fra jaktfeltnamdalen:

| Legacy (jaktfelt) | Bifrost (mål) |
|-------------------|---------------|
| `jaktfelt_participants` | `event_participant_profiles` |
| `jaktfelt_competition_signup_figures` | `event_signups` |
| `jaktfelt_competition_results` | `event_results` (planlagt) |
| `jaktfelt_user_profiles` | `auth_users` |

---

## 8. Erstatning av jaktfeltnamdalen

### 8.1 Hva public-ui erstatter

| Område | Erstatning |
|--------|------------|
| Offentlige sider | Hjem, om, sponsor, kalender, resultater, sammenlagt, arkiv |
| Auth UX | Registrering, innlogging, logout, onboarding |
| Deltakerflate | Profil, mine deltakere, mine påmeldinger |
| Påmelding | Stevnevisning + påmelding/avmelding |
| Branding | Per-tenant tema, logo, navigasjon |

### 8.2 Hva som **ikke** er public-ui

| Område | Hvor det hører hjemme |
|--------|----------------------|
| Cup-/plattformadmin | `bifrost-admin-ui` |
| Stevneadmin på stedet | Egen app (som `apps/stevneadmin`) |
| Offline PWA | Egen app (som `public/offline`) |
| Økonomi/rapporter | Admin eller arrangørverktøy |
| Resultatkontroll | Arrangør/admin-flyt |
| `/admin/*` datavedlikehold | Admin-ui eller backend-verktøy |

### 8.3 Overgangsfase

Realistisk migrering:

1. **Fase 1:** Bootstrap public-ui MVC, tenant-resolve per host, statisk landing
2. **Fase 2:** Offentlig kalender/resultater mot nye backend-API
3. **Fase 3:** Participant-auth + profil + påmelding
4. **Fase 4:** Dashboard/min side, onboarding
5. **Fase 5:** Arkiv, sponsorer, avansert branding
6. **Fase 6:** Avvikle jaktfeltnamdalen for valgt cup/tenant

Under overgang kan jaktfeltnamdalen kjøre parallelt med data-migrering via `bifrost-shared`-migrasjoner.

---

## 9. Funksjonskrav per brukertype

### 9.1 Publikum (uten innlogging)

| Funksjon | Beskrivelse | Referanse i jaktfeltnamdalen |
|----------|-------------|------------------------------|
| Forside | Velkomst, cup-info, statistikk | `/` |
| Stevnekalender | Kommende stevner, filtrering | `/calendar` |
| Resultater | Stevner med resultater | `/results`, `/results/{id}` |
| Sammenlagt | Cup-stilling | `/sammenlagt` |
| Om cupen | Regler, arrangørinfo, for publikum | `/om` |
| Sponsorer | Sponsorvisning | `/sponsor` |
| Arkiv | Tidligere sesonger | `/arkiv/*` |
| Vilkår | Personvern, brukervilkår | `/avtaler/*` |

### 9.2 Innlogget bruker

| Funksjon | Beskrivelse | Referanse |
|----------|-------------|-----------|
| Registrering | Ny Bifrost-konto | `/auth/register` |
| Innlogging | Modal eller egen side | `/auth/login` |
| Onboarding | Velg deltaker/arrangør etter registrering | `/onboarding` |
| Min profil | Navn, telefon, adresse | `/dashboard?tab=profil` |
| Logg ut | Avslutt session | `/auth/logout` |

### 9.3 Deltaker/skytter

| Funksjon | Beskrivelse | Referanse |
|----------|-------------|-----------|
| Deltakerprofil | `event_participant_profiles` per tenant | `/participants` |
| Flere deltakere | Egne barn/andre profiler | Dashboard deltakere-fane |
| Påmelding | Meld seg på stevne | `/calendar/{id}` + POST |
| Mine påmeldinger | Oversikt over egne påmeldinger | Dashboard / egen side |
| Klasse/klubb | Deltakerkategori og klubb | Profil + påmeldingsskjema |
| Claim | Knytte eksisterende deltaker-ID | `/participants/{id}/claim` |

### 9.4 Ikke i scope nå

- Bilder, kommentarer, chat/community
- Arrangørverktøy (cup-admin, stevneadmin, økonomi)
- Offline-app

---

## 10. Påmelding — anbefalt design

### 10.1 Prinsipper

1. **Offentlig liste, privat handling** — `/calendar` er åpen; påmelding krever innlogging
2. **Profil før påmelding** — bruker må ha (eller opprette) `event_participant_profiles` for tenant
3. **Stevne som enhet** — påmelding knyttes til konkurranse + slot/figur (som i dag)
4. **Tydelig status** — åpen/lukket/full, egen påmelding fremhevet

### 10.2 Foreslått flyt i public-ui

```
/calendar                    → liste (offentlig)
/calendar/{id}               → detalj (offentlig visning, påmelding bak auth)
/calendar/{id}/meld-pa        → redirect til login om nødvendig, deretter påmelding
/mine-pameldinger             → innlogget oversikt
/deltaker/profil              → vedlikehold av deltakerprofil
/deltaker/ny                  → opprett ny deltaker (f.eks. barn)
```

### 10.3 API-kontrakt (mål)

```
GET  /api/public/competitions/{id}           → stevneinfo, påmeldingsstatus
GET  /api/participant/profile                → egen profil for tenant
PUT  /api/participant/profile                → oppdater profil
POST /api/participant/signups                → meld på
DELETE /api/participant/signups/{id}         → avmeld
GET  /api/participant/signups                → mine påmeldinger
```

---

## 11. Resultater — anbefalt design

### 11.1 Offentlig visning

| Side | Innhold |
|------|---------|
| `/results` | Stevner med publiserte resultater, gruppert etter sesong/runde |
| `/results/{id}` | Rangert liste, poeng, hold-for-hold der relevant |
| `/sammenlagt` | Cup-sammenlagt for aktiv sesong |
| `/arkiv/sesong/{id}/sammenlagt` | Historisk sammenlagt |

### 11.2 Dataprinsipper

- Kun `is_published` / tilsvarende flagg vises offentlig
- Telefonnummer og sensitiv info skjules (som i jaktfeltnamdalen premietrekning)
- Resultatredigering og godkjenning er **ikke** i public-ui

### 11.3 API-kontrakt (mål)

```
GET /api/public/competitions?has_results=1
GET /api/public/competitions/{id}/results
GET /api/public/seasons/{id}/standings
```

---

## 12. Skille offentlig vs innlogget funksjonalitet

### 12.1 Arkitektur

```
┌─────────────────────────────────────────────────────────┐
│                    Public layout                         │
│  ┌─────────────────────────────────────────────────┐    │
│  │ Offentlig nav: Hjem | Kalender | Resultater | Om │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─ Offentlig innhold (ingen session nødvendig) ─────┐  │
│  │  Kalenderliste, resultater, om, sponsor, arkiv     │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─ Auth-gated (session / BIFROSTSESSID) ────────────┐  │
│  │  Påmelding, min profil, mine deltakere, påmeldinger│  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  Header: «Logg inn» / brukermeny (Min profil, Logg ut)  │
└─────────────────────────────────────────────────────────┘
```

### 12.2 Implementasjonsregler

| Regel | Beskrivelse |
|-------|-------------|
| Lazy session | Offentlige GET uten cookie trenger ikke session (spar ressurser) |
| Auth-gate i controller | Som jaktfeltnamdalen — ikke global middleware |
| Tydelig CTA | «Logg inn for å melde deg på» på stevnesider |
| Separat user-meny | Innlogget bruker får egen dropdown, ikke blandet i hovednav |
| Ingen admin-lenker | Admin/cup-admin peker til `bifrost-admin-ui` (annen URL) |
| Tenant-kontekst | All data scopet til tenant fra hostname |

### 12.3 URL-struktur

| Prefix | Tilgang | Eksempel |
|--------|---------|----------|
| `/` | Offentlig | `/`, `/om`, `/calendar` |
| `/auth/*` | Offentlig (muterer session) | `/auth/login` |
| `/min-side/*` eller `/dashboard` | Innlogget | `/min-side/profil` |
| `/deltaker/*` | Innlogget | `/deltaker/profil` |
| `/admin` | **Ikke public-ui** | Egen app |

---

## 13. Multi-tenant og branding

### 13.1 Jaktfeltnamdalen-mønster (referanse)

- `APP_INSTALLATION` → `config/installations/{namdal|cup}.php`
- `InstallationProfile` styrer tema, features, tekster, logo
- `config/navigation/{profile}.json` styrer meny
- `DomainCupResolver` filtrerer sesonger etter HTTP-host

### 13.2 Bifrost-mønster (mål)

- `GET /api/tenant/resolve?host=` → tenant-id, navn, domener
- Tenant-spesifikk config i backend eller statisk per deploy
- CSS-variabler injisert i layout fra tenant-branding
- Feature flags per tenant (arkiv, sponsor, etc.)

---

## 14. Foreslått menystruktur for public-ui

### 14.1 Hovedmeny (offentlig)

Vises for alle besøkende. Rekkefølge og synlighet kan styres per tenant/feature flag.

| # | Label | URL | Feature flag | Merknad |
|---|-------|-----|--------------|---------|
| 1 | Hjem | `/` | alltid | Forside |
| 2 | Stevnekalender | `/calendar` | alltid | Kommende stevner |
| 3 | Resultater | `/results` | alltid | Stevneresultater |
| 4 | Sammenlagt | `/sammenlagt` | `standings` | Cup-stilling |
| 5 | Arkiv | `/arkiv` | `archive` | Tidligere sesonger |
| 6 | Sponsorer | `/sponsor` | `sponsor_page` | Valgfritt |
| 7 | Om | `/om` | alltid | Info, regler, kontakt |

### 14.2 Brukermeny (innlogget — header dropdown)

| # | Label | URL | Krav |
|---|-------|-----|------|
| 1 | Min profil | `/min-side/profil` | innlogget |
| 2 | Mine deltakere | `/min-side/deltakere` | innlogget |
| 3 | Mine påmeldinger | `/min-side/pameldinger` | innlogget |
| — | *(skille)* | | |
| 99 | Logg ut | `/auth/logout` | innlogget |

### 14.3 Header for anonyme besøkende

| Element | Handling |
|---------|----------|
| «Logg inn» | Åpner auth-modal eller `/auth/login` |
| «Registrer deg» | `/auth/register` |

### 14.4 Sekundærnavigasjon på «Min side»

Dashboard-lignende faner (kun innlogget):

| Fane | URL | Innhold |
|------|-----|---------|
| Profil | `/min-side/profil` | Brukerdata (navn, telefon, adresse) |
| Deltakere | `/min-side/deltakere` | Skytterprofiler |
| Påmeldinger | `/min-side/pameldinger` | Aktive og tidligere påmeldinger |

**Merk:** Arrangør-, cup-admin- og økonomi-faner fra jaktfeltnamdalen hører **ikke** i public-ui. De peker til admin-ui eller egne verktøy.

### 14.5 Fotmeny (valgfritt)

| Label | URL |
|-------|-----|
| Personvern | `/avtaler/privacy_policy` |
| Brukervilkår | `/avtaler/terms_of_use` |
| Kontakt | `/om#kontakt` |

### 14.6 Sammenligning med jaktfeltnamdalen

Dagens `config/navigation/namdal.json` har: Sponsorer, Stevnekalender, Resultater, Sammenlagt, Om (+ Økonomi for privilegerte). Public-ui foreslått meny er tilsvarende, men:

- Legger til eksplisitt «Mine påmeldinger»
- Flytter arrangør/cup-admin/økonomi ut av public-ui
- Beholder feature flags (`sponsor_page`, `archive`, `standings`)

---

## 15. Teknisk veikart (ikke implementere nå)

### 15.1 Bootstrap public-ui

1. Kopier MVC-skjelett fra `bifrost-admin-ui` (uten admin-views)
2. Lag `PublicView` (erstatter `AdminView`) med offentlig layout
3. Implementer tenant-resolve via `BackendApiClient`
4. Legg til `deploy-manifest.json` + CI

### 15.2 Backend-utvidelser (parallelt)

1. Participant-login (eller fjern admin-krav på login for public-klient)
2. Public read-API: competitions, results, standings
3. Participant write-API: profile, signups
4. Branding/bootstrap-endepunkt

### 15.3 Første brukbare MVP

Offentlig: hjem + kalender + resultater for én tenant  
Innlogget: registrering, profil, enkel påmelding

---

## 16. Risiko og avhengigheter

| Risiko | Konsekvens | Tiltak |
|--------|------------|--------|
| Backend-API mangler | Public-ui kan ikke vise ekte data | Bygg public API parallelt |
| Participant-auth mangler | Ingen innlogging for deltakere | Utvid `AuthController` i backend |
| Data i legacy `jaktfelt_*` | Migrering nødvendig | Bruk `bifrost_011` backfill |
| Feature-paritet med jaktfeltnamdalen | Brukere savner funksjoner | Prioriter MVP, fase inn resten |
| Multi-tenant branding | Feil cup vises | Test tenant-resolve grundig |

---

## 17. Vedlegg — filreferanser

### bifrost-public-ui
- `README.md` — prosjektbeskrivelse

### bifrost-admin-ui (teknisk mal)
- `public/index.php`, `routes/web.php`
- `app/04-services/BackendApiClient.php`
- `app/06-support/Router.php`, `Response.php`, `AdminView.php`
- `config/admin-menu.php`
- `deploy-manifest.json`

### bifrost-backend
- `routes/web.php` — alle API-ruter
- `app/03-controller/AuthController.php` — admin-only login

### bifrost-shared
- `reference/mvc-standard-from-jaktfeltnamdalen.md`
- `reference/auth-design.md`
- `reference/database-naming.md`
- `database/migrations/bifrost_008_event_participant_profiles.sql`

### jaktfeltnamdalen (funksjonell mal)
- `routes/web.php` — alle ruter
- `app/02-view/layout.php` — layout og styling
- `config/navigation/namdal.json` — dagens meny
- `config/installations/namdal.php` — tenant-profil
- `app/03-controller/CompetitionCalendarController.php` — påmelding
- `app/03-controller/ResultsController.php` — resultater
- `app/03-controller/ParticipantController.php` — deltakere
- `app/03-controller/AuthController.php` — auth

---

*Dokumentet er en kartlegging — ingen kodeendringer er gjort utover selve dokumentasjonen.*
