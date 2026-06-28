# GitHub API integration for Cursor
# Reads token from config/github.local.php (never committed)

param(
    [string] $ConfigPath = ""
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$configFile = Join-Path $root "config\github.local.php"
if (-not (Test-Path $configFile)) {
    Write-Host ""
    Write-Host "Missing config/github.local.php" -ForegroundColor Red
    Write-Host ""
    Write-Host "1. Copy config\github.local.example.php to config\github.local.php"
    Write-Host "2. Paste your GitHub token into GITHUB_TOKEN"
    Write-Host "3. Run this script again"
    Write-Host ""
    Write-Host "Guide: docs\CURSOR_GITHUB_INTEGRATION.md"
    exit 1
}

$json = php (Join-Path $root "scripts\load-github-config.php")
if (-not $json) {
    Write-Error "Could not read GitHub config."
}

$cfg = $json | ConvertFrom-Json
$token = [string]$cfg.GITHUB_TOKEN
$user = [string]$cfg.GITHUB_USER
$repo = [string]$cfg.GITHUB_REPO
$branch = [string]$cfg.GITHUB_BRANCH
$private = [bool]$cfg.GITHUB_PRIVATE

if ($token -eq "" -or $user -eq "" -or $repo -eq "") {
    Write-Error "Set GITHUB_TOKEN, GITHUB_USER, and GITHUB_REPO in config/github.local.php"
}

if ($branch -eq "") {
    $branch = git rev-parse --abbrev-ref HEAD
}

Write-Host "GitHub: $user/$repo  branch: $branch" -ForegroundColor Cyan

$headers = @{
    Authorization = "Bearer $token"
    Accept = "application/vnd.github+json"
    "X-GitHub-Api-Version" = "2022-11-28"
}

$repoUrl = "https://api.github.com/repos/$user/$repo"
$exists = $false
try {
    Invoke-RestMethod -Uri $repoUrl -Headers $headers -Method Get | Out-Null
    $exists = $true
    Write-Host "Repository already exists on GitHub." -ForegroundColor Yellow
} catch {
    if ($_.Exception.Response.StatusCode.value__ -ne 404) {
        throw
    }
}

if (-not $exists) {
    Write-Host "Creating repository via GitHub API..." -ForegroundColor Green
    $body = @{
        name = $repo
        private = $private
        auto_init = $false
        description = "Mirza Nabeel Ahmed — portfolio site (PHP v5)"
    } | ConvertTo-Json
    Invoke-RestMethod -Uri "https://api.github.com/user/repos" -Method Post -Headers $headers -Body $body -ContentType "application/json"
    Write-Host "Created https://github.com/$user/$repo"
}

$remoteUrl = "https://github.com/$user/$repo.git"
$remotes = git remote
if ($remotes -contains "origin") {
    git remote set-url origin $remoteUrl
} else {
    git remote add origin $remoteUrl
}

$status = git status --porcelain
if ($status) {
    Write-Host "Uncommitted changes — commit in Cursor Source Control first." -ForegroundColor Yellow
    git status --short
    exit 1
}

Write-Host "Pushing to GitHub (token auth, one-time)..." -ForegroundColor Green
$pushUrl = "https://x-access-token:$token@github.com/$user/$repo.git"
$env:GIT_TERMINAL_PROMPT = "0"
git push $pushUrl "${branch}:${branch}"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Push failed. Check token has 'repo' scope." -ForegroundColor Red
    exit 1
}

git branch --set-upstream-to=origin/$branch $branch 2>$null
git config branch.$branch.remote origin
git config branch.$branch.merge "refs/heads/$branch"

Write-Host ""
Write-Host "Success! Repository:" -ForegroundColor Green
Write-Host "  https://github.com/$user/$repo"
Write-Host ""
Write-Host "Cursor: open Source Control and use Push/Sync from now on."
Write-Host "Credentials should be saved in Windows Git Credential Manager."
Write-Host ""
Write-Host "Optional: GitHub -> Settings -> General -> Default branch -> $branch"
