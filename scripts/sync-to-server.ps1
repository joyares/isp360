param(
    [Parameter(Mandatory = $true)][string]$RemoteHost,
    [Parameter(Mandatory = $true)][string]$RemoteUser,
    [Parameter(Mandatory = $true)][string]$RemotePath,
    [string]$Branch = 'main',
    [switch]$RunSeeder,
    [switch]$RunDbImport,
    [string]$DumpFile = 'database/snapshots/isp360-data.sql.gz'
)

$ErrorActionPreference = 'Stop'

Write-Host "[1/3] Pushing local commits to origin/$Branch ..."
git push origin $Branch

$remote = "$RemoteUser@$RemoteHost"
$remoteCommand = "cd '$RemotePath'; git fetch origin; git checkout $Branch; git pull --ff-only origin $Branch"

if ($RunSeeder) {
    $remoteCommand = "$remoteCommand; php database/seeder.php"
}

if ($RunDbImport) {
    $remoteCommand = "$remoteCommand; bash scripts/db-import.sh '$DumpFile'"
}

Write-Host "[2/3] Pulling latest code on server ($remote) ..."
ssh $remote $remoteCommand

Write-Host "[3/3] Sync complete."
