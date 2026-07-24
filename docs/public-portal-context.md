# Publikumsportal — Application / Domain / Event Space-kontekst

**Repo:** `bifrost-public-ui`  
**Primær klasse:** `App\Support\PublicPortalContext`

---

## Oppslagskjede

```text
HTTP_HOST
  → GET /api/public/context?host=…
      → app_domains.hostname (active)
      → app_applications
      → event_spaces (visibility=public, status=active)
      → ui_labels (space → application → defaults)
```

Implementert i Events-modulen (`PublicCalendarService::context`).

Branding/meny/innhold kommer fra lokal `CupConfigLoader` (`config/cups/*.json`), ikke fra V2-tenant.

---

## PublicPortalContext-felt

| Felt | Beskrivelse |
|------|-------------|
| `host` | Aktivt hostname |
| `resolved` | Om application ble funnet |
| `application_id` / `application_key` / `application_name` | Applikasjon |
| `space` / `spaces` | Offentlige Event Spaces |
| `labels` | Terminologi |
| `branding` | Cup JSON via `CupConfigLoader` |
| `display_name` | Prefererer cup-config name, deretter application_name |
| `source` | `'v3'` |
| `error` / `status` | Ved feil |

---

## Hva som ikke skal skje

- Host-oppslag spredt i controllers/views
- V2 `TenantContext` / `/api/tenant/resolve`
- Layoutkode flyttet til Events-modulen
