#!/usr/bin/env pwsh
# Aktiver local-quality for Apache (kopier .env.local-quality -> .env).
# CLI quality-scripts bruker allerede BIFROST_DOTENV; HTTP trenger samme profil.

param(
    [ValidateSet('activate', 'deactivate')]
    [string]$Action = 'activate'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$publicUiRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$adminCoreCandidate = Join-Path $publicUiRoot.Path '..\bifrost-admin-core'
if (-not (Test-Path $adminCoreCandidate)) {
    throw "Mangler bifrost-admin-core ved $adminCoreCandidate"
}
$adminCoreRoot = (Resolve-Path $adminCoreCandidate).Path

$repos = @(
    @{
        Name = 'bifrost-public-ui'
        Root = $publicUiRoot.Path
        QualityFile = '.env.local-quality'
    },
    @{
        Name = 'bifrost-admin-core'
        Root = $adminCoreRoot
        QualityFile = '.env.local-quality'
    }
)

function Activate-Repo {
    param(
        [string]$Name,
        [string]$Root,
        [string]$QualityFile
    )

    $active = Join-Path $Root $QualityFile
    $target = Join-Path $Root '.env'
    $backup = Join-Path $Root '.env.before-quality'

    if (-not (Test-Path $active)) {
        throw "Mangler $active i $Name. Kopier fra .env.local-quality.example."
    }

    if (-not (Test-Path $backup) -and (Test-Path $target)) {
        Copy-Item $target $backup
        Write-Host "  Backup: $backup"
    }

    Copy-Item $active $target -Force
    Write-Host "  OK $Name : $QualityFile -> .env"
}

function Deactivate-Repo {
    param(
        [string]$Name,
        [string]$Root
    )

    $target = Join-Path $Root '.env'
    $backup = Join-Path $Root '.env.before-quality'

    if (-not (Test-Path $backup)) {
        Write-Host "  Hopper over $Name (ingen .env.before-quality)"
        return
    }

    Copy-Item $backup $target -Force
    Write-Host "  OK $Name : gjenopprettet .env fra backup"
}

Write-Host ""
if ($Action -eq 'activate') {
    Write-Host 'Aktiverer local-quality for Apache (.env)' -ForegroundColor Cyan
    foreach ($repo in $repos) {
        Activate-Repo -Name $repo.Name -Root $repo.Root -QualityFile $repo.QualityFile
    }
    Write-Host ""
    Write-Host 'Restart Apache/XAMPP, deretter:' -ForegroundColor Green
    Write-Host '  curl http://api.bifrost.local/api/health'
    Write-Host '  (skal vise app_env=local-quality, database_name=bifrost_quality_local)'
} else {
    Write-Host 'Gjenoppretter .env fra backup' -ForegroundColor Cyan
    foreach ($repo in $repos) {
        Deactivate-Repo -Name $repo.Name -Root $repo.Root
    }
    Write-Host ''
    Write-Host 'Restart Apache etter deaktivering.' -ForegroundColor Yellow
}

Write-Host ''
