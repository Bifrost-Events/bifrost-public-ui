# Publikumsportal V3 — overgang (fase 1–6)

**Status:** Implementert (hybrid)  
**Dato:** 2026-07-16  

Auth: [public-auth-v3.md](public-auth-v3.md)  
Påmelding: [public-registrations-v3.md](public-registrations-v3.md)  
Resultater: [public-results-v3.md](public-results-v3.md)  
API: [public-portal-api-dependencies.md](public-portal-api-dependencies.md)

---

## Hybridmodell (fase 6)

| Funksjon | Kilde |
|----------|-------|
| Login / person / representasjon | **V3** admin-core |
| Kalender / serie / arrangement / resultater / sammenlagt | **V3** events |
| Påmelding (ikke-jaktfelt V3-events) | **V3** `event_registrations` |
| Påmelding (jaktfelt_competitions legacy) | **V2** `/calendar/{id}` |
| Mine deltakere / onboarding | **V2** |

---

## Tester

```bash
cd ../bifrost-events
php bin/console public-registrations-test
php bin/console public-results-test

cd ../bifrost-public-ui
php scripts/test_public_portal_v3.php
```

### Testresultat (2026-07-16)

| Suite | Resultat |
|-------|----------|
| `public-registrations-test` | OK (13 sjekker) |
| `public-results-test` | OK |
| `public-catalog-test` | OK |
| `test_public_portal_v3.php --offline` | 34 passed |
| `test_public_portal_v3.php` (live) | 113 passed |
