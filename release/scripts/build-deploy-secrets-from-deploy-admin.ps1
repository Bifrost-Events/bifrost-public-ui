#!/usr/bin/env pwsh
# Generer deploy-secrets.local.yml fra Deploy-Admin JSON (uten passord).
#
# Usage:
#   powershell -File release/scripts/build-deploy-secrets-from-deploy-admin.ps1
#   powershell -File release/scripts/build-deploy-secrets-from-deploy-admin.ps1 -OutputPath release/config/deploy-secrets.local.yml

param(
    [string]$DeployAdminPath,
    [string]$OutputPath
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$releaseRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$publicUiRoot = Resolve-Path (Join-Path $releaseRoot '..')

if (-not $DeployAdminPath) {
    $DeployAdminPath = Join-Path $publicUiRoot '..\..\platformstandard\Deploy-Admin'
}
$DeployAdminPath = (Resolve-Path $DeployAdminPath).Path

if (-not $OutputPath) {
    $OutputPath = Join-Path $releaseRoot 'config\deploy-secrets.local.yml'
}

$platformPath = Join-Path $DeployAdminPath 'config\platform_data.json'
$deployPath = Join-Path $DeployAdminPath 'config\deploy_data.json'

foreach ($p in @($platformPath, $deployPath)) {
    if (-not (Test-Path $p)) {
        throw "Fant ikke $p"
    }
}

$platform = Get-Content $platformPath -Raw | ConvertFrom-Json

function Get-HostingAccount([string]$AccountId) {
    $acc = $platform.hosting_accounts.$AccountId
    if (-not $acc) {
        throw "Ukjent hosting-konto: $AccountId"
    }
    return $acc
}

function Build-FtpPath([string]$WebrootBase, [string]$FileArea, [string]$AppFolder) {
    return ('{0}/{1}/{2}' -f $WebrootBase.TrimEnd('/'), $FileArea, $AppFolder.TrimEnd('/') + '/')
}

function New-EnvYaml([string]$EnvName, [hashtable]$Vars, [string]$Comment) {
    $out = @()
    $out += "      ${EnvName}:"
    if ($Comment) {
        $out += "        # $Comment"
    }
    $out += '        vars:'
    foreach ($key in @('FTP_HOST', 'FTP_USERNAME', 'FTP_PATH', 'FTP_PORT', 'APP_URL')) {
        $out += "          ${key}: $($Vars[$key])"
    }
    $out += '        secrets:'
    $out += '          FTP_PASSWORD: "SET_LOCALLY"'
    return $out
}

$bifrost = Get-HostingAccount 'csj1wp95j_w1406434'
$jaktfeltkarusell = Get-HostingAccount 'csj1wp95j_w1439551'
$jaktfeltcup = Get-HostingAccount 'csj1wp95j_w1415788'

$lines = @(
    '# AUTO-GENERERT fra Deploy-Admin',
    "# Kilde: $DeployAdminPath",
    '# Sett FTP_PASSWORD per hosting-konto (samme som i Deploy-Admin).',
    '',
    'repositories:',
    '  bifrost-backend:',
    '    environments:'
)

$lines += New-EnvYaml 'test' @{
    FTP_HOST = $bifrost.ftp.host
    FTP_USERNAME = $bifrost.ftp.username
    FTP_PATH = Build-FtpPath $bifrost.webroot_base 'r1465208' 'bifrostbackend/'
    FTP_PORT = '"21"'
    APP_URL = 'https://test.api.bifrostevents.no'
} 'deploy_data: stage=test, r1465208'

$lines += New-EnvYaml 'production' @{
    FTP_HOST = $bifrost.ftp.host
    FTP_USERNAME = $bifrost.ftp.username
    FTP_PATH = Build-FtpPath $bifrost.webroot_base 'r1464762' 'bifrostbackend/'
    FTP_PORT = '"21"'
    APP_URL = 'https://api.bifrostevents.no'
} 'deploy_data: stage=prod, r1464762'

$lines += @('  bifrost-public-ui:', '    environments:')

$lines += New-EnvYaml 'test' @{
    FTP_HOST = $jaktfeltkarusell.ftp.host
    FTP_USERNAME = $jaktfeltkarusell.ftp.username
    FTP_PATH = Build-FtpPath $jaktfeltkarusell.webroot_base 'r1439642' 'bifrostpublicui/'
    FTP_PORT = '"21"'
    APP_URL = 'https://test.jaktfeltcup.no'
} 'deploy_data: test r1439642 (jaktfeltkarusell-konto)'

$lines += New-EnvYaml 'production' @{
    FTP_HOST = $jaktfeltcup.ftp.host
    FTP_USERNAME = $jaktfeltcup.ftp.username
    FTP_PATH = Build-FtpPath $jaktfeltcup.webroot_base 'r1415789' 'bifrostpublicui/'
    FTP_PORT = '"21"'
    APP_URL = 'https://jaktfeltcup.no'
} 'forventet jaktfeltcup prod r1415789 - verifiser i Deploy-Admin'

$lines += @('  bifrost-admin-ui:', '    environments:')

$lines += New-EnvYaml 'test' @{
    FTP_HOST = $bifrost.ftp.host
    FTP_USERNAME = $bifrost.ftp.username
    FTP_PATH = '/customers/d/9/c/csj1wp95j/webroots/PLACEHOLDER_TEST_ADMIN/bifrostadminui/'
    FTP_PORT = '"21"'
    APP_URL = 'https://test.admin.bifrostevents.no'
} 'MANGLER i Deploy-Admin - oppdater PLACEHOLDER'

$lines += New-EnvYaml 'production' @{
    FTP_HOST = $bifrost.ftp.host
    FTP_USERNAME = $bifrost.ftp.username
    FTP_PATH = Build-FtpPath $bifrost.webroot_base 'r1464744' 'bifrostadminui/'
    FTP_PORT = '"21"'
    APP_URL = 'https://admin.bifrostevents.no'
} 'deploy_data: r1464744'

$lines += @('  bifrost-homepage:', '    environments:')

$lines += New-EnvYaml 'production' @{
    FTP_HOST = $bifrost.ftp.host
    FTP_USERNAME = $bifrost.ftp.username
    FTP_PATH = Build-FtpPath $bifrost.webroot_base 'r1464777' 'bifrosthomepage/'
    FTP_PORT = '"21"'
    APP_URL = 'https://bifrostevents.no'
} 'deploy_data: sjurivar/bifrost-homepage r1464777'

$lines += @(
    '',
    '# Passord per hosting-konto (fyll inn over):',
    "#   $($bifrost.ftp.username) -> bifrost-backend + bifrost-admin-ui + bifrost-homepage",
    "#   $($jaktfeltkarusell.ftp.username) -> bifrost-public-ui test",
    "#   $($jaktfeltcup.ftp.username) -> bifrost-public-ui production"
)

Set-Content -Path $OutputPath -Value ($lines -join "`n") -Encoding utf8
Write-Host ('Skrev ' + $OutputPath) -ForegroundColor Green
Write-Host 'Sett FTP_PASSWORD for hver hosting-konto for sync' -ForegroundColor Yellow
