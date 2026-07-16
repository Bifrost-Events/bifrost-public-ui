# Publikumsportal — V3-påmelding

**Status:** Implementert (hybrid)  
**Dato:** 2026-07-16  
**API:** [../bifrost-events/docs/registrations-v3.md](../../bifrost-events/docs/registrations-v3.md)

---

## Hybridregel

| Flow | UI |
|------|-----|
| `v3` | Personvelger + Meld på / Avmeld på arrangementside |
| `v2_legacy` | Lenke til `/calendar/{legacyId}` (jaktfelt) |

Resolver: `EventUrlResolver::registrationFlow()`.

---

## UI

| Side | Innhold |
|------|---------|
| `/arrangementer/{id}` | Påmeldingsseksjon (login CTA / personstatus / handlinger) |
| `/min-side/pameldinger` | Kommende + tidligere V3-påmeldinger + avmelding |

Auth: `EventsApiClient` videresender `BIFROSTADMIN`-cookie for påmeldingskall.

Statusvisning på Min side / arrangementside leses direkte fra API (`registration_status`). Arrangørendringer (confirmed/rejected/cancelled) synes uten egen logikk i public-ui.

---

## Tester

```bash
cd ../bifrost-events && php bin/console public-registrations-test
php scripts/test_public_portal_v3.php
```
