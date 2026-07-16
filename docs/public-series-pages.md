# Publikumsportal — seriesider (V3)

**Repo:** `bifrost-public-ui`  
**Rute:** `GET /serier/{seriesId}`  
**Controller:** `App\Controller\SeriesController`  
**View:** `app/02-view/series-show-content.php`  
**API:** `GET /api/public/series/{seriesId}?host=`

---

## Navigasjon

```text
Kalender (/calendar)
  → Serie / sesong (/serier/{id})
    → Underserie / runde (/serier/{id})
      → Arrangement (/arrangementer/{id})
```

Primær nøkkel er **series_id** (stabil). Slug er ikke primær rute i v1 (slug er ofte unik kun innen space).

---

## Innhold på seriesiden

- Breadcrumb (ancestor-kjede fra root til aktuell serie)
- Navn, beskrivelse, sesong/periode (`season_label`, `starts_at`/`ends_at`)
- Event Space-navn
- Eierorganisasjon
- Parent-serie (hvis underserie)
- Liste over child-serier (underserier/runder)
- Arrangementer **direkte** knyttet til denne `series_id`
- Domenetilpassede labels (ikke hardkodet «stevne»/«runde»)

### Terminologieksempler

| Nivå | Jaktfelt | Fotball |
|------|----------|---------|
| Event Space | Cup | Turnering |
| Serie | Sesong | Serie |
| Underserie | Runde | Runde |
| Event | Stevne | Kamp |

---

## API-responsform for serieliste

`GET /api/public/event-spaces/{spaceId}/series` returnerer **flat liste** med `parent_series_id` og `event_count`.

Valgt fordi:

- matcher enkel klientmapping
- UI kan bygge tre ved behov
- lettere å utvide senere

Seriedetalj returnerer allerede `parent`, `children`, `events`, `breadcrumb`.

---

## Visibility

Kun `visibility=public` + `status=active` + ikke deleted, og scoped til host-application. Feil/annen app → 404.

---

## Tomtilstander

- Ingen underserier → muted tekst med labels
- Ingen arrangementer i serien → muted tekst (ikke feil)
