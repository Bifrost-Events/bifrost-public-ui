# E2E-verifisering av release-flyt



Sjekkliste for å verifisere at release-flyten fungerer ende-til-ende.



## Forutsetninger



- [ ] `gh auth login` fungerer

- [ ] Lokal quality er satt opp (`.env.local-quality` i public-ui og backend) – se [quality/README.md](../../quality/README.md)

- [ ] `deploy-secrets.local.yml` er fylt ut og synket (`npm run release:sync-secrets`)

- [ ] GitHub Environments `test` og `production` finnes i **bifrost-backend**, **bifrost-public-ui**, **bifrost-admin-ui** og **bifrost-homepage** (homepage kun `production`)

- [ ] `FTP_PATH` og `APP_URL` er korrekte for test og production

- [ ] `bifrost-backend/database/` er synket fra bifrost-shared ved behov



## Lokal quality (erstatter sky-staging)



1. `npm run release:create`

2. `npm run quality:db:prepare` (om nødvendig)

3. `npm run quality:local`

4. `npm run quality:local` (inkl. admin; arrangor når klar)

5. `npm run release:approve -- -ReleaseId <id> -Type quality -By "..." -Reason "..."`

6. `npm run release:check` viser neste steg = test deploy



## Test



1. `npm run release:deploy -- -ReleaseId <id> -Environment test`

2. Verifiser grønn `Deploy release` i **backend**, **public-ui** og **admin-ui**

3. `npm run release:mark-smoke -- -ReleaseId <id> -Environment test`

4. Manuell verifisering av test-miljø

5. `npm run release:approve -- -ReleaseId <id> -Type test -By "..." -Reason "..."`



## Production



1. `npm run release:deploy -- -ReleaseId <id> -Environment production`

2. `npm run release:mark-smoke -- -ReleaseId <id> -Environment production`

3. `npm run release:check` viser fullført



## Push til GitHub



Push til `main` deployer **ikke** automatisk. Kun `npm run release:deploy` (etter gates) trigger deploy til test/production.



## Etter verifisering



Oppdater rot-README med peker til `release/README.md` om nødvendig.

