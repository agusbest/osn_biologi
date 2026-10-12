#!/usr/bin/env bash
# Memasang kerangka Laravel 12 + Sanctum dan menggabungkannya dengan kode di backend/.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"; BE="$ROOT/backend"; TMP="$ROOT/.laravel-skeleton"

[ -f "$BE/artisan" ] && { echo "Backend sudah terpasang."; exit 0; }
command -v php >/dev/null && command -v composer >/dev/null || { echo "Butuh php (8.2+) dan composer di PATH."; exit 1; }

rm -rf "$TMP"
composer create-project laravel/laravel "$TMP" "^12.0" --prefer-dist --no-interaction
cp -rn "$TMP"/. "$BE"/        # -n: jangan timpa berkas kita
rm -rf "$TMP"

cd "$BE"
cp -f .env.example .env
rm -f database/database.sqlite
composer require laravel/sanctum --no-interaction
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag=sanctum-migrations --no-interaction
php artisan key:generate
php artisan storage:link
cat <<'MSG'

Selesai. Berikutnya:
  1) Edit backend/.env (DB_*) dan buat database MySQL-nya.
  2) cd backend && php artisan migrate --seed
  3) php artisan serve     |  php artisan test
MSG
