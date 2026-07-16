# Publikumsportal — jaktfeltpåmelding V3

**Status:** Implementert (hybrid)  
**API:** [../../bifrost-events/docs/jaktfelt-registration-v3.md](../../bifrost-events/docs/jaktfelt-registration-v3.md)

Når `event.modules.jaktfelt` er true:

- Personvelger + klasse + lag/plass på `/arrangementer/{id}`
- Visningsnavn: **plass** (ikke figur)
- Avmelding via jaktfelt-cancel (frigjør plass, beholder historikk)

Ellers: generell V3 eller V2 legacy etter `EventUrlResolver::registrationFlow()`.
