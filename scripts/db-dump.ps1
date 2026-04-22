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
$maxDumpMb = [int](Get-EnvValue -Key "DB_DUMP_MAX_MB" -DefaultValue "50")

$mysqlBinDir = Get-EnvValue -Key "MYSQL_BIN_DIR" -DefaultValue "D:/laragon/bin/mysql/mysql-8.0.30-winx64/bin"
$mysqldump = Join-Path $mysqlBinDir "mysqldump.exe"
if (!(Test-Path $mysqldump)) {
    throw "mysqldump not found at $mysqldump. Set MYSQL_BIN_DIR in .env"
}

$dumpPath = Join-Path $repoRoot $DumpFile
$dumpDir = Split-Path -Parent $dumpPath
if (!(Test-Path $dumpDir)) {
    New-Item -ItemType Directory -Path $dumpDir | Out-Null
}

$tempSqlPath = Join-Path $dumpDir "isp360-data.tmp.sql"

$args = @(
    "--host=$dbHost",
    "--port=$dbPort",
    "--user=$dbUser",
    "--single-transaction",
    "--routines",
    "--triggers",
    "--events",
    "--add-drop-table",
    "--databases",
    $dbName
)

if ($dbPass -ne "") {
    $args += "--password=$dbPass"
}

if (Test-Path $tempSqlPath) {
    Remove-Item -Path $tempSqlPath -Force
}

& $mysqldump @args | Out-File -FilePath $tempSqlPath -Encoding utf8
if ($LASTEXITCODE -ne 0) {
    throw "mysqldump failed with exit code $LASTEXITCODE"
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$inputStream = [System.IO.File]::OpenRead($tempSqlPath)
$outputStream = [System.IO.File]::Create($dumpPath)
try {
    $gzipStream = New-Object System.IO.Compression.GzipStream($outputStream, [System.IO.Compression.CompressionLevel]::Optimal)
    try {
        $inputStream.CopyTo($gzipStream)
    } finally {
        $gzipStream.Dispose()
    }
} finally {
    $inputStream.Dispose()
    $outputStream.Dispose()
}

Remove-Item -Path $tempSqlPath -Force

$legacyDumpPath = Join-Path $dumpDir "isp360-data.sql"
if (Test-Path $legacyDumpPath) {
    Remove-Item -Path $legacyDumpPath -Force
}

$dumpBytes = (Get-Item $dumpPath).Length
$maxDumpBytes = $maxDumpMb * 1MB
if ($dumpBytes -gt $maxDumpBytes) {
    throw "Dump size $([math]::Round($dumpBytes / 1MB, 2))MB exceeds limit ${maxDumpMb}MB. Increase DB_DUMP_MAX_MB in .env if expected."
}

Write-Host "Database dump created: $DumpFile ($([math]::Round($dumpBytes / 1MB, 2))MB)"
