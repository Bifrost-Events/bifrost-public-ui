# Publikumsportal — Application / Domain / Event Space-kontekst

**Repo:** `bifrost-public-ui`  
**Primær klasse:** `App\Support\PublicPortalContext`

---

## Oppslagskjede (V3)

```text
HTTP_HOST
  → GET /api/public/context?host=…
      → app_domains.hostname (active)
      → app_applications
      → event_spaces (visibility=public, status=active)
      → ui_labels (space → application → defaults)
```

Implementert i `bifrost-events` (`PublicCalendarService::context`).

---

## PublicPortalContext-felt

| Felt | Beskrivelse |
|------|-------------|
| `host` | Aktivt hostname |
| `resolved` | Om application ble funnet |
| `application_id` / `application_key` / `application_name` | Applikasjon |
| `space` | Primært Event Space (laveste `space_id`) eller `null` |
| `spaces` | Liste over offentlige spaces |
| `labels` | Terminologi (`event_space`, `series`, `subseries`, `event`) |
| `branding` | Cup JSON via `CupConfigLoader` (layout/theme) |
| `display_name` | Prefererer cup-config name, deretter application_name |
| `source` | Alltid `'v3'` |
| `error` / `status` | Ved feil (404 ukjent host, 503 transport, …) |

Hjelpemetode: `PublicPortalContext::label('event', 'plural')`.

---

## Hva som ikke skal skje

- Host-oppslag spredt i controllers/views
- V2 `TenantContext` / `/api/tenant/resolve` som primær kilde for V3-kalender
- Layoutkode flyttet til `bifrost-events`

---

## Terminologi

| Key | Jaktfelt-eksempel | Fotball-eksempel | Fallback |
|-----|-------------------|------------------|----------|
| `event_space` | Cup | Turnering | Event Space |
| `series` | Sesong | Serie | Serie |
| `subseries` | Runde | Runde | Underserie |
| `event` | Stevne | Kamp | Arrangement |

Påvirker **visning** (kalendertittel, tabellheaders), ikke routes eller tabellnavn.

---

## Hybrid med TenantContext

| Behov | Bruk |
|-------|------|
| Portal-identitet, kalender, labels | `PublicPortalContext` |
| Auth, resultater, påmelding, deltakere | `TenantContext` (V2) midlertidig |

`requestHost()` finnes på begge; preferer `PublicPortalContext::requestHost()` for nye V3-kall.
