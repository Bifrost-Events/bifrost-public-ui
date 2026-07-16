# Publikumsportal — V3-auth

**Status:** Implementert (hybrid)  
**Dato:** 2026-07-16  
**Analyse:** [public-auth-v3-analysis.md](public-auth-v3-analysis.md)  
**API:** [../bifrost-admin-core/docs/auth-public-api.md](../../bifrost-admin-core/docs/auth-public-api.md)

---

## Hva som er V3

| Funksjon | Implementasjon |
|----------|----------------|
| Login / logout | `AuthController` → `AdminAuthClient` → admin-core |
| Me / profil | `/min-side/profil` via `ProfileController` |
| Personvelger | `PersonPickerService` + `_person_picker.php` |
| Representerte personer | `GET/POST /api/public/me/people` |
| Handler på vegne av | Session `acting_person_id` |

## Hva som er V2 midlertidig

| Funksjon | Merknad |
|----------|---------|
| Påmelding (jaktfelt legacy) | `v2_legacy` → `/calendar/{id}` |
| Mine deltakere (shooters) | V2 participant API |
| Onboarding / claim | V2 |
| Registrering av ny konto | Fortsatt V2 participant register |

## Hva som er V3-påmelding

| Funksjon | Merknad |
|----------|---------|
| Generell påmelding | `event_registrations` via events API |
| Mine påmeldinger | `/min-side/pameldinger` |
| Representasjon | Samme personsett som auth (`person_user_reps`) |

Hybridregel: [public-registrations-v3.md](public-registrations-v3.md). Ved V3-login forsøkes best-effort V2-login med samme credentials for legacy jaktfelt-påmelding. V3-sider faller **ikke** automatisk tilbake til V2-auth.

---

## Konfigurasjon

```env
ADMIN_URL=http://admin.bifrost.local   # valgfri; fallback = EVENTS_URL
EVENTS_URL=http://admin.bifrost.local
BACKEND_API_URL=http://api.bifrost.local
```

---

## Ruter

| URL | Handling |
|-----|----------|
| `/auth/login` | V3-login |
| `/auth/logout` | V3 + V2 logout |
| `/min-side/profil` | Min side + personvelger |
| `/min-side/pameldinger` | V3-påmeldinger (kommende/tidligere) |
| `/min-side/personvelger` | POST bytt acting person |
| `/min-side/personer` | POST opprett representert person |

---

## Personoppretting (Alternativ B)

Innlogget bruker kan opprette enkel person + `guardian`/`manual`/`delegated`-kobling med eksplisitt `confirm`.
