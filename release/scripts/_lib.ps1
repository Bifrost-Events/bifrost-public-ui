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

    # Legacy / utenfor repos.yml (f.eks. bifrost-admin-ui)
    $legacyOwner = @{
        'bifrost-homepage' = 'sjurivar'
    }
    $owner = if ($legacyOwner.ContainsKey($RepoKey)) { $legacyOwner[$RepoKey] } else { 'Bifrost-Events' }
    Write-Host "  (repo $RepoKey ikke i repos.yml - bruker $owner/$RepoKey)" -ForegroundColor DarkYellow
    return "$owner/$RepoKey"
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
    param(
        [string]$RepoPath,
        [string[]]$ExcludeDirtyPaths = @()
    )

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
        if ($status) {
            foreach ($line in ($status -split "`n")) {
                $line = $line.TrimEnd()
                if (-not $line) { continue }

                $path = $line.Substring(3).Trim().Replace('\', '/')
                $skip = $false
                foreach ($prefix in $ExcludeDirtyPaths) {
                    $normalized = $prefix.Trim().TrimEnd('/').Replace('\', '/')
                    if ($path -eq $normalized -or $path -like "$normalized/*") {
                        $skip = $true
                        break
                    }
                }
                if (-not $skip) {
                    $dirty = $true
                    break
                }
            }
        }

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
            throw 'Ingen release funnet. Kjor release:create forst.'
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

function Invoke-GitQuiet {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$GitArgs
    )

    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & git @GitArgs 2>&1
        return [PSCustomObject]@{
            ExitCode = $LASTEXITCODE
            Output = @($output)
        }
    }
    finally {
        $ErrorActionPreference = $prev
    }
}

function Get-ManifestGitPublishState {
    param([Parameter(Mandatory)][string]$ReleaseId)

    $publicUiRoot = (Get-PublicUiRoot).Path
    $relPath = "release/releases/$ReleaseId/manifest.json".Replace('\', '/')

    Push-Location $publicUiRoot
    try {
        if (-not (Test-Path $relPath)) {
            return [PSCustomObject]@{
                Published = $false
                Detail = "Manifest finnes ikke: $relPath"
            }
        }

        $tracked = Invoke-GitQuiet ls-files -- $relPath
        if ($tracked.Output.Count -eq 0) {
            return [PSCustomObject]@{
                Published = $false
                Detail = 'Manifest er ikke committet (git add + git commit).'
            }
        }

        $diff = Invoke-GitQuiet diff --quiet HEAD -- $relPath
        if ($diff.ExitCode -ne 0) {
            return [PSCustomObject]@{
                Published = $false
                Detail = 'Manifest har ulagrede endringer. Commit for deploy.'
            }
        }

        $branch = (Invoke-GitQuiet rev-parse --abbrev-ref HEAD).Output | Out-String
        $branch = $branch.Trim()
        $remote = 'origin'
        Invoke-GitQuiet fetch $remote $branch | Out-Null
        $upstream = "$remote/$branch"

        $verify = Invoke-GitQuiet rev-parse --verify $upstream
        if ($verify.ExitCode -ne 0) {
            return [PSCustomObject]@{
                Published = $false
                Detail = "Fant ikke $upstream. Push manifest til GitHub forst."
            }
        }

        $remotePath = Invoke-GitQuiet rev-parse "${upstream}:$relPath"
        if ($remotePath.ExitCode -ne 0) {
            return [PSCustomObject]@{
                Published = $false
                Detail = "Manifest finnes ikke pa $upstream. Push til GitHub for deploy."
            }
        }

        $localBlob = (Invoke-GitQuiet hash-object $relPath).Output | Out-String
        $localBlob = $localBlob.Trim()
        $remoteBlob = ($remotePath.Output | Out-String).Trim()
        if ($localBlob -ne $remoteBlob) {
            return [PSCustomObject]@{
                Published = $false
                Detail = "Manifest er committet lokalt men ikke pushet til $upstream."
            }
        }

        return [PSCustomObject]@{
            Published = $true
            Detail = $upstream
        }
    }
    finally {
        Pop-Location
    }
}

function Assert-ManifestPublishedToGit {
    param([Parameter(Mandatory)][string]$ReleaseId)

    $state = Get-ManifestGitPublishState -ReleaseId $ReleaseId
    if (-not $state.Published) {
        $relDir = "release/releases/$ReleaseId"
        throw @"
Deploy stoppet: release-manifest er ikke publisert til GitHub.
$($state.Detail)

  git add $relDir/
  git commit -m "release: $ReleaseId"
  git push

Manifest ma ligge pa origin for deploy kan startes.
"@
    }
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
        'ok' { return '[OK]' }
        'approved' { return '[OK]' }
        'pending' { return '[..]' }
        'failed' { return '[X]' }
        'blocked' { return '[!]' }
        default { return '[?]' }
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
        throw 'Ikke autentisert med gh. Kjor: gh auth login'
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
