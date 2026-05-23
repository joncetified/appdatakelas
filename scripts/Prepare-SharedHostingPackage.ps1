[CmdletBinding()]
param(
    [string] $Domain = "xxx.rplkodingan.com",
    [string] $AppFolder = "",
    [string] $OutputPath = "_hosting_upload",
    [string] $DbHost = "",
    [string] $DbDatabase = "database",
    [string] $DbUsername = "username_database",
    [string] $DbPassword = "password_database"
)

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$publicPath = Join-Path $projectRoot "public"
$outputRoot = Join-Path $projectRoot $OutputPath

if (-not (Test-Path $publicPath)) {
    throw "Folder public tidak ditemukan: $publicPath"
}

if ([string]::IsNullOrWhiteSpace($AppFolder)) {
    $AppFolder = "app_{0}" -f (Get-Random -Minimum 100000 -Maximum 999999)
}

if ([string]::IsNullOrWhiteSpace($DbHost)) {
    $DbHost = $Domain
}

if (Test-Path $outputRoot) {
    throw "Folder output sudah ada: $outputRoot. Hapus atau rename folder itu dulu, lalu jalankan ulang."
}

New-Item -ItemType Directory -Path $outputRoot | Out-Null
$appRoot = Join-Path $outputRoot $AppFolder
New-Item -ItemType Directory -Path $appRoot | Out-Null

Get-ChildItem -LiteralPath $publicPath -Force | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination $outputRoot -Recurse -Force
}

$skipNames = @(
    ".git",
    ".idea",
    ".vscode",
    "node_modules",
    "public",
    $OutputPath
)

Get-ChildItem -LiteralPath $projectRoot -Force | Where-Object {
    $skipNames -notcontains $_.Name
} | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination $appRoot -Recurse -Force
}

$indexPath = Join-Path $outputRoot "index.php"
$indexPhp = @"
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists(`$maintenance = __DIR__.'/$AppFolder/storage/framework/maintenance.php')) {
    require `$maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/$AppFolder/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application `$app */
`$app = require_once __DIR__.'/$AppFolder/bootstrap/app.php';

`$app->handleRequest(Request::capture());
"@

Set-Content -LiteralPath $indexPath -Value $indexPhp -Encoding UTF8

$envPath = Join-Path $appRoot ".env"
if (-not (Test-Path $envPath)) {
    Copy-Item -LiteralPath (Join-Path $appRoot ".env.example") -Destination $envPath
}

function Set-EnvValue {
    param(
        [string[]] $Lines,
        [string] $Key,
        [string] $Value
    )

    $pattern = "^{0}=" -f [regex]::Escape($Key)
    $replacement = "{0}={1}" -f $Key, $Value
    $found = $false

    $updated = $Lines | ForEach-Object {
        if ($_ -match $pattern) {
            $found = $true
            $replacement
        } else {
            $_
        }
    }

    if (-not $found) {
        $updated += $replacement
    }

    return $updated
}

$envLines = Get-Content -LiteralPath $envPath
$envLines = Set-EnvValue -Lines $envLines -Key "APP_ENV" -Value "production"
$envLines = Set-EnvValue -Lines $envLines -Key "APP_DEBUG" -Value "false"
$envLines = Set-EnvValue -Lines $envLines -Key "APP_URL" -Value "https://$Domain"
$envLines = Set-EnvValue -Lines $envLines -Key "DB_CONNECTION" -Value "mysql"
$envLines = Set-EnvValue -Lines $envLines -Key "DB_HOST" -Value $DbHost
$envLines = Set-EnvValue -Lines $envLines -Key "DB_PORT" -Value "3306"
$envLines = Set-EnvValue -Lines $envLines -Key "DB_DATABASE" -Value $DbDatabase
$envLines = Set-EnvValue -Lines $envLines -Key "DB_USERNAME" -Value $DbUsername
$envLines = Set-EnvValue -Lines $envLines -Key "DB_PASSWORD" -Value $DbPassword
Set-Content -LiteralPath $envPath -Value $envLines -Encoding UTF8

Write-Host "Paket hosting berhasil dibuat."
Write-Host "Folder upload : $outputRoot"
Write-Host "Folder app    : $AppFolder"
Write-Host "Upload semua isi folder $OutputPath ke server FileZilla."
