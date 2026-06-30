# Synkroniser cup-/sponsor-assets fra jaktfeltnamdalen til bifrost-public-ui.
# Kjør fra repo-roten: .\scripts\sync-assets-from-jaktfeltnamdalen.ps1
#
# Forventet kilde (jaktfeltnamdalen):
#   storage/images/sponsors/*
#   storage/images/logos/namdal-jaktfeltkarusell-header.svg
#   storage/images/logos/NamdalJaktfeltkarusell.jpg
#   storage/images/logos/cup/jaktfeltcup_logo.png

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$jaktfeltRoot = Join-Path (Split-Path -Parent (Split-Path -Parent $repoRoot)) 'main-projects\jaktfeltnamdalen'

if (-not (Test-Path $jaktfeltRoot)) {
    $jaktfeltRoot = 'C:\xampp\htdocs\main-projects\jaktfeltnamdalen'
}

if (-not (Test-Path $jaktfeltRoot)) {
    Write-Error "Fant ikke jaktfeltnamdalen på: $jaktfeltRoot"
}

$srcSponsors = Join-Path $jaktfeltRoot 'storage\images\sponsors'
$srcLogos = Join-Path $jaktfeltRoot 'storage\images\logos'
$dstSponsors = Join-Path $repoRoot 'public\assets\sponsors'
$dstNamdal = Join-Path $repoRoot 'public\assets\cups\namdal'
$dstCup = Join-Path $repoRoot 'public\assets\cups\jaktfeltcup'

New-Item -ItemType Directory -Force -Path $dstSponsors, $dstNamdal, $dstCup | Out-Null

$copied = 0
if (Test-Path $srcSponsors) {
    Get-ChildItem $srcSponsors -File | ForEach-Object {
        Copy-Item $_.FullName (Join-Path $dstSponsors $_.Name) -Force
        $copied++
        Write-Host "sponsors: $($_.Name)"
    }
}

$logoMaps = @(
    @{ Src = Join-Path $srcLogos 'namdal-jaktfeltkarusell-header.svg'; Dst = Join-Path $dstNamdal 'logo.svg' },
    @{ Src = Join-Path $srcLogos 'NamdalJaktfeltkarusell.jpg'; Dst = Join-Path $dstNamdal 'logo.jpg' },
    @{ Src = Join-Path $srcLogos 'cup\jaktfeltcup_logo.png'; Dst = Join-Path $dstCup 'logo.png' }
)
foreach ($m in $logoMaps) {
    if (Test-Path $m.Src) {
        Copy-Item $m.Src $m.Dst -Force
        $copied++
        Write-Host "logo: $($m.Src) -> $($m.Dst)"
    } else {
        Write-Host "mangler (hoppet over): $($m.Src)" -ForegroundColor Yellow
    }
}

$expectedSponsors = @(
    'sjur-ivar-hjellum-utvikling.svg',
    'anne-britts-arrangementservice.svg',
    'gull-sponsor.svg',
    'KKC_of_Norway_37x85.svg',
    'logo_Norma.JPG',
    'raavaren-logo-hvit.png',
    'jarnheimr.png',
    'A-TEC_LOGO24_positiv.png',
    'Logo_utmarkscompagniet.jpg.png'
)
Write-Host ""
Write-Host "Forventede sponsorfiler i config (namdal-jaktfeltkarusell.json):"
foreach ($f in $expectedSponsors) {
    $p = Join-Path $dstSponsors $f
    if (Test-Path $p) {
        Write-Host "  OK  $f"
    } else {
        Write-Host "  MANGLER  $f  (kopier fra produksjon/deploy)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Kopiert $copied fil(er)."
