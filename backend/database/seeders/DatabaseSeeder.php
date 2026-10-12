<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            PlanSeeder::class,
            UserSeeder::class,
            CurriculumSeeder::class,
            SettingSeeder::class,
        ];
        // Konten contoh ("[Contoh]") ikut di-seed kecuali SEED_SAMPLE_CONTENT=false.
        if (config('osn.seed_sample_content')) {
            $seeders[] = SampleContentSeeder::class;
        }
        $this->call($seeders);
    }
}
