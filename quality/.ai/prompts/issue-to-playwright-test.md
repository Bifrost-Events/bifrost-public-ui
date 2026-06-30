# Issue → Playwright-test

Bruk denne malen når en bug eller feature skal bli en varig quality-test i Bifrost.

## Input fra issue

- Cup(er) som er berørt (`slatlem`, `jaktfeltcup`, `namdal`)
- Miljø der feilen ble observert (`local`, `staging`, `production`)
- URL / route (`/calendar`, `/results`, …)
- Forventet vs. faktisk oppførsel
- Console-feil, screenshots eller trace fra Playwright hvis tilgjengelig

## Beslutning: smoke eller domain?

| Type | Plassering | Eksempel |
|------|------------|----------|
| Side laster, riktig cup, ingen console-feil | Evt. ny `route` i `quality/apps/{cup}.yml` | Eksisterende smoke dekker det meste |
| Feil cup på domene | `quality/tests/domain/` | `data-cup-key` matcher ikke |
| Regresjon på spesifikk interaksjon | Ny test i `quality/tests/smoke/` eller egen mappe | Knapp, skjema, redirect |

**Preferanse:** Utvid manifest (`routes`, `expected.visibleText`) før du skriver ny testkode.

## Steg for ny route i manifest

1. Åpne `quality/apps/{cup}.yml`
2. Legg til under `routes`:
   ```yaml
   - path: /eksempel
     name: Eksempel
   ```
3. Legg til `expected.visibleText` hvis siden har unik tekst som bekrefter riktig innhold.

## Steg for ny dedikert test

1. Opprett `quality/tests/{område}/{beskrivelse}.spec.ts`
2. Importer fra `quality/support/fixtures.ts`:
   - `test`, `expect`, `visitRoute`, `assertPageBasics`, `consoleCollector`
3. Bruk `app`-fixture – ikke hardkod URL eller cup-navn
4. Kjør: `cross-env QUALITY_ENV=local QUALITY_APP={cup} npx playwright test path/to/spec.ts`

## Sikkerhet

- Ingen ekte betaling, e-post eller destruktive POST i automatiserte tester
- Produksjon: kun read-only smoke
- Ingen hemmeligheter i repo – bruk GitHub Secrets ved behov

## Output

- PR med manifest-endring og/eller ny `.spec.ts`
- Oppdatert `quality/README.md` hvis nye miljøvariabler eller scripts
