#!/usr/bin/env pwsh
# Marker smoke som OK etter vellykket GitHub Actions smoke (eller manuell verifisering).

param(
    [Parameter(Mandatory)][string]$ReleaseId,
    [Parameter(Mandatory)]
    [ValidateSet('test', 'production')]
    [string]$Environment
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

$loaded = Load-Manifest -ReleaseId $ReleaseId
$manifest = $loaded.Data
$now = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')

switch ($Environment) {
    'test' {
        $manifest.status.testSmoke.state = 'ok'
        $manifest.status.testSmoke.updatedAt = $now
    }
    'production' {
        $manifest.status.productionSmoke.state = 'ok'
        $manifest.status.productionSmoke.updatedAt = $now
    }
}

$path = Save-Manifest -ReleaseId $ReleaseId -Data $manifest
Write-Host "Smoke markert OK for $Environment i $ReleaseId" -ForegroundColor Green
Write-Host "Manifest: $path"
