# Background worker: applies winget upgrades and restarts Apache. Updates job JSON for admin UI polling.

param(
    [Parameter(Mandatory)][string]$JobId,
    [Parameter(Mandatory)][string]$JobFile,
    [Parameter(Mandatory)][string]$LogFile,
    [Parameter(Mandatory)][string]$Root
)

$ErrorActionPreference = 'Continue'

function Write-Log([string]$Message) {
    $line = (Get-Date -Format o) + ' ' + $Message
    Add-Content -Path $LogFile -Value $line -Encoding UTF8
}

function Read-Job {
    if (-not (Test-Path $JobFile)) { return $null }
    try {
        return Get-Content -Path $JobFile -Raw -Encoding UTF8 | ConvertFrom-Json
    } catch {
        return $null
    }
}

function Write-Job([object]$Job) {
    $json = $Job | ConvertTo-Json -Depth 12
    Set-Content -Path $JobFile -Value $json -Encoding UTF8
}

function Update-JobStatus([string]$Status, [string]$Step, [hashtable]$Extra = @{}) {
    $job = Read-Job
    if ($null -eq $job) { return }
    $job.status = $Status
    $job.step = $Step
    if ($Status -eq 'running' -and -not $job.started_at) {
        $job.started_at = (Get-Date).ToString('o')
    }
    if ($Status -in @('completed', 'failed')) {
        $job.finished_at = (Get-Date).ToString('o')
    }
    foreach ($key in $Extra.Keys) {
        $job.$key = $Extra[$key]
    }
    Write-Job $job
}

function Restart-ApacheService {
    $restarted = @()
    $serviceNames = @('Apache2.4', 'apache2.4', 'Apache24', 'Apache')
    foreach ($name in $serviceNames) {
        $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
        if ($svc) {
            Write-Log "Restarting Windows service: $name"
            Restart-Service -Name $name -Force -ErrorAction SilentlyContinue
            $restarted += $name
            return $restarted
        }
    }

    $httpd = 'C:\Apache24\bin\httpd.exe'
    if (Test-Path $httpd) {
        Write-Log 'Running httpd -k restart'
        & $httpd -k restart 2>&1 | ForEach-Object { Write-Log $_ }
        $restarted += 'httpd -k restart'
    } else {
        Write-Log 'Apache service not found; skip restart'
    }
    return $restarted
}

Write-Log "Worker started job=$JobId"
Update-JobStatus 'running' 'starting'

$job = Read-Job
if ($null -eq $job) {
    Write-Log 'Job file missing'
    exit 1
}

if ($job.job_id -ne $JobId) {
    Write-Log 'Job ID mismatch'
    exit 1
}

$packagesUpgraded = @()
$servicesRestarted = @()
$phpUpgraded = $false

try {
    $planPackages = @($job.plan.packages)
    foreach ($pkg in $planPackages) {
        if (-not $pkg.update_available) { continue }
        $id = $pkg.winget_id
        $label = $pkg.label
        if (-not $id) { continue }

        Update-JobStatus 'running' "upgrading:$($pkg.key)"
        Write-Log "Upgrading $label ($id)"
        winget upgrade --id $id --exact --accept-package-agreements --accept-source-agreements 2>&1 | ForEach-Object { Write-Log $_ }
        if ($LASTEXITCODE -eq 0 -or $LASTEXITCODE -eq -1978335189) {
            $packagesUpgraded += $label
            if ($pkg.key -eq 'php') { $phpUpgraded = $true }
        } else {
            Write-Log "winget upgrade exit code $LASTEXITCODE for $id"
        }
    }

    if ($phpUpgraded) {
        Update-JobStatus 'running' 'restarting_services'
        $servicesRestarted = @(Restart-ApacheService)
        Start-Sleep -Seconds 3
    }

    Update-JobStatus 'running' 'verifying'
    $verifyScript = Join-Path $Root 'scripts\verify-after-runtime-update.php'
    $compatOk = $false
    if (Test-Path $verifyScript) {
        php $verifyScript 2>&1 | ForEach-Object { Write-Log $_ }
        $compatOk = ($LASTEXITCODE -eq 0)
    }

    Update-JobStatus 'completed' 'done' @{
        packages_upgraded = $packagesUpgraded
        services_restarted = $servicesRestarted
        compatibility_ok = $compatOk
        error = ''
    }
    Write-Log 'Worker completed successfully'
    exit 0
} catch {
    Write-Log "Worker failed: $($_.Exception.Message)"
    Update-JobStatus 'failed' 'error' @{
        error = $_.Exception.Message
        packages_upgraded = $packagesUpgraded
        services_restarted = $servicesRestarted
    }
    exit 1
}
