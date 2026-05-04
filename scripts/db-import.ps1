param(
    [string]$DumpFile = "database/snapshots/isp360-data.sql.gz"
)

$ErrorActionPreference = "Stop"
$repoRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $repoRoot ".env"

function Get-EnvValue {
    param(
        [string]$Key,
        [string]$DefaultValue = ""
    )

    if (Test-Path "Env:$Key") {
        return (Get-Item "Env:$Key").Value
    }

    if (Test-Path $envFile) {
        $line = Get-Content $envFile | Where-Object {
            $_ -match "^\s*$Key\s*=" -and $_ -notmatch "^\s*#"
        } | Select-Object -First 1

        if ($line) {
            $value = ($line -split "=", 2)[1].Trim()
            if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
                $value = $value.Substring(1, $value.Length - 2)
            }
            return $value
        }
    }

    return $DefaultValue
}

$dbHost = Get-EnvValue -Key "DB_HOST" -DefaultValue "127.0.0.1"
$dbPort = Get-EnvValue -Key "DB_PORT" -DefaultValue "3306"
$dbName = Get-EnvValue -Key "DB_NAME" -DefaultValue "isp360"
$dbUser = Get-EnvValue -Key "DB_USER" -DefaultValue "root"
$dbPass = Get-EnvValue -Key "DB_PASS" -DefaultValue ""

$mysqlBinDir = Get-EnvValue -Key "MYSQL_BIN_DIR" -DefaultValue "D:/laragon/bin/mysql/mysql-8.0.30-winx64/bin"
$mysql = Join-Path $mysqlBinDir "mysql.exe"
if (!(Test-Path $mysql)) {
    throw "mysql client not found at $mysql. Set MYSQL_BIN_DIR in .env"
}

$dumpPath = Join-Path $repoRoot $DumpFile
$legacyDumpFile = "database/snapshots/isp360-data.sql"
$legacyDumpPath = Join-Path $repoRoot $legacyDumpFile
if (!(Test-Path $dumpPath) -and (Test-Path $legacyDumpPath)) {
    $dumpPath = $legacyDumpPath
}

if (!(Test-Path $dumpPath)) {
    Write-Host "Dump file not found: $DumpFile or $legacyDumpFile. Skipping import."
    exit 0
}

$mysqlArgs = @(
    "--host=$dbHost",
    "--port=$dbPort",
    "--user=$dbUser"
)
if ($dbPass -ne "") {
    $mysqlArgs += "--password=$dbPass"
}

function Test-MySqlConnection {
    param(
        [string]$DbHost,
        [string]$DbPort,
        [string]$DbUser,
        [string]$DbPass
    )

    $args = @(
        "--host=$DbHost",
        "--port=$DbPort",
        "--user=$DbUser"
    )

    if ($DbPass -ne "") {
        $args += "--password=$DbPass"
    }

    & $mysql $args -e "SELECT 1" > $null
    if ($LASTEXITCODE -eq 0) {
        return $args
    }

    return $null
}

Write-Host "Connecting to MySQL on $dbHost`:$dbPort..."
$connectedArgs = Test-MySqlConnection -DbHost $dbHost -DbPort $dbPort -DbUser $dbUser -DbPass $dbPass

if ($null -eq $connectedArgs) {
    $fallbackHost = $null
    if ($dbHost -eq "127.0.0.1") {
        $fallbackHost = "localhost"
    } elseif ($dbHost -eq "localhost") {
        $fallbackHost = "127.0.0.1"
    }

    if ($fallbackHost) {
        Write-Host "Primary host failed. Retrying with $fallbackHost`:$dbPort..."
        $connectedArgs = Test-MySqlConnection -DbHost $fallbackHost -DbPort $dbPort -DbUser $dbUser -DbPass $dbPass
        if ($null -ne $connectedArgs) {
            $dbHost = $fallbackHost
            $mysqlArgs = $connectedArgs
            Write-Host "Connected successfully using $dbHost`:$dbPort." -ForegroundColor Yellow
        }
    }
}

if ($null -eq $connectedArgs) {
    Write-Host "Error: Could not connect to MySQL server at $dbHost`:$dbPort." -ForegroundColor Red
    Write-Host "Please ensure Laragon/MySQL is running and DB_HOST/DB_PORT in .env are correct."
    Write-Host "Tip: In Laragon, click Start All, then retry this script."
    exit 1
}

$mysqlArgs = $connectedArgs

$dropCreateSql = "DROP DATABASE IF EXISTS ``$dbName``; CREATE DATABASE ``$dbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& $mysql $mysqlArgs -e $dropCreateSql
if ($LASTEXITCODE -ne 0) {
    throw "Failed to drop/create database $dbName"
}

Write-Host "Importing data into $dbName..."
if ($dumpPath.ToLower().EndsWith('.gz')) {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $fileStream = [System.IO.File]::OpenRead($dumpPath)
    try {
        $gzipStream = New-Object System.IO.Compression.GzipStream($fileStream, [System.IO.Compression.CompressionMode]::Decompress)
        try {
            $streamReader = New-Object System.IO.StreamReader($gzipStream)
            try {
                $sql = $streamReader.ReadToEnd()
            } finally {
                $streamReader.Dispose()
            }
        } finally {
            $gzipStream.Dispose()
        }
    } finally {
        $fileStream.Dispose()
    }

    $sql | & $mysql $mysqlArgs $dbName
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to import dump file $dumpPath"
    }
} else {
    Get-Content -Path $dumpPath -Raw | & $mysql $mysqlArgs $dbName
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to import dump file $dumpPath"
    }
}

Write-Host "Database restored from: $dumpPath"
