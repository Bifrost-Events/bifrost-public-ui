# Publikumsportal — V3-auth analyse

**Dato:** 2026-07-16  
**Formål:** Beslutningsgrunnlag for V3-auth, personidentitet og representasjon.

---

## Dagens situasjon

| Flate | Auth | Cookie | Identitet |
|-------|------|--------|-----------|
| Admin-core | `AuthService` + `AuthApiController` | `BIFROSTADMIN` (host-only) | `auth_users` ↔ `person_people` |
| Public-ui | Proxy til bifrost-backend | `BIFROSTPUBLIC` + lagret `BIFROSTSESSID` | V2 users / participants |
| bifrost-backend | Participant login/register | `BIFROSTSESSID` | `owner_user_id` → mange «skyttere» |
| jaktfeltnamdalen | Delegation | — | `jaktfelt_user_relationships` (`guardian_delegate`) |

Auth eies allerede i admin-core for admin. Public-ui har **ingen** egen auth-logikk utover proxy.

---

## Hva som kan gjenbrukes

- `POST /api/auth/login`, `logout`, `GET /api/auth/me` i admin-core
- `person_people` + `auth_users` (AB-0009 person/user-splitt)
- `PdoPersonRepository::create` for enkel personoppretting
- Public-ui UX: login-modal, session-lazy pattern, Min side-meny
- Server-side cookie-forwarding (samme mønster som `BackendApiClient`)

---

## Hva som er knyttet til V2

- `/api/auth/participant/*`
- «Mine deltakere» via `owner_user_id` / shooters
- Claim/onboarding
- Påmelding (`/api/participant/signups`)
- ShooterID / jaktfelt_id

---

## Hva som mangler i V3

- Public representation-tabell og API
- Public-ui klient mot admin-core auth (ikke backend)
- «Min side» basert på person + representasjon
- Generell personvelger uten jaktfelt-domene
- Cookie-strategi på tvers av public-host ↔ admin-host

---

## Cookie-beslutning

`BIFROSTADMIN` er host-only og deles ikke med `jaktfeltcup.local`.  
**Valgt løsning:** Public-ui proxier auth server-side (som V2) og lagrer `BIFROSTADMIN`-cookien i `BIFROSTPUBLIC`-sesjonen. Ingen CORS for credentials i nettleseren. Auth-logikk forblir i admin-core.

---

## Migrering senere

| Fra V2 | Til V3 |
|--------|--------|
| users + linked person | `auth_users` + `person_people` |
| participants under owner | `person_people` + `person_user_representations` |
| guardian_delegate | `relationship_type = guardian` / `delegated` |
| ShooterID | egen identifikator-modul (utsatt) |

---

## Hybrid (fase 5)

| V3 | V2 midlertidig |
|----|----------------|
| Login/logout/me | Påmelding |
| Egen person + representerte | Mine deltakere (shooters) |
| Personvelger | Onboarding/claim |
| Min profil (enkel) | Legacy resultathistorikk som krever V2-session |
