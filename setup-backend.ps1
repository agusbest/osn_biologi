# Memasang kerangka Laravel 12 + Sanctum dan menggabungkannya dengan kode di folder backend/.
# Jalankan dari akar proyek:  powershell -ExecutionPolicy Bypass -File .\setup-backend.ps1
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
$be   = Join-Path $root 'backend'
$tmp  = Join-Path $root '.laravel-skeleton'

if (Test-Path (Join-Path $be 'artisan')) { Write-Host 'Backend sudah terpasang (artisan ditemukan).'; exit 0 }
foreach ($cmd in 'php','composer') {
  if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) { throw "$cmd tidak ditemukan di PATH. Pasang PHP 8.2+ dan Composer dulu." }
}

if (Test-Path $tmp) { Remove-Item -Recurse -Force $tmp }
composer create-project laravel/laravel $tmp "^12.0" --prefer-dist --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'composer create-project gagal' }

# Salin kerangka ke backend/ TANPA menimpa berkas kita (/XC /XN /XO = lewati berkas yang sudah ada).
robocopy $tmp $be /E /XC /XN /XO /NFL /NDL /NJH /NJS /NP | Out-Null
if ($LASTEXITCODE -ge 8) { throw 'robocopy gagal' }
Remove-Item -Recurse -Force $tmp

Set-Location $be
Copy-Item .env.example .env -Force          # pakai .env.example kita (MySQL), bukan bawaan kerangka
Remove-Item -ErrorAction SilentlyContinue database\database.sqlite

composer require laravel/sanctum --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'composer require sanctum gagal' }
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag=sanctum-migrations --no-interaction
php artisan key:generate
php artisan storage:link

Write-Host ''
Write-Host 'Selesai. Langkah berikutnya:'
Write-Host '  1) Edit backend\.env (DB_DATABASE / DB_USERNAME / DB_PASSWORD), buat database MySQL-nya.'
Write-Host '  2) cd backend; php artisan migrate --seed'
Write-Host '  3) php artisan serve      (API di http://localhost:8000)'
Write-Host '  4) php artisan test       (jalankan feature test)'
