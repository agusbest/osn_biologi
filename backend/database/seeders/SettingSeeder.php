<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $s = app(SettingsService::class);
        foreach (SettingsService::defaults() as $k => $v) {
            if ($v !== null) {
                $s->set($k, $v);
            }
        }
    }
}
