#!/usr/bin/env pwsh
# Vis release-status, sammenlign commits og foreslå neste steg.

param(
    [string]$ReleaseId
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

$loaded = Load-Manifest -ReleaseId $ReleaseId
$manifest = $loaded.Data
$ReleaseId = $loaded.ReleaseId
$reposConfig = Get-ReposConfig
$qualityCmd = Get-LocalQualityCommand

Write-Host ""
Write-Host "Release: $ReleaseId" -ForegroundColor Cyan
Write-Host "Opprettet: $($manifest.createdAt)"
Write-Host ""
Write-Host "Repos:"

$allMatch = $true
foreach ($prop in $manifest.repositories.PSObject.Properties) {
    $key = $prop.Name
    $entry = $prop.Value
    $cfg = $reposConfig.repositories.$key
    if (-not $cfg) { continue }

    $isTrackOnly = $false
    if ($entry.PSObject.Properties.Name -contains 'trackOnly') {
        $isTrackOnly = [bool]$entry.trackOnly
    }

    $repoPath = Resolve-RepoPath -LocalPath $cfg.localPath
    $git = Get-RepoGitInfo -RepoPath $repoPath
    $match = $git.Commit -eq $entry.commit
    if (-not $match) { $allMatch = $false }

    $icon = if ($match) { '[OK]' } else { '[!]' }
    $track = if ($isTrackOnly) { ' (track-only)' } else { '' }
    $localNote = if (-not $match) { " (lokal: $($git.ShortCommit))" } else { '' }
    Write-Host "  $icon $key $($entry.shortCommit)$track$localNote"
}

if (-not $allMatch) {
    Write-Host ""
    Write-Host "[!] Lokale commits avviker fra manifest." -ForegroundColor Yellow
    Write-Host "  Kjor release:create pa nytt hvis du vil oppdatere release-settet."
}

Write-Host ""
Write-Host "Status:"

function Write-StatusLine {
    param([string]$Label, [string]$State, [string]$FailLabel = $null)

    $icon = Format-StatusIcon -State $State
    $text = switch ($State) {
        'ok' { $Label }
        'approved' { $Label }
        'pending' { "$Label ikke startet" }
        'failed' { "$Label feilet" }
        'blocked' { "$FailLabel" }
        default { "$Label ($State)" }
    }
    Write-Host "  $icon $text"
}

$st = $manifest.status
$qualityOk = Test-QualityApproved -Manifest $manifest

if ($qualityOk) {
    Write-Host "  $(Format-StatusIcon 'approved') lokal quality godkjent"
} else {
    Write-Host "  $(Format-StatusIcon 'failed') lokal quality mangler"
}

Write-StatusLine -Label 'test deploy' -State $st.testDeploy.state
Write-StatusLine -Label 'test smoke' -State $st.testSmoke.state

$testOk = Test-TestApproved -Manifest $manifest
if ($testOk) {
    Write-Host "  $(Format-StatusIcon 'approved') test-godkjenning"
} else {
    Write-Host "  $(Format-StatusIcon 'failed') test-godkjenning mangler"
}

$prodAllowed = $testOk -and
    $st.testDeploy.state -eq 'ok' -and
    $st.testSmoke.state -eq 'ok'

Write-StatusLine -Label 'production deploy' -State $(if ($prodAllowed) { $st.productionDeploy.state } else { 'blocked' }) -FailLabel 'production ikke tillatt'
Write-StatusLine -Label 'production smoke' -State $(if ($prodAllowed) { $st.productionSmoke.state } else { 'blocked' }) -FailLabel 'production smoke ikke tillatt'

Write-Host ""
Write-Host "Neste steg:" -ForegroundColor Cyan

if (-not $qualityOk) {
    Write-Host "  Kjor lokal quality (public-ui + admin + arrangor mot samme backend):"
    Write-Host "    $qualityCmd"
    Write-Host ""
    Write-Host "  Krever Apache/vhosts: jaktfeltcup.local, admin.bifrost.local"
    Write-Host "  (arrangor.bifrost.local nar portalen er klar - ellers hoppes den over)"
    Write-Host ""
    Write-Host "  Deretter godkjenn:"
    Write-Host "    npm run release:approve -- -ReleaseId $ReleaseId -Type quality -By `"<navn>`" -Reason `"Playwright platform OK`""
    exit 0
}

if ($st.testDeploy.state -ne 'ok') {
    Write-Host "  npm run release:deploy -- -ReleaseId $ReleaseId -Environment test"
    exit 0
}

if ($st.testSmoke.state -ne 'ok') {
    Write-Host "  Vent pa test smoke i GitHub Actions, deretter:"
    Write-Host "    npm run release:mark-smoke -- -ReleaseId $ReleaseId -Environment test"
    exit 0
}

if (-not $testOk) {
    Write-Host "  Manuell test-godkjenning:"
    Write-Host "    npm run release:approve -- -ReleaseId $ReleaseId -Type test -By `"<navn>`" -Reason `"Manuell test OK`""
    exit 0
}

if ($st.productionDeploy.state -ne 'ok') {
    Write-Host "  npm run release:deploy -- -ReleaseId $ReleaseId -Environment production"
    exit 0
}

if ($st.productionSmoke.state -ne 'ok') {
    Write-Host "  Vent pa production smoke i GitHub Actions, deretter:"
    Write-Host "    npm run release:mark-smoke -- -ReleaseId $ReleaseId -Environment production"
    exit 0
}

Write-Host "  Release-flyt fullfort for $ReleaseId." -ForegroundColor Green
