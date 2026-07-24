# Public onboarding V3

**Status:** Implementert  
**Dato:** 2026-07-17  
**App:** `bifrost-public-ui`

---

## Flyt

1. Opprett konto → `POST /api/auth/register` via `AdminAuthClient`
2. Min side / profil → se egen person, rediger minimumsfelt
3. Personer jeg representerer → `/min-side/personer` + `POST /api/public/me/people`
4. Fullfør deltakerprofil → `PATCH /api/public/me/person`
5. Videre til Min side eller arrangement

Arrangørsøknader ligger i **arrangørportalen**, ikke public.

Public viser: «Representerer du en arrangør? Gå til arrangørportalen.»  
(`ARRANGOR_URL` / config `arrangor.public_url` når satt.)

---

## Sider

| Rute | Formål |
|------|--------|
| `/auth/register` | Opprett konto (V3) |
| `/min-side/profil` | Profil + redigering |
| `/min-side/personer` | Representerte personer |
| `/onboarding` | Lett V3-stegvis oversikt (ikke V2 claim) |

---

## Avhengigheter

Krever admin-core auth + me/people API (se `bifrost-admin-core/docs/user-onboarding-v3.md`).
