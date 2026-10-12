# OSN Biology Academy (MVP Fase 1)

E-course Biologi untuk persiapan OSN: **Learn → Practice → Evaluate → Improve**.

- `frontend/`: React + TypeScript + Vite, Tailwind CSS v4, React Router, TanStack Query, Recharts, KaTeX, TipTap.
- `backend/`: Laravel 12 REST API, Sanctum (token Bearer), Policies, Form Request, MySQL.
- `ASSUMPTIONS.md`: asumsi dan keputusan desain. Baca dulu.

> **Status verifikasi.** Frontend sudah lolos `tsc` dan `vite build`, lalu dicoba di browser dengan API tiruan.
> Kode backend **belum pernah dijalankan** karena lingkungan pembuatnya tidak bisa memasang Laravel (Packagist diblokir).
> Hanya sintaks PHP (`php -l`) dan `HtmlSanitizer` yang sudah diuji. Jalankan `php artisan test` di komputer Anda
> dan perbaiki bila ada yang gagal sebelum dipakai.

## Prasyarat
PHP 8.2+ (ekstensi: mbstring, xml, curl, zip, pdo_mysql, fileinfo, gd opsional), Composer 2, Node 20+, MySQL 8 / MariaDB 10.6+.

## 1. Backend
Folder `backend/` hanya berisi kode aplikasi kita (model, controller, migrasi, seeder, test). Kerangka Laravel dipasang oleh skrip:

```powershell
# Windows (PowerShell), dari akar proyek
powershell -ExecutionPolicy Bypass -File .\setup-backend.ps1
# macOS/Linux
./setup-backend.sh
```

Skrip: `composer create-project laravel/laravel ^12` → menggabungkannya tanpa menimpa berkas kita → `composer require laravel/sanctum` → publish migrasi Sanctum → `key:generate` → `storage:link`.

Lalu:
```bash
# buat database:  CREATE DATABASE osn_academy CHARACTER SET utf8mb4;
cd backend
# edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL, FRONTEND_URL, CORS_ALLOWED_ORIGINS
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

### Akun demo (hanya lokal, dari seeder; ganti/hapus sebelum deploy)
| Peran | Email | Kata sandi |
|---|---|---|
| Admin | admin@osn.test | Admin#Osn2026 |
| Siswa Free | siswa.free@osn.test | Siswa#Free2026 |
| Siswa Full Course | siswa.full@osn.test | Siswa#Full2026 |

### Test
```bash
cd backend
php artisan test
```
Mencakup: penilaian benar/salah dan berbobot, kunci/pembahasan tidak bocor sebelum submit, akses premium tanpa enrollment → 403 (termasuk enrollment kedaluwarsa/dibatalkan), konten draft/review/archived tidak terlihat siswa (404), alur status konten, pembayaran manual, sanitasi XSS, rate limit login, aturan rekomendasi dari pengaturan.
Test memakai SQLite in-memory (bawaan `phpunit.xml` Laravel), jadi tidak menyentuh database MySQL Anda.

### Backup database
`php artisan app:db-backup` (mysqldump; menyimpan 14 backup terakhir di `storage/app/backups`). Dijadwalkan harian 02:00 di `routes/console.php`; di server jalankan scheduler (`* * * * * php artisan schedule:run`). Pastikan `mysqldump` ada di PATH.
Audit log tindakan admin dan transaksi ada di tabel `audit_logs` (menu **Audit log** di panel admin).

## 2. Frontend
```bash
cd frontend
cp .env.example .env.local     # VITE_API_URL=http://localhost:8000/api
npm install
npm run dev                    # http://localhost:5173
npm run build                  # hasil di frontend/dist
```

## Peta fitur
| Area | Lokasi |
|---|---|
| Landing, demo materi gratis, legal | `frontend/src/pages/Landing.tsx`, `Public.tsx` |
| Auth (daftar, masuk, lupa/reset sandi) | `AuthController`, `AuthPages.tsx` |
| Dashboard, kurikulum, materi | `Student/Dashboard|Curriculum|LessonController` |
| Bank soal, pengerjaan, pembahasan | `Student/QuestionController`, `AttemptController`, `AttemptService` |
| Evaluasi & rekomendasi | `AnalyticsService`, `SettingsService` |
| Paket, pembayaran manual, enrollment | `Student/BillingController`, `Admin/PaymentController` |
| CMS admin, alur status | `Admin/*`, `StatusController` |

## Ringkasan API (`/api`)
Publik: `GET public/landing`, `GET public/demo`, `POST auth/{register,login,forgot-password,reset-password}`.
Siswa (Bearer): `GET dashboard|curriculum|evaluation|profile|billing|quizzes|questions`, `GET lessons/{id}`, `POST|DELETE lessons/{id}/complete`, `POST attempts`, `GET attempts/{id}`, `PUT attempts/{id}/answers`, `POST attempts/{id}/submit`, `GET attempts/{id}/results`, `POST attempts/{id}/answers/{q}/self-assess`, `POST attempts/{id}/retry`, `POST payments`.
Admin (`/admin/*`, role admin): CRUD `topics|modules|lessons|questions|quizzes|plans`, `PATCH {type}/{id}/status`, `POST uploads`, `users`, `enrollments`, `payments/{id}/verify|reject`, `settings`, `stats`, `audit-logs`.

## Keamanan (ringkas)
Kunci/pembahasan hanya dikirim setelah submit; penilaian dan batas waktu dihitung server; akses premium dicek lewat Policy di tiap endpoint; status pembayaran hanya berubah lewat admin; HTML rich-text disanitasi di server (allowlist) **dan** di klien (DOMPurify); upload hanya gambar raster ≤ 2 MB (tanpa SVG); rate limit login 5/menit; header keamanan dan `Cache-Control: no-store` pada API.

## Deploy
Vercel (frontend) + Render (backend Docker) + Neon (PostgreSQL): lihat [DEPLOY.md](DEPLOY.md).
