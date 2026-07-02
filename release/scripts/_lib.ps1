# Shared helpers for Bifrost release scripts.

function Get-ReleaseRoot {
    $root = Resolve-Path (Join-Path $PSScriptRoot '..')
    return $root.Path
}

function Get-PublicUiRoot {
    return Resolve-Path (Join-Path (Get-ReleaseRoot) '..')
}

function Read-YamlConfig {
    param([Parameter(Mandatory)][string]$Path)

    $publicUiRoot = (Get-PublicUiRoot).Path
    $nodeScript = Join-Path $publicUiRoot 'release\bin\read-yaml.mjs'
    if (-not (Test-Path $nodeScript)) {
        throw "Fant ikke $nodeScript"
    }

    $json = & node $nodeScript $Path 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Kunne ikke lese YAML: $Path`n$json"
    }

    return $json | ConvertFrom-Json
}

function Get-ReposConfig {
    $path = Join-Path (Get-ReleaseRoot) 'config\repos.yml'
    return Read-YamlConfig -Path $path
}

function Resolve-GithubRepoSlug {
    param([Parameter(Mandatory)][string]$RepoKey)

    if ($RepoKey -match '/') {
        return $RepoKey
    }

    $repos = Get-ReposConfig
    foreach ($prop in $repos.repositories.PSObject.Properties) {
        $cfg = $prop.Value
        if ($cfg.repo -eq $RepoKey) {
            return "$($cfg.owner)/$($cfg.repo)"
        }
    }

    throw "Fant ikke GitHub-repo for '$RepoKey' i config/repos.yml"
}

function Get-EnvironmentsConfig {
    $path = Join-Path (Get-ReleaseRoot) 'config\environments.yml'
    return Read-YamlConfig -Path $path
}

function Resolve-RepoPath {
    param([string]$LocalPath)

    $publicUiRoot = (Get-PublicUiRoot).Path
    if ([string]::IsNullOrWhiteSpace($LocalPath) -or $LocalPath -eq '.') {
        return $publicUiRoot
    }

    return Resolve-Path (Join-Path $publicUiRoot $LocalPath)
}

function Get-RepoGitInfo {
    param([string]$RepoPath)

    if (-not (Test-Path (Join-Path $RepoPath '.git'))) {
        throw "Ikke et git-repo: $RepoPath"
    }

    Push-Location $RepoPath
    try {
        $branch = (git rev-parse --abbrev-ref HEAD 2>$null).Trim()
        $commit = (git rev-parse HEAD 2>$null).Trim()
        $short = (git rev-parse --short HEAD 2>$null).Trim()
        $dirty = $false
        $status = git status --porcelain 2>$null
        if ($status) { $dirty = $true }

        return [PSCustomObject]@{
            Branch = $branch
            Commit = $commit
            ShortCommit = $short
            Dirty = $dirty
        }
    }
    finally {
        Pop-Location
    }
}

function Get-ManifestPath {
    param([Parameter(Mandatory)][string]$ReleaseId)

    return Join-Path (Get-ReleaseRoot) "releases\$ReleaseId\manifest.json"
}

function Get-LatestReleaseId {
    $releasesDir = Join-Path (Get-ReleaseRoot) 'releases'
    $dirs = @(Get-ChildItem -Path $releasesDir -Directory -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending)

    if ($dirs.Count -eq 0) {
        return $null
    }

    return $dirs[0].Name
}

function Load-Manifest {
    param([string]$ReleaseId)

    if (-not $ReleaseId) {
        $ReleaseId = Get-LatestReleaseId
        if (-not $ReleaseId) {
            throw 'Ingen release funnet. Kjør release:create først.'
        }
    }

    $path = Get-ManifestPath -ReleaseId $ReleaseId
    if (-not (Test-Path $path)) {
        throw "Manifest finnes ikke: $path"
    }

    $manifest = Get-Content -Path $path -Raw | ConvertFrom-Json
    return [PSCustomObject]@{
        ReleaseId = $ReleaseId
        Path = $path
        Data = $manifest
    }
}

function Save-Manifest {
    param(
        [Parameter(Mandatory)][string]$ReleaseId,
        [Parameter(Mandatory)]$Data
    )

    $dir = Join-Path (Get-ReleaseRoot) "releases\$ReleaseId"
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
    $path = Join-Path $dir 'manifest.json'
    $json = $Data | ConvertTo-Json -Depth 20
    Set-Content -Path $path -Value $json -Encoding utf8
    return $path
}

function New-ReleaseStatus {
    return [PSCustomObject]@{
        quality = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
        testDeploy = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
        testSmoke = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
        testApproval = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
        productionDeploy = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
        productionSmoke = [PSCustomObject]@{ state = 'pending'; updatedAt = $null }
    }
}

function Get-LocalQualityCommand {
    $envConfig = Get-EnvironmentsConfig
    if ($envConfig.localQuality -and $envConfig.localQuality.qualityCommand) {
        return [string]$envConfig.localQuality.qualityCommand
    }
    return 'npm run quality:local'
}

function Get-LocalQualityVerifyNote {
    $envConfig = Get-EnvironmentsConfig
    if ($envConfig.localQuality -and $envConfig.localQuality.manualVerifyNote) {
        return [string]$envConfig.localQuality.manualVerifyNote
    }
    return $null
}

function New-ReleaseId {
    $today = Get-Date -Format 'yyyy-MM-dd'
    $releasesDir = Join-Path (Get-ReleaseRoot) 'releases'
    $existing = @(Get-ChildItem -Path $releasesDir -Directory -Filter "$today-*" -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending)

    $seq = 1
    if ($existing.Count -gt 0) {
        $last = $existing[0].Name
        if ($last -match "$today-(\d+)$") {
            $seq = [int]$Matches[1] + 1
        }
    }

    return ('{0}-{1:D3}' -f $today, $seq)
}

function Format-StatusIcon {
    param([string]$State)

    switch ($State) {
        'ok' { return '✅' }
        'approved' { return '✅' }
        'pending' { return '⏸' }
        'failed' { return '❌' }
        'blocked' { return '⛔' }
        default { return '❓' }
    }
}

function Test-QualityApproved {
    param($Manifest)

    if ($Manifest.status.quality.state -eq 'approved') {
        return $true
    }

    foreach ($approval in $Manifest.approvals) {
        if ($approval.type -in @('quality', 'quality-override') -and $approval.method -eq 'manual') {
            return $true
        }
    }

    return $false
}

function Test-TestApproved {
    param($Manifest)

    if ($Manifest.status.testApproval.state -eq 'approved') {
        return $true
    }

    foreach ($approval in $Manifest.approvals) {
        if ($approval.type -eq 'test' -and $approval.method -eq 'manual') {
            return $true
        }
    }

    return $false
}

function Assert-GhCli {
    if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
        throw 'GitHub CLI (gh) er ikke installert. Installer fra https://cli.github.com/'
    }

    gh auth status 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'Ikke autentisert med gh. Kjør: gh auth login'
    }
}

function Get-DeployableRepos {
    param($ReposConfig)

    $list = @()
    foreach ($prop in $ReposConfig.repositories.PSObject.Properties) {
        $repo = $prop.Value
        $isTrackOnly = $false
        if ($repo.PSObject.Properties.Name -contains 'trackOnly') {
            $isTrackOnly = [bool]$repo.trackOnly
        }
        if ($isTrackOnly) { continue }
        $list += [PSCustomObject]@{
            Key = $prop.Name
            Config = $repo
        }
    }

    return $list | Sort-Object { $_.Config.deployOrder }
}

function Test-RepoDeploysToEnvironment {
    param(
        $RepoConfig,
        [Parameter(Mandatory)][string]$Environment
    )

    if ($RepoConfig.PSObject.Properties.Name -contains 'deployEnvironments') {
        $envs = @($RepoConfig.deployEnvironments)
        return $envs -contains $Environment
    }

    return $true
}
