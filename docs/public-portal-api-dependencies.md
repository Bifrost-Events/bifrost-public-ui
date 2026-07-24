# Publikumsportal — API-avhengigheter (V3 only)

**Repo:** `bifrost-public-ui`  
**Oppdatert:** 2026-07-24

---

## Oversikt

```text
bifrost-public-ui
  ├─ AdminAuthClient      → ADMIN_URL (bifrost-admin-core)
  │    POST /api/auth/login|register|logout
  │    GET  /api/auth/me
  │    GET/POST/PATCH /api/public/me/people*
  │
  ├─ EventsApiClient      → EVENTS_URL (admin-core / Events-modul)
  │    GET /api/public/context|calendar|series|events|results|standings
  │    POST/GET jaktfelt + generelle registrations
  │
  └─ CupConfigLoader      → lokal config/cups/*.json (brand/meny/innhold)
```

Ingen `bifrost-backend` / V2-klient. Alle Events-kall krever `?host=` for application-scope.

---

## Auth (admin-core)

| Metode | Endepunkt | Brukt av |
|--------|-----------|----------|
| POST | `/api/auth/login` | `AuthController` |
| POST | `/api/auth/register` | `AuthController` |
| POST | `/api/auth/logout` | `AuthController` |
| GET | `/api/auth/me` | `Auth` / `ProfileController` |
| GET/POST | `/api/public/me/people` | Personvelger / representerte personer |
| PATCH | `/api/public/me/people/{id}` | Profiloppdatering |

---

## Events (public)

| Metode | Endepunkt | Brukt av |
|--------|-----------|----------|
| GET | `/api/public/context?host=` | `PublicPortalContext` |
| GET | `/api/public/calendar?host=` | `PublicCalendarService` |
| GET | `/api/public/series/{id}?host=` | `PublicCatalogClient` |
| GET | `/api/public/series/{id}/standings?host=` | Sammenlagt |
| GET | `/api/public/events/{id}?host=` | Arrangement |
| GET | `/api/public/events/{id}/results?host=` | Resultater |
| POST | `/api/public/events/{id}/registrations?host=` | Generell påmelding |
| GET/POST | `/api/public/jaktfelt/events/{id}/…` | Jaktfelt lag/plass |
| GET | `/api/public/me/registrations?host=` | Mine påmeldinger |

**Feilstrategi:** kontrollert feilmelding / 404 / 503 — ingen V2-fallback.

---

## Lenker

| Situasjon | URL |
|-----------|-----|
| Kalender → arrangement | `/arrangementer/{event_id}` |
| Legacy `/calendar/{legacy_id}` | Redirect til V3-event hvis mapping finnes, ellers 410 |
| Sesong/serie | `/serier/{series_id}` |
| Resultater | `/arrangementer/{event_id}/resultater` |
| Sammenlagt | `/serier/{series_id}/sammenlagt` |
| Representerte personer | `/min-side/personer` |

---

## Env

```env
ADMIN_URL=http://admin.bifrost.local
EVENTS_URL=http://admin.bifrost.local
ADMIN_CORE_PATH=../bifrost-admin-core
ADMIN_CORE_DOTENV=.env
ARRANGOR_PORTAL_URL=http://arrangor.jaktfeltcup.local
```

`BACKEND_API_URL` er ikke i bruk i app-kode. Quality-scripts aksepterer fortsatt `BACKEND_PATH` som alias for `ADMIN_CORE_PATH`.

---

## Demo-/test-hosts

| Host | application_key |
|------|-----------------|
| `jaktfeltcup.local` | `jaktfeltcup` |
| `namdal.jaktfeltkarusell.local` | `jaktfeltkarusell-namdal` |
| `slatlemcup.local` | `slatlem` |
| `skytecuper.bifrost.local` | `skytecuper` |
