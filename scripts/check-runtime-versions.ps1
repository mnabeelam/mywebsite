# Reports installed vs latest stable versions for tools used with this site.
# Does not install updates. Use upgrade-runtimes.ps1 after reviewing this report.

$ErrorActionPreference = 'Continue'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

function Get-VersionFromText {
    param([string]$Text, [string]$Pattern)
    if ($Text -match $Pattern) { return $Matches[1] }
    return $null
}

function Get-InstalledVersion {
    param([string]$Name)
    switch ($Name) {
        'PHP' {
            try { return (php -r "echo PHP_VERSION;").Trim() } catch { return $null }
        }
        'Node.js' {
            try { return (node -v).Trim().TrimStart('v') } catch { return $null }
        }
        'Git' {
            try {
                $v = git --version
                return Get-VersionFromText $v 'git version ([0-9.]+)'
            } catch { return $null }
        }
        'Apache' {
            $httpd = 'C:\Apache24\bin\httpd.exe'
            if (Test-Path $httpd) {
                $v = & $httpd -v 2>&1 | Out-String
                return Get-VersionFromText $v 'Apache/([0-9.]+)'
            }
            return $null
        }
        '.NET SDK' {
            try { return (dotnet --version).Trim() } catch { return $null }
        }
    }
    return $null
}

function Get-LatestFromUrl {
    param([string]$Name)
    try {
        switch ($Name) {
            'PHP' {
                $json = Invoke-RestMethod -Uri 'https://www.php.net/releases/index.php?json&version=8&max=1' -TimeoutSec 12
                return $json[0].version
            }
            'Node.js' {
                $json = Invoke-RestMethod -Uri 'https://nodejs.org/dist/index.json' -TimeoutSec 12
                $lts = $json | Where-Object { $_.lts -ne $false } | Select-Object -First 1
                if ($lts) { return ($lts.version -replace '^v', '') }
                return ($json[0].version -replace '^v', '')
            }
            'Git' {
                $rel = Invoke-RestMethod -Uri 'https://api.github.com/repos/git-for-windows/git/releases/latest' -TimeoutSec 12
                return ($rel.tag_name -replace '^v', '')
            }
        }
    } catch {
        return $null
    }
    return $null
}

Write-Host '=== Runtime version report (read-only) ===' -ForegroundColor Cyan
Write-Host "Site root: $root"
Write-Host ''

$tools = @('PHP', 'Node.js', 'Git', 'Apache', '.NET SDK')
foreach ($tool in $tools) {
    $installed = Get-InstalledVersion $tool
    $latest = if ($tool -in @('PHP', 'Node.js', 'Git')) { Get-LatestFromUrl $tool } else { $null }
    $status = 'unknown'
    if ($installed -and $latest) {
        $status = if ([version]$installed -ge [version]$latest) { 'current' } else { 'UPDATE AVAILABLE' }
    } elseif ($installed) {
        $status = 'installed (latest not checked)'
    } else {
        $status = 'not installed'
    }
    Write-Host "$tool"
    Write-Host "  Installed : $(if ($installed) { $installed } else { '—' })"
    Write-Host "  Latest    : $(if ($latest) { $latest } else { '—' })"
    Write-Host "  Status    : $status"
    Write-Host ''
}

Write-Host '=== Site compatibility (PHP) ===' -ForegroundColor Cyan
php "$root\scripts\verify-after-runtime-update.php"
$compatExit = $LASTEXITCODE

Write-Host ''
Write-Host 'Auto OS upgrades and automatic code rewrites are NOT enabled from the website.' -ForegroundColor Yellow
Write-Host 'To apply package upgrades after backup: scripts\upgrade-runtimes.ps1 -Apply' -ForegroundColor Yellow

exit $compatExit
