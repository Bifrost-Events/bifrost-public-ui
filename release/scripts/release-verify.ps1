#!/usr/bin/env pwsh
# Sjekk forutsetninger før E2E release-flyt.

param(
    [string]$ReleaseId
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

$ok = $true

function Test-Check {
    param([bool]$Passed, [string]$Message)
    script:ok = script:ok -and $Passed
    $icon = if ($Passed) { '[OK]' } else { '[X]' }
    Write-Host "  $icon $Message"
}

Write-Host ""
Write-Host "Release E2E forutsetninger" -ForegroundColor Cyan
Write-Host ""

Write-Host "Verktoy:"
try {
    Assert-GhCli
    Test-Check -Passed $true -Message 'gh CLI autentisert'
} catch {
    Test-Check -Passed $false -Message $_.Exception.Message
}

try {
    node --version | Out-Null
    Test-Check -Passed $true -Message 'Node.js tilgjengelig (YAML-config)'
} catch {
    Test-Check -Passed $false -Message 'Node.js mangler'
}

Write-Host ""
Write-Host "Lokale repo:"
$reposConfig = Get-ReposConfig
foreach ($prop in $reposConfig.repositories.PSObject.Properties) {
    $cfg = $prop.Value
    $path = Resolve-RepoPath -LocalPath $cfg.localPath
    $exists = Test-Path (Join-Path $path '.git')
    Test-Check -Passed $exists -Message "$($prop.Name) -> $path"
}

Write-Host ""
Write-Host "Lokal quality:"
$publicUiRoot = (Get-PublicUiRoot).Path
$localQualityUi = Join-Path $publicUiRoot '.env.local-quality'
$localQualityBe = Join-Path $publicUiRoot '..\bifrost-backend\.env.local-quality'
Test-Check -Passed (Test-Path $localQualityUi) -Message 'public-ui .env.local-quality'
Test-Check -Passed (Test-Path $localQualityBe) -Message 'backend .env.local-quality'

Write-Host ""
Write-Host "Secrets config:"
$secretsPath = Join-Path (Get-ReleaseRoot) 'config\deploy-secrets.local.yml'
Test-Check -Passed (Test-Path $secretsPath) -Message "deploy-secrets.local.yml finnes"

Write-Host ""
Write-Host "Release manifest:"
if ($ReleaseId) {
    $manifestPath = Get-ManifestPath -ReleaseId $ReleaseId
    Test-Check -Passed (Test-Path $manifestPath) -Message "manifest for $ReleaseId"
} else {
    $latest = Get-LatestReleaseId
    if ($latest) {
        Test-Check -Passed $true -Message "Siste release: $latest"
    } else {
        Test-Check -Passed $false -Message 'Ingen release opprettet enna (kjor release:create)'
    }
}

Write-Host ""
if ($ok) {
    Write-Host "Klar for release-flyt. Se release/docs/e2e-verification.md" -ForegroundColor Green
    exit 0
}

Write-Host "Noen sjekker feilet. Rett opp for E2E." -ForegroundColor Red
exit 1
