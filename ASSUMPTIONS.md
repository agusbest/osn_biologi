# Asumsi dan keputusan

## Lingkungan pembuatan (penting)
1. Kode backend ditulis tanpa bisa menjalankan Laravel (Packagist/GitHub diblokir di lingkungan pembuat). **Belum diuji runtime**; hanya `php lint` dan `HtmlSanitizer` yang dijalankan. Frontend di-build dan dicoba di browser dengan API tiruan.
2. Karena itu `backend/` berisi overlay (kode kita) dan dipasangkan ke kerangka Laravel 12 oleh `setup-backend.*`. Versi Laravel dipatok `^12.0` (Laravel tidak lagi punya rilis "LTS" formal; sesuaikan bila Anda ingin versi lain).

## Teknis
3. **Auth: token Bearer Sanctum**, bukan cookie SPA. Tidak butuh CSRF; token disimpan di `localStorage` (risiko XSS dimitigasi sanitasi HTML dua lapis). Ganti ke mode cookie bila kebijakan keamanan Anda mensyaratkan.
4. Level akses: `plans.access_level` 0 Free, 1 Full, 2 Premium; modul/soal/kuis punya `access_level`; akses siswa = level tertinggi dari enrollment aktif. Admin level 99.
5. "Courses": tabel disiapkan, MVP memakai satu kursus. `lessons.reference_list` dinamai demikian karena `references` kata cadangan SQL.
6. Uraian (essay) **tidak dinilai otomatis**: siswa menilai sendiri setelah melihat pembahasan; skor memasukkan uraian yang sudah dinilai.
7. Skor memakai bobot (`quiz_questions.weight`, bawaan 1) dan hanya soal yang sudah dinilai. Soal tak dijawab = salah. Jawaban yang masuk setelah tenggat + toleransi 30 detik tidak dipakai.
8. Rekomendasi: aturan (batas 60/80, teks tindakan, minimal 3 soal dijawab) disimpan di tabel `settings` dan bisa diubah admin; default ada di `SettingsService::defaults()`.
9. Target belajar harian dihitung dari estimasi menit materi yang diselesaikan hari ini + durasi latihan yang dikirim hari ini (tidak ada pelacak waktu aktif).
10. "Materi tersulit" = modul dengan akurasi jawaban terendah pada soal yang dihubungkan ke modul itu (soal tanpa modul tidak ikut).
11. Pembayaran: hanya manual. Siswa mengajukan (nominal dari server), admin memverifikasi → enrollment dibuat/diperpanjang. Logika verifikasi terpusat di `PaymentController::verify` agar bisa dipanggil webhook gateway nanti.
12. Penerbitan soal mensyaratkan satu kunci benar dan ketujuh bagian pembahasan terisi; materi mensyaratkan target, penjelasan, dan ringkasan.
13. Pesan validasi bawaan Laravel berbahasa Inggris (belum ada `lang/id`); pesan buatan sendiri berbahasa Indonesia.
14. Landing page adalah SPA; untuk SEO yang kuat pertimbangkan prerender/SSR. Meta tag, `robots.txt`, `sitemap.xml` (ganti domain) sudah disiapkan; halaman privat memakai `noindex`.
15. PWA belum diaktifkan (layout sudah mobile-first, siap ditambah service worker/manifest).
16. Target LCP ≤ 2,5 detik belum diukur; route dipecah (lazy) dan font di-bundle lokal, tetapi perlu uji Lighthouse di lingkungan sebenarnya.
17. Halaman legal berisi draf kerangka dan ditandai harus ditinjau konsultan hukum. Harga paket di seeder hanya contoh.

## Konten dan hak cipta
18. Modul "Memahami Enzim dan Cara Kerjanya" dan soal contoh ditulis orisinal dan ditandai contoh; **perlu ditinjau pengajar** sebelum dianggap materi final. Soal Hardy–Weinberg sesuai PRD. Tidak ada teks/gambar/soal yang disalin dari buku atau kompetisi.
19. Jumlah soal, durasi, bobot, dan target harian dapat dikonfigurasi admin; tidak ada format ujian resmi yang dikunci.
20. Fase 2 (tes diagnostik, simulasi OSN, flashcard, lab virtual) tidak dibangun. Kerangka siap: `quizzes` bertimer + autosave, `attempt_answers`, `settings`, `courses`.
