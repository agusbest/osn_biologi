# Deploy: Vercel (frontend) + Render (backend Docker) + Neon (database)

Urutan: **Neon → GitHub → Render → Vercel → sambungkan CORS**.

> **Penting: Neon adalah PostgreSQL, bukan MySQL.** Kode sudah disesuaikan agar berjalan di PostgreSQL
> (dan tetap di MySQL untuk lokal). Jika Anda benar-benar harus MySQL, pakai penyedia MySQL lain
> (mis. Aiven atau TiDB Cloud) dan set `DB_CONNECTION=mysql`.

## 0. Prasyarat
- Akun GitHub, Neon, Render, Vercel.
- Proyek ini sudah jalan di lokal (`php artisan test` lolos).

## 1. Neon (database)
1. Buat project di https://console.neon.tech (pilih region terdekat, mis. Singapore).
2. Dari **Connection Details**, catat: **host**, **database**, **user**, **password**.
3. Untuk `DB_HOST` pakai host **langsung** (tanpa `-pooler`). Migrasi lebih aman lewat koneksi langsung.
4. Neon otomatis tidur saat idle; permintaan pertama setelah tidur bisa terasa lambat. Itu normal.

## 2. Siapkan `APP_KEY`
Di komputer Anda:
```powershell
cd "D:\Remote Worker\project\osn-26\backend"
php artisan key:generate --show
```
Salin hasilnya (diawali `base64:`). Simpan; ini untuk Render.

## 3. Push ke GitHub
Dari folder root proyek (`osn-26`):
```powershell
git init
git add .
git status        # pastikan TIDAK ada backend/.env dan backend/vendor
git commit -m "OSN Biology Academy MVP"
git branch -M main
git remote add origin https://github.com/<akun-anda>/<repo>.git
git push -u origin main
```
Gunakan repo **private**. Pastikan `backend/composer.lock` ikut ter-commit (Docker membutuhkannya).

## 4. Render (backend)
1. Dashboard Render → **New → Blueprint** → pilih repo. Render membaca `render.yaml`.
2. Isi variabel yang bertanda "isi manual":

| Variabel | Isi |
|---|---|
| `APP_KEY` | hasil langkah 2 |
| `APP_URL` | URL layanan Render, mis. `https://osn-api.onrender.com` |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | dari Neon |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | akun admin Anda (kata sandi **minimal 12 karakter**) |
| `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS` | isi sementara `https://example.com`; diganti di langkah 6 |

3. Deploy pertama: set **`RUN_SEED=true`** supaya paket, kurikulum, pengaturan, dan akun admin dibuat.
   Setelah berhasil, **kembalikan ke `false`** dan simpan.
   - Di produksi, akun demo (`siswa.free@osn.test`, dst.) **tidak dibuat**. Hanya admin dari env.
   - Konten contoh "[Contoh]" ikut dibuat. Untuk menghilangkannya set `SEED_SAMPLE_CONTENT=false` sebelum seed.
4. Cek: buka `https://<layanan>.onrender.com/up`. Harus mengembalikan status 200.
   Lalu `https://<layanan>.onrender.com/api/public/landing` harus mengembalikan JSON.

## 5. Vercel (frontend)
1. **Add New → Project** → pilih repo yang sama.
2. **Root Directory: `frontend`**. Framework terdeteksi Vite (sudah diatur di `frontend/vercel.json`).
3. **Environment Variables**: `VITE_API_URL` = `https://<layanan>.onrender.com/api` (tanpa slash di akhir).
   Variabel `VITE_*` dibaca saat *build*, jadi setelah mengubahnya Anda harus **Redeploy**.
4. Deploy. Catat URL-nya, mis. `https://osn-academy.vercel.app`.

## 6. Sambungkan CORS
Di Render, ubah lalu simpan (layanan akan restart):
- `CORS_ALLOWED_ORIGINS` = URL Vercel persis (mis. `https://osn-academy.vercel.app`), tanpa slash di akhir.
  Beberapa origin dipisah koma.
- `FRONTEND_URL` = URL Vercel yang sama.

Setelah itu buka URL Vercel dan login dengan `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## 7. Domain sendiri (opsional)
Tambahkan domain di Vercel, lalu perbarui `CORS_ALLOWED_ORIGINS` / `FRONTEND_URL` di Render, dan ganti
`GANTI-DOMAIN-ANDA` di `frontend/public/robots.txt` dan `sitemap.xml`.

## Batasan yang perlu Anda tahu
- **Render gratis tidur** setelah ±15 menit tidak ada trafik; permintaan pertama bisa memakan 30–60 detik.
- **File unggahan (gambar materi) hilang saat redeploy** karena disk gratis Render tidak permanen.
  Untuk produksi sungguhan, pakai Render Persistent Disk (berbayar) atau penyimpanan objek (S3/R2).
- **Email lupa kata sandi tidak terkirim** (`MAIL_MAILER=log`). Atur SMTP (mis. Resend/Brevo) di env
  (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`).
- **Backup:** `php artisan app:db-backup` menyimpan ke disk container (tidak permanen). Andalkan
  fitur backup/restore bawaan Neon, dan jadwalkan dump terpisah bila perlu. Penjadwal Laravel tidak berjalan di container ini.
- **Rate limiting** memakai cache file; cukup untuk satu instance. Jika menambah instance, pindah ke Redis/database.
- Build image Docker **belum diuji** oleh saya (tidak ada Docker daemon di lingkungan kerja saya). Jika build di Render gagal, kirim log-nya.

## Pemecahan masalah
| Gejala | Penyebab umum |
|---|---|
| Deploy gagal: `APP_KEY belum diisi` | isi `APP_KEY` di Render |
| `SQLSTATE ... could not translate host name` | `DB_HOST` salah |
| `SSL connection is required` | pastikan `DB_SSLMODE=require` |
| Login di Vercel: "Tidak dapat terhubung ke server" | `VITE_API_URL` salah/belum redeploy, atau backend sedang bangun dari tidur |
| Konsol browser: error CORS | `CORS_ALLOWED_ORIGINS` tidak sama persis dengan URL Vercel |
| Halaman 404 saat refresh di Vercel | pastikan `frontend/vercel.json` ikut ter-deploy dan Root Directory = `frontend` |
