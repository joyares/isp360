$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

git config core.hooksPath .githooks

Write-Host "Git hooks path set to .githooks"
Write-Host "pre-push will export compressed database dump (.sql.gz)"
Write-Host "post-merge (git pull) will drop and import database from snapshot"
