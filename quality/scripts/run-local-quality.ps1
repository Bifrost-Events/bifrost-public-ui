#!/usr/bin/env pwsh
# Kjør Playwright mot local-quality uten å permanent overskrive dev-.env.
#
# CLI (quality-db, manifest) leser .env.local-quality via BIFROST_DOTENV / QUALITY_ENV.
# Apache trenger samme profil for HTTP – dette scriptet aktiverer midlertidig (.env.local-quality → .env)
# og gjenoppretter dev-.env etterpå.

param(
    [switch]$SkipActivate,
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PlaywrightArgs
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$activateScript = Join-Path $PSScriptRoot 'activate-local-quality.ps1'
$activated = $false
$exitCode = 0

try {
    if (-not $SkipActivate) {
        & $activateScript -Action activate
        $activated = $true
        Write-Host ''
        Write-Host 'Apache: restart XAMPP første gang etter activate, eller hvis health feiler.' -ForegroundColor Yellow
        Write-Host ''
    }

    # Force array — string splat in PowerShell expands to characters ("test" → t e s t).
    $playwrightCmd = if ($null -ne $PlaywrightArgs -and @($PlaywrightArgs).Count -gt 0) {
        @($PlaywrightArgs)
    } else {
        @('test')
    }
    & npx playwright @playwrightCmd
    $exitCode = $LASTEXITCODE
} finally {
    if ($activated) {
        Write-Host ''
        & $activateScript -Action deactivate
        Write-Host 'Dev-.env gjenopprettet. Restart Apache for manuell utvikling.' -ForegroundColor Yellow
    }
}

exit $exitCode
