<?php

namespace App\Services;

use App\Models\Setting;

/** Pengaturan yang bisa diubah admin (aturan rekomendasi, jumlah soal, dsb.), bukan hardcode. */
class SettingsService
{
    public static function defaults(): array
    {
        return [
            'site_name' => 'OSN Biology Academy',
            'practice_default_count' => 10,
            'practice_max_count' => 30,
            'daily_target_min' => 30,
            'daily_target_max' => 60,
            'autosave_seconds' => 15,
            'demo_lesson_id' => null,
            'payment_instructions' => 'CONTOH: Transfer sesuai nominal paket, lalu kirim referensi pembayaran di form. Admin akan memverifikasi secara manual. (Ubah teks ini di Pengaturan admin.)',
            'recommendation_rules' => [
                'min_answers' => 3,
                'tiers' => [
                    ['below' => 60, 'key' => 'dasar', 'label' => 'Perkuat fondasi', 'actions' => ['Ulangi teori inti pada topik ini', 'Kerjakan latihan dasar (tingkat fondasi)']],
                    ['below' => 80, 'key' => 'aplikasi', 'label' => 'Latih penerapan', 'actions' => ['Kerjakan latihan aplikasi', 'Latih analisis data dan grafik']],
                    ['below' => 101, 'key' => 'lanjutan', 'label' => 'Naik tingkat', 'actions' => ['Kerjakan soal lanjutan (tantangan OSN)', 'Coba latihan bertimer']],
                ],
            ],
        ];
    }

    public function all(): array
    {
        $stored = Setting::all()->mapWithKeys(fn ($s) => [$s->name => $s->value])->all();

        return array_replace(self::defaults(), $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['name' => $key], ['value' => $value]);
    }

    /** Pilih tier rekomendasi berdasarkan akurasi (%). */
    public function tierFor(float $accuracy): array
    {
        $tiers = $this->get('recommendation_rules')['tiers'];
        usort($tiers, fn ($a, $b) => $a['below'] <=> $b['below']);
        foreach ($tiers as $tier) {
            if ($accuracy < $tier['below']) {
                return $tier;
            }
        }

        return end($tiers);
    }
}
