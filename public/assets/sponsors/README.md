# Sponsorlogoer

Filer serveres statisk fra `/assets/sponsors/`.

## Kilde

Logoer synkroniseres fra **jaktfeltnamdalen**:

```
jaktfeltnamdalen/storage/images/sponsors/
```

Kjør sync fra `bifrost-public-ui`:

```powershell
.\scripts\sync-assets-from-jaktfeltnamdalen.ps1
```

## Forventet for Namdal (cup-config)

| Fil | I git (jaktfeltnamdalen) |
|-----|--------------------------|
| `sjur-ivar-hjellum-utvikling.svg` | Ja |
| `anne-britts-arrangementservice.svg` | Ja |
| `gull-sponsor.svg` | Ja |
| `KKC_of_Norway_37x85.svg` | Ja |
| `logo_Norma.JPG` | Nei — deploy-asset |
| `raavaren-logo-hvit.png` | Nei — deploy-asset |
| `jarnheimr.png` | Nei — deploy-asset |
| `A-TEC_LOGO24_positiv.png` | Nei — deploy-asset |
| `Logo_utmarkscompagniet.jpg.png` | Ved sync fra jaktfeltnamdalen |

PNG/JPG ligger ofte kun i lokal/produksjons-installasjon av jaktfeltnamdalen — ikke alltid i git. Kjør sync-scriptet etter deploy eller når nye sponsorer legges til der.
