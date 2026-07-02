#!/usr/bin/env pwsh
# Opprett release-manifest med commit-pin fra alle repo i release-settet.

param(
    [string]$ReleaseId
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

$reposConfig = Get-ReposConfig
$warnings = @()

if (-not $ReleaseId) {
    $ReleaseId = New-ReleaseId
}

$repositories = @{}
foreach ($prop in $reposConfig.repositories.PSObject.Properties) {
    $key = $prop.Name
    $cfg = $prop.Value
    $repoPath = Resolve-RepoPath -LocalPath $cfg.localPath
    $git = Get-RepoGitInfo -RepoPath $repoPath

    if ($git.Dirty) {
        $warnings += "[!] $key ($($cfg.repo)) har ulagrede endringer i $repoPath"
    }

    $trackOnly = $false
    if ($cfg.PSObject.Properties.Name -contains 'trackOnly') {
        $trackOnly = [bool]$cfg.trackOnly
    }

    $repositories[$key] = [ordered]@{
        owner = $cfg.owner
        repo = $cfg.repo
        branch = $git.Branch
        commit = $git.Commit
        shortCommit = $git.ShortCommit
        dirty = $git.Dirty
        trackOnly = $trackOnly
    }
}

$manifest = [ordered]@{
    releaseId = $ReleaseId
    createdAt = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
    repositories = $repositories
    status = New-ReleaseStatus
    approvals = @()
}

$path = Save-Manifest -ReleaseId $ReleaseId -Data $manifest

Write-Host ""
Write-Host "Release opprettet: $ReleaseId" -ForegroundColor Green
Write-Host "Manifest: $path"
Write-Host ""
Write-Host "Repos:"
foreach ($key in $repositories.Keys) {
    $r = $repositories[$key]
    $icon = if ($r.dirty) { '[!]' } else { '[OK]' }
    $track = if ($r.trackOnly -eq $true) { ' (track-only)' } else { '' }
    Write-Host "  $icon $key $($r.shortCommit)$track"
}

if ($warnings.Count -gt 0) {
    Write-Host ""
    Write-Host "Advarsler:" -ForegroundColor Yellow
    foreach ($w in $warnings) {
        Write-Host "  $w" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Neste steg:"
Write-Host "  npm run release:check -- -ReleaseId $ReleaseId"
Write-Host "  npm run quality:local   # lokal quality for test-deploy"
