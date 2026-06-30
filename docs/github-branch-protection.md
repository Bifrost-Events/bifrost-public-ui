# GitHub branch protection (anbefalt)

Konfigureres manuelt i GitHub etter første vellykkede release-pipeline.

## Repository: `Bifrost-Events/bifrost-public-ui`

**Settings → Branches → Add rule** for `main`:

| Innstilling | Verdi |
|-------------|--------|
| Require status checks | `quality-staging` (fra Release pipeline) |
| Require branches to be up to date | Valgfritt |

Valgfritt: opprett GitHub Environment **`test-promotion`** med **Required reviewers** – brukes av `backend-test`-jobben i [release-pipeline.yml](../.github/workflows/release-pipeline.yml) før deploy til test-miljø.

## Repository: `Bifrost-Events/bifrost-backend`

Ingen required checks på `main` nødvendig – staging deployes ved push; quality kjører i public-ui-pipelinen.

## Status checks etter første kjøring

Navn på checks matcher job-navn i workflow:

- `Release pipeline / quality-staging`
- `Release pipeline / quality-test-smoke`

Eksakt navn vises under Actions etter første run – bruk det i branch protection.
