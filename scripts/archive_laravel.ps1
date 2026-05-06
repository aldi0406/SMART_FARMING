#!/usr/bin/env pwsh
<#
Archive common Laravel files and folders into a timestamped backup directory.
Run this from the repository root in PowerShell: `.	ools\archive_laravel.ps1`
#>
$ts = Get-Date -Format "yyyyMMdd_HHmmss"
$dest = Join-Path -Path (Get-Location) -ChildPath "legacy_laravel_backup_$ts"
New-Item -ItemType Directory -Path $dest -Force | Out-Null

$items = @(
    'artisan', 'app', 'bootstrap', 'config', 'database', 'public', 'resources',
    'routes', 'storage', 'tests', 'vendor', 'composer.json', 'composer.lock',
    'package.json', 'vite.config.js', 'phpunit.xml', '.env'
)

foreach ($i in $items) {
    if (Test-Path $i) {
        try {
            Move-Item -Path $i -Destination $dest -Force -ErrorAction Stop
        } catch {
            Write-Warning "Could not move $i: $_"
        }
    }
}

Write-Host "Laravel files archived to: $dest"
