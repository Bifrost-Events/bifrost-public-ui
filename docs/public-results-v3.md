# Publikumsportal — V3 resultater og sammenlagt

**Status:** Implementert (read-only)  
**Dato:** 2026-07-16  
**API:** [../bifrost-events/docs/results-v3.md](../../bifrost-events/docs/results-v3.md)

---

## Ruter

| URL | Controller | Kilde |
|-----|------------|--------|
| `/arrangementer/{eventId}/resultater` | `EventController::results` | `GET /api/public/events/{id}/results?host=` |
| `/serier/{seriesId}/sammenlagt` | `SeriesController::standings` | `GET /api/public/series/{id}/standings?host=` |

Ingen legacy-ID i primær URL.

---

## Arrangement

- Primær: V3-resultatliste (klassegrupper, plass, navn, klubb, total, skille)
- Tomtilstand når ingen publiserte V3-rader
- V2-resultatlenke kun når **ingen** V3-resultater **og** sikker legacy-mapping
- Når V3 finnes: V2-resultatlenke undertrykkes (inkl. på arrangementsdetalj)

---

## Serie / sammenlagt

- Lenke «Sammenlagt» på serieside → `/serier/{id}/sammenlagt`
- Label default: `Sammenlagt` (domenetilpasset label kan komme senere via config)
- Underserie: API resolver til toppserie; UI viser merknad via `resolved_from_series_id`

---

## Hybrid (resultater)

| Situasjon | Oppførsel |
|-----------|-----------|
| V3 published results | Vis V3; skjul V2-resultatlenke |
| Ingen V3, sikker V2-mapping | Tomtilstand + midlertidig V2-lenke |
| Ingen V3, ingen mapping | Tomtilstand |

V2 `ResultsController` / `StandingsController` / `BackendApiClient` er **ikke** slettet.

---

## Klasser

| Klasse | Rolle |
|--------|--------|
| `EventsApiClient::publicEventResults` / `publicSeriesStandings` | HTTP |
| `PublicCatalogClient::eventResults` / `seriesStandings` | Enrich + URLs |
| `EventUrlResolver::v3EventResultsUrl` / `v3StandingsUrl` | V3-stier |

Views: `event-results-content.php`, `series-standings-content.php`.

---

## Tester

```bash
php scripts/test_public_portal_v3.php --offline
php scripts/test_public_portal_v3.php
```
