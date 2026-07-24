# Publikumsportal — V3-auth

**Status:** Implementert (V3 only)  
**Dato:** 2026-07-24  
**API:** [../bifrost-admin-core/docs/auth-public-api.md](../../bifrost-admin-core/docs/auth-public-api.md)

---

## Hva som er V3

| Funksjon | Implementasjon |
|----------|----------------|
| Login / logout / register | `AuthController` → `AdminAuthClient` → admin-core |
| Me / profil | `/min-side/profil` via `ProfileController` |
| Personvelger | `PersonPickerService` + `_person_picker.php` |
| Representerte personer | `/min-side/personer` (`/api/public/me/people`) |
| Påmelding | Events API (generell + jaktfelt) |
| Mine påmeldinger | `/min-side/pameldinger` |

Legacy «Mine deltakere» (`/min-side/deltakere`) redirecter til `/min-side/personer`. ShooterID/claim er ikke portert.

---

## Konfigurasjon

```env
ADMIN_URL=http://admin.bifrost.local
EVENTS_URL=http://admin.bifrost.local
```

---

## Ruter

| URL | Handling |
|-----|----------|
| `/auth/login` | V3-login |
| `/auth/register` | V3-registrering |
| `/auth/logout` | V3 logout |
| `/min-side/profil` | Min side + personvelger |
| `/min-side/pameldinger` | Påmeldinger |
| `/min-side/personer` | Representerte personer |
| `/min-side/deltakere` | Redirect → personer |
