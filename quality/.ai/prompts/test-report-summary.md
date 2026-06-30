# Testrapport-oppsummering

Bruk denne malen for å oppsummere en Bifrost quality-kjøring for teamet eller en PR.

## Input

- `quality/reports/results.json`
- `quality/reports/junit.xml` (CI)
- Artifact `quality-report-{env}-{app}` fra GitHub Actions
- Kjøreparametre: `QUALITY_ENV`, `QUALITY_APP`, scope (full/smoke)

## Oppsummeringsmal

### Status

- **Miljø:** {local|staging|production}
- **Cuper:** {liste}
- **Resultat:** {passed|failed} – X passed, Y failed, Z skipped
- **Varighet:** {minutter}

### Per cup (prosjekt)

For hver av `slatlem`, `jaktfeltcup`, `namdal`:

| Cup | Routes OK | Domain OK | Console OK | Merknad |
|-----|-----------|-----------|------------|---------|
| … | ✓/✗ | ✓/✗ | ✓/✗ | |

### Feilede tester

For hver feil:

1. Testnavn og fil
2. Route / URL
3. Kort feilmelding (assertion, timeout, connection)
4. Lenke til screenshot/trace hvis i CI-artifact

### Risiko-vurdering

- **Blokkerer deploy?** Ja/nei – begrunnelse
- **Kun miljø/infrastruktur?** (f.eks. staging nede)
- **Regresjon i app?** (krever kodefix)

### Anbefalte neste steg

- [ ] Fix app/config
- [ ] Oppdater manifest
- [ ] Utvid allowlist (kun dokumenterte ufarlige meldinger)
- [ ] Re-kjør med `QUALITY_APP={cup}`

## Tone

Kort, faktabasert, på norsk. Skill mellom infrastruktur-problemer og faktiske regresjoner.

## CI-kommentar til PR (eksempel)

```
## Bifrost Quality – staging

✅ slatlem, jaktfeltcup – alle smoke-tester grønne
❌ namdal – `/results` timeout (backend 504)

Anbefaling: ikke merge før backend staging er stabil, eller kjør på nytt.
Rapport: [artifact link]
```
