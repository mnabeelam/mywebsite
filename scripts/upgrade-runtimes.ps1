# Optional guided upgrades via winget. Requires admin PowerShell for some packages.
# Does NOT modify application source code — run verify-after-runtime-update.php after.

param(
    [switch]$Apply
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

$packages = @(
    @{ Label = 'PHP 8.3'; Id = 'PHP.PHP.8.3' },
    @{ Label = 'Node.js LTS'; Id = 'OpenJS.NodeJS.LTS' },
    @{ Label = 'Git'; Id = 'Git.Git' }
)

function Test-Winget {
    try {
        winget --version | Out-Null
        return $true
    } catch {
        return $false
    }
}

Write-Host '=== Runtime upgrade helper ===' -ForegroundColor Cyan
Write-Host 'Backup the site before upgrading PHP or Apache.'
Write-Host ''

if (-not (Test-Winget)) {
    Write-Host 'winget is not available. Install App Installer from Microsoft Store, then retry.' -ForegroundColor Red
    exit 1
}

foreach ($pkg in $packages) {
    Write-Host "Checking $($pkg.Label) ($($pkg.Id))..." -ForegroundColor Gray
    winget show --id $pkg.Id --exact 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  Package not found in winget: $($pkg.Id)" -ForegroundColor Yellow
        continue
    }
    if ($Apply) {
        Write-Host "  Upgrading $($pkg.Label)..." -ForegroundColor Green
        winget upgrade --id $pkg.Id --exact --accept-package-agreements --accept-source-agreements
    } else {
        winget upgrade --id $pkg.Id --exact 2>$null
    }
}

if (-not $Apply) {
    Write-Host ''
    Write-Host 'Preview only. To apply upgrades run:' -ForegroundColor Yellow
    Write-Host '  powershell -ExecutionPolicy Bypass -File scripts\upgrade-runtimes.ps1 -Apply' -ForegroundColor Yellow
    exit 0
}

Write-Host ''
Write-Host 'Running post-upgrade compatibility checks...' -ForegroundColor Cyan
php "$root\scripts\verify-after-runtime-update.php"
exit $LASTEXITCODE
