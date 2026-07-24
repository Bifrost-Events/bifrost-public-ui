# Publikumsportal V3 — overgang (fullført)

**Status:** V3 only (V2 cutover)  
**Dato:** 2026-07-24  

Auth: [public-auth-v3.md](public-auth-v3.md)  
API: [public-portal-api-dependencies.md](public-portal-api-dependencies.md)

---

## Modell

| Funksjon | Kilde |
|----------|-------|
| Login / person / representasjon | **admin-core** |
| Kalender / serie / arrangement / resultater / sammenlagt | **Events** |
| Påmelding (generell + jaktfelt) | **Events** |
| Brand / meny / forsidetekst | **CupConfigLoader** (`config/cups`) |
| Legacy `/calendar/{id}` | Redirect til V3-event eller 410 |

`BackendApiClient` / `TenantContext` er fjernet.

---

## Tester

```bash
cd ../bifrost-public-ui
php scripts/test_public_portal_v3.php --offline
php scripts/test_public_portal_v3.php
```
