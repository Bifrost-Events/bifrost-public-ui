#!/usr/bin/env pwsh
# Registrer manuell godkjenning i release-manifest.

param(
    [Parameter(Mandatory)][string]$ReleaseId,
    [Parameter(Mandatory)]
    [ValidateSet('quality', 'quality-override', 'test', 'production-override')]
    [string]$Type,
    [Parameter(Mandatory)][string]$By,
    [Parameter(Mandatory)][string]$Reason
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. "$PSScriptRoot\_lib.ps1"

$loaded = Load-Manifest -ReleaseId $ReleaseId
$manifest = $loaded.Data

$approval = [ordered]@{
    type = $Type
    method = 'manual'
    by = $By
    reason = $Reason
    timestamp = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
}

$approvals = @($manifest.approvals) + @($approval)
$manifest.approvals = $approvals

switch ($Type) {
    'quality' {
        $manifest.status.quality.state = 'approved'
        $manifest.status.quality.updatedAt = $approval.timestamp
    }
    'quality-override' {
        $manifest.status.quality.state = 'approved'
        $manifest.status.quality.updatedAt = $approval.timestamp
        Write-Host "Quality override registrert." -ForegroundColor Yellow
    }
    'test' {
        $manifest.status.testApproval.state = 'approved'
        $manifest.status.testApproval.updatedAt = $approval.timestamp
    }
    'production-override' {
        $manifest.status.testApproval.state = 'approved'
        $manifest.status.testApproval.updatedAt = $approval.timestamp
        Write-Host "Production override (test-godkjenning hoppet over) registrert." -ForegroundColor Yellow
    }
}

$path = Save-Manifest -ReleaseId $ReleaseId -Data $manifest

Write-Host ""
Write-Host "Godkjenning lagret: $Type" -ForegroundColor Green
Write-Host "Manifest: $path"
Write-Host ""
Write-Host "Neste steg:"
& "$PSScriptRoot\release-check.ps1" -ReleaseId $ReleaseId
