<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Harga dan durasi di sini hanya CONTOH; admin mengaturnya lewat panel.
        $plans = [
            ['code' => 'free', 'name' => 'Free / Demo', 'description' => 'Coba materi dan latihan gratis.', 'price' => 0, 'duration_days' => null, 'access_level' => 0, 'sort' => 1,
                'features' => ['Materi demo gratis', 'Beberapa soal latihan', 'Dashboard progres dasar']],
            ['code' => 'full', 'name' => 'Full Course', 'description' => 'Seluruh materi dan bank soal (harga contoh).', 'price' => 499000, 'duration_days' => 365, 'access_level' => 1, 'sort' => 2,
                'features' => ['Semua materi 7 bidang', 'Bank soal lengkap + pembahasan 7 bagian', 'Analisis kelemahan per topik', 'Akses 12 bulan']],
            ['code' => 'premium', 'name' => 'Premium', 'description' => 'Full Course + konten lanjutan (harga contoh).', 'price' => 899000, 'duration_days' => 365, 'access_level' => 2, 'sort' => 3,
                'features' => ['Semua isi Full Course', 'Soal tantangan OSN & kuis bertimer', 'Jalur praktikum lanjutan', 'Akses 12 bulan']],
        ];
        foreach ($plans as $p) {
            Plan::updateOrCreate(['code' => $p['code']], $p + ['is_active' => true]);
        }
    }
}
