#!/usr/bin/env pwsh
# Synk FTP credentials fra deploy-secrets.local.yml til GitHub Environments.
# Sletter ikke ukjente secrets. Logger aldri hemmeligheter.

param(
    [string]$ConfigPath,
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

Assert-GhCli

if (-not $ConfigPath) {
    $ConfigPath = Join-Path (Get-ReleaseRoot) 'config\deploy-secrets.local.yml'
}

if (-not (Test-Path $ConfigPath)) {
    throw "Fant ikke $ConfigPath. Kopier deploy-secrets.example.yml til deploy-secrets.local.yml og fyll inn verdier."
}

$config = Read-YamlConfig -Path $ConfigPath

function Ensure-GithubEnvironment {
    param(
        [string]$Repo,
        [string]$Environment
    )

    if ($DryRun) {
        Write-Host "[dry-run] Opprett/verifiser environment $Environment i $Repo"
        return
    }

    gh api "repos/$Repo/environments/$Environment" -X PUT 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Kunne ikke opprette environment $Environment i $Repo"
    }
}

function Set-GithubEnvVariable {
    param(
        [string]$Repo,
        [string]$Environment,
        [string]$Name,
        [string]$Value
    )

    if ($DryRun) {
        Write-Host "[dry-run] $Repo env=$Environment var $Name"
        return
    }

    $Value | gh variable set $Name --repo $Repo --env $Environment
    if ($LASTEXITCODE -ne 0) {
        throw "Kunne ikke sette variable $Name for $Repo/$Environment"
    }
    Write-Host "  var $Name" -ForegroundColor DarkGray
}

function Set-GithubEnvSecret {
    param(
        [string]$Repo,
        [string]$Environment,
        [string]$Name,
        [string]$Value
    )

    if ($Value -eq 'SET_LOCALLY' -or [string]::IsNullOrWhiteSpace($Value)) {
        Write-Host "  hopper over secret $Name (ikke satt)" -ForegroundColor Yellow
        return
    }

    if ($DryRun) {
        Write-Host "[dry-run] $Repo env=$Environment secret $Name"
        return
    }

    $Value | gh secret set $Name --repo $Repo --env $Environment
    if ($LASTEXITCODE -ne 0) {
        throw "Kunne ikke sette secret $Name for $Repo/$Environment"
    }
    Write-Host "  secret $Name (satt)" -ForegroundColor DarkGray
}

foreach ($repoProp in $config.repositories.PSObject.Properties) {
    $repoKey = $repoProp.Name
    $repoName = Resolve-GithubRepoSlug -RepoKey $repoKey
    $repoData = $repoProp.Value

    Write-Host ""
    Write-Host "Repo: $repoName" -ForegroundColor Cyan

    foreach ($envProp in $repoData.environments.PSObject.Properties) {
        $envName = $envProp.Name
        $envData = $envProp.Value

        Write-Host "  Environment: $envName"
        Ensure-GithubEnvironment -Repo $repoName -Environment $envName

        if ($envData.vars) {
            foreach ($varProp in $envData.vars.PSObject.Properties) {
                Set-GithubEnvVariable -Repo $repoName -Environment $envName -Name $varProp.Name -Value $varProp.Value
            }
        }

        if ($envData.secrets) {
            foreach ($secProp in $envData.secrets.PSObject.Properties) {
                Set-GithubEnvSecret -Repo $repoName -Environment $envName -Name $secProp.Name -Value $secProp.Value
            }
        }
    }
}

Write-Host ""
Write-Host "Synk fullfort." -ForegroundColor Green
