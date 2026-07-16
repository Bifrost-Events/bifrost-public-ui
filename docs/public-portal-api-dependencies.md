# Publikumsportal — API-avhengigheter (hybrid V3 + V2)

**Repo:** `bifrost-public-ui`  
**Oppdatert:** 2026-07-16

---

## Oversikt

```text
bifrost-public-ui
  ├─ AdminAuthClient      → ADMIN_URL / EVENTS_URL (bifrost-admin-core)
  │    POST /api/auth/login
  │    POST /api/auth/logout
  │    GET  /api/auth/me
  │    GET  /api/public/me/people
  │    POST /api/public/me/people
  │    GET  /api/public/me/people/{id}
  │
  ├─ EventsApiClient      → EVENTS_URL (admin / bifrost-events)
  │    GET /api/public/context
  │    GET /api/public/calendar
  │    GET /api/public/event-spaces/{id}
  │    GET /api/public/event-spaces/{id}/series
  │    GET /api/public/series/{id}
  │    GET /api/public/series/{id}/standings
  │    GET /api/public/events/{id}
  │    GET /api/public/events/{id}/results
  │    POST /api/public/events/{id}/registrations
  │    GET  /api/public/events/{id}/registrations/me
  │    GET  /api/public/me/registrations
  │    POST /api/public/registrations/{id}/cancel
  │
  └─ BackendApiClient     → BACKEND_API_URL (bifrost-backend V2)
       health, tenant resolve, auth (hybrid), results, standings, signup (legacy), participant
```

Alle Events-kall krever `?host=` for application-scope.

---

## V3 (Admin Core) — auth

| Metode | Endepunkt | Brukt av |
|--------|-----------|----------|
| POST | `/api/auth/login` | `AuthController` / `AdminAuthClient` |
| POST | `/api/auth/logout` | `AuthController` |
| GET | `/api/auth/me` | `Auth` / `ProfileController` |
| GET | `/api/public/me/people` | `PersonPickerService` |
| POST | `/api/public/me/people` | `ProfileController::createPerson` |
| GET | `/api/public/me/people/{id}` | (klar) |

---

## V3 (Events) — aktiv

| Metode | Endepunkt | Brukt av |
|--------|-----------|----------|
| GET | `/api/public/context?host=` | `PublicPortalContext` |
| GET | `/api/public/calendar?host=` | `PublicCalendarService` |
| GET | `/api/public/event-spaces/{id}?host=` | (klar for space-side) |
| GET | `/api/public/event-spaces/{id}/series?host=` | (klar for serieliste) |
| GET | `/api/public/series/{id}?host=` | `PublicCatalogClient` / `SeriesController` |
| GET | `/api/public/series/{id}/standings?host=` | `PublicCatalogClient` / `SeriesController::standings` |
| GET | `/api/public/events/{id}?host=` | `PublicCatalogClient` / `EventController` |
| GET | `/api/public/events/{id}/results?host=` | `PublicCatalogClient` / `EventController::results` |
| POST | `/api/public/events/{id}/registrations?host=` | `EventController::register` (auth cookie) |
| GET | `/api/public/events/{id}/registrations/me?host=` | `EventController::show` |
| GET | `/api/public/me/registrations?host=` | `SignupController` |
| POST | `/api/public/registrations/{id}/cancel` | Avmelding |

**Feilstrategi:** kontrollert feilmelding / 404 / 503 — **ingen** V2-fallback for V3-sider.

### Visibility (API)

- `visibility = public`
- `status = active`
- ikke soft-deleted
- ressurs må tilhøre application for request-host (ellers 404)

---

## V2 (Backend) — midlertidig hybrid

| Område | Endepunkter | Brukt av |
|--------|-------------|----------|
| Tenant | `/api/tenant/resolve` | `TenantContext` (auth/legacy) |
| Auth | `/api/auth/participant/*` | Hybrid best-effort ved V3-login; påmelding/deltakere |
| Results index | `/api/public/results` | `ResultsController` |
| Standings | `/api/public/standings` | `StandingsController` |
| Signup (V2 detalj) | `/api/public/competitions/{id}/signup` | Kun `v2_legacy` jaktfelt-hybrid |
| Participant | `/api/participant/*` | Mine deltakere / onboarding (V2) |

---

## Lenker

| Situasjon | URL |
|-----------|-----|
| Kalender → arrangement | `/arrangementer/{event_id}` (V3) |
| Sesong/serie | `/serier/{series_id}` (V3) |
| Arrangementsresultater | `/arrangementer/{event_id}/resultater` (V3) |
| Sammenlagt | `/serier/{series_id}/sammenlagt` (V3) |
| V2 påmelding (hybrid) | `/calendar/{legacy_id}` — kun `jaktfelt_competitions` + numerisk id |
| V2 resultater (hybrid) | `/results/{legacy_id}` — kun uten V3-data + samme mapping |

Resolver: `App\Service\EventUrlResolver`.

---

## Demo-/test-hosts

| Host | application_key |
|------|-----------------|
| `jaktfeltcup.local` | `jaktfeltcup` |
| `namdal.jaktfeltkarusell.local` | `jaktfeltkarusell-namdal` |
| `slatlemcup.local` | `slatlem` |
| `skytecuper.bifrost.local` | `skytecuper` |

---

## Kommandoer

```bash
php scripts/test_public_portal_v3.php
cd ../bifrost-events && php bin/console public-catalog-test
```
