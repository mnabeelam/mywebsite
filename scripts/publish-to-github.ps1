# Publish this project to GitHub
# Usage:
#   .\scripts\publish-to-github.ps1 -RepoUrl "https://github.com/username/repo.git"
#   .\scripts\publish-to-github.ps1 -RepoUrl "https://github.com/username/repo.git" -Branch cleanup-phase-1

param(
    [Parameter(Mandatory = $true)]
    [string] $RepoUrl,
    [string] $Branch = ""
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

Write-Host "Project: $root" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".git")) {
    Write-Error "Not a git repository."
}

if ($Branch -eq "") {
    $Branch = git rev-parse --abbrev-ref HEAD
}

Write-Host "Branch to push: $Branch" -ForegroundColor Cyan

$status = git status --porcelain
if ($status) {
    Write-Host "Warning: uncommitted changes exist. Commit them in Cursor Source Control first, then run this script again." -ForegroundColor Yellow
    git status --short
    exit 1
}

$remotes = git remote
if ($remotes -contains "origin") {
    Write-Host "Updating remote origin..." -ForegroundColor Yellow
    git remote set-url origin $RepoUrl
} else {
    Write-Host "Adding remote origin (this was missing — why GitHub upload failed)..." -ForegroundColor Yellow
    git remote add origin $RepoUrl
}

Write-Host ""
git remote -v
Write-Host ""
Write-Host "Pushing $Branch to GitHub..." -ForegroundColor Green
git push -u origin $Branch

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Success!" -ForegroundColor Green
    $web = $RepoUrl -replace '\.git$', '' -replace 'git@github\.com:', 'https://github.com/'
    Write-Host "Repo: $web"
    if ($Branch -ne "main") {
        Write-Host ""
        Write-Host "Tip: On GitHub go to Settings -> General -> Default branch to set '$Branch' or merge into main." -ForegroundColor Yellow
    }
} else {
    Write-Host ""
    Write-Host "Push failed. Read docs/GITHUB_UPLOAD.md — usually you need a Personal Access Token for login." -ForegroundColor Red
    exit 1
}
