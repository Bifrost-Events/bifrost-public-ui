# GitHub branch protection (anbefalt)

Konfigureres manuelt i GitHub.

## Repository: `Bifrost-Events/bifrost-public-ui`

**Settings → Branches → Add rule** for `main`:

| Innstilling | Verdi |
|-------------|--------|
| Require pull request reviews | Anbefalt |
| Require status checks | Valgfritt (ingen auto-deploy ved push) |

Deploy til test og production skjer kun via release-flyten (`npm run release:deploy`) etter quality-godkjenning.

## Repository: `Bifrost-Events/bifrost-backend`

Ingen required checks på `main` nødvendig for deploy – push deployer ikke automatisk.

## Release-gates (lokalt)

- `release:approve quality` før test-deploy
- `release:approve test` før production-deploy

Se [release/README.md](../release/README.md).
