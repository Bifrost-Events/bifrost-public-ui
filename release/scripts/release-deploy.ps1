#!/usr/bin/env pwsh
# Valider gates og trigge deploy-release workflow i hvert repo.

param(
    [Parameter(Mandatory)][string]$ReleaseId,
    [Parameter(Mandatory)]
    [ValidateSet('test', 'production')]
    [string]$Environment
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

Assert-GhCli

$loaded = Load-Manifest -ReleaseId $ReleaseId
$manifest = $loaded.Data
$reposConfig = Get-ReposConfig
$envConfig = Get-EnvironmentsConfig

switch ($Environment) {
    'test' {
        if (-not (Test-QualityApproved -Manifest $manifest)) {
            throw 'Test deploy ikke tillatt: lokal quality er ikke godkjent. Kjor quality:local og release:approve.'
        }
    }
    'production' {
        if (-not (Test-TestApproved -Manifest $manifest)) {
            throw 'Production deploy ikke tillatt: test-godkjenning mangler.'
        }
        if ($manifest.status.testDeploy.state -ne 'ok' -or $manifest.status.testSmoke.state -ne 'ok') {
            throw 'Production deploy ikke tillatt: test deploy/smoke ma vaere OK forst.'
        }
    }
}

$deployRepos = Get-DeployableRepos -ReposConfig $reposConfig
$githubEnv = $envConfig.environments.$Environment.githubEnvironment

Write-Host ""
Write-Host "Deploy release $ReleaseId til $Environment" -ForegroundColor Cyan
Write-Host "GitHub environment: $githubEnv"
Write-Host ""

foreach ($item in $deployRepos) {
    $key = $item.Key
    $cfg = $item.Config

    if (-not (Test-RepoDeploysToEnvironment -RepoConfig $cfg -Environment $Environment)) {
        Write-Host "Hopper over $key (deployer ikke til $Environment)" -ForegroundColor DarkGray
        continue
    }

    $repoEntry = $manifest.repositories.$key
    if (-not $repoEntry) {
        throw "Manifest mangler repo: $key"
    }

    $fullRepo = "$($cfg.owner)/$($cfg.repo)"
    $ref = $repoEntry.commit

    Write-Host "Trigger $fullRepo ($key) ref=$($repoEntry.shortCommit) ..." -ForegroundColor Cyan

    gh workflow run $cfg.workflow `
        --repo $fullRepo `
        -f environment=$Environment `
        -f release_id=$ReleaseId `
        -f ref=$ref

    if ($LASTEXITCODE -ne 0) {
        throw "workflow run feilet for $fullRepo"
    }

    Write-Host "  OK Workflow startet" -ForegroundColor Green
}

$now = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
switch ($Environment) {
    'test' {
        $manifest.status.testDeploy.state = 'ok'
        $manifest.status.testDeploy.updatedAt = $now
    }
    'production' {
        $manifest.status.productionDeploy.state = 'ok'
        $manifest.status.productionDeploy.updatedAt = $now
    }
}

Save-Manifest -ReleaseId $ReleaseId -Data $manifest | Out-Null

Write-Host ""
Write-Host "Deploy trigget. Folg med i GitHub Actions." -ForegroundColor Green
Write-Host ""
Write-Host "Etter smoke er OK, oppdater manifest:"
Write-Host "  npm run release:mark-smoke -- -ReleaseId $ReleaseId -Environment $Environment"
Write-Host ""
Write-Host "Eller sjekk status:"
Write-Host "  npm run release:check -- -ReleaseId $ReleaseId"
