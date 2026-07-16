# Publikumsportal — arrangementssider (V3)

**Repo:** `bifrost-public-ui`  
**Rute:** `GET /arrangementer/{eventId}`  
**Controller:** `App\Controller\EventController`  
**View:** `app/02-view/event-show-content.php`  
**API:** `GET /api/public/events/{eventId}?host=`

---

## Formål

Read-only V3-arrangementsside. Primær detaljflate fra kalenderen.

Kalenderen linker alltid hit (`/arrangementer/{event_id}`), uavhengig av legacy.

---

## Innhold

Vises når felt har verdi:

- Navn, beskrivelse
- Dato/klokkeslett (`starts_at` / `ends_at`)
- Sted (`location_name`)
- Arrangør / eierorganisasjon
- Serie / underserie (med type-basert label)
- Event Space
- Breadcrumb gjennom seriehierarki
- Påmeldingsperiode
- Kapasitet (`max_participants`)
- Status

Labels styrer overskrifter og brødsmulestart (f.eks. «Stevner» vs «Kamper»).

---

## V2-hybridlenker

På siden kan det vises:

| Lenke | URL | Vilkår |
|-------|-----|--------|
| Påmelding (V2) | `/calendar/{legacy_id}` | `legacy.table = jaktfelt_competitions` + numerisk `legacy.id` |
| Resultater (V2) | `/results/{legacy_id}` | samme |

Implementert i `EventUrlResolver::v2SignupUrl` / `v2ResultsUrl`.  
Merket i UI som «Handlinger (midlertidig V2)» / `data-hybrid="v2"`.

**Ikke** gjett lenke fra navn, slug eller dato.

Uten mapping: full V3-side uten V2-knapper.

---

## Visibility og feil

| Case | Respons |
|------|---------|
| Ukjent `event_id` | 404 |
| Event fra annen application/host | 404 (ingen lekkasje) |
| Ikke-offentlig / ikke-active | 404 |
| Events API nede | 503 / feilmelding |

Ingen V2-fallback for selve detaljsiden.

---

## Relatert

- [public-series-pages.md](public-series-pages.md)
- [public-portal-api-dependencies.md](public-portal-api-dependencies.md)
- [public-portal-v3-transition.md](public-portal-v3-transition.md)
