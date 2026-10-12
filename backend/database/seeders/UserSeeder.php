<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Lokal/testing: membuat 3 akun DEMO (kredensial ada di README, jangan dipakai di produksi).
 * Produksi (APP_ENV=production): TIDAK membuat akun demo. Hanya membuat 1 admin dari
 * env ADMIN_EMAIL + ADMIN_PASSWORD (+ ADMIN_NAME); jika tidak diisi, tidak ada akun yang dibuat.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->seedProductionAdmin();

            return;
        }

        $accounts = [
            ['Admin OSN', 'admin@osn.test', 'Admin#Osn2026', 'admin', 'free'],
            ['Siswa Free', 'siswa.free@osn.test', 'Siswa#Free2026', 'student', 'free'],
            ['Siswa Full Course', 'siswa.full@osn.test', 'Siswa#Full2026', 'student', 'full'],
        ];

        foreach ($accounts as [$name, $email, $password, $role, $planCode]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'password' => $password])->forceFill(['role' => $role, 'email_verified_at' => now()])->save();

            $free = Plan::where('code', 'free')->first();
            Enrollment::firstOrCreate(['user_id' => $user->id, 'plan_id' => $free->id], ['status' => 'active', 'starts_at' => now()]);

            if ($planCode === 'full') {
                $full = Plan::where('code', 'full')->first();
                Enrollment::firstOrCreate(
                    ['user_id' => $user->id, 'plan_id' => $full->id],
                    ['status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays($full->duration_days)],
                );
            }
        }
    }

    private function seedProductionAdmin(): void
    {
        $email = config('osn.admin.email');
        $password = config('osn.admin.password');
        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD belum diisi: tidak ada akun admin yang dibuat.');

            return;
        }
        if (strlen((string) $password) < 12) {
            $this->command?->error('ADMIN_PASSWORD minimal 12 karakter. Admin tidak dibuat.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        // Jika admin sudah ada, jangan menimpa kata sandi yang mungkin sudah diganti.
        if (! $user->exists) {
            $user->fill(['name' => config('osn.admin.name'), 'password' => $password]);
        }
        $user->forceFill(['role' => 'admin', 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $free = Plan::where('code', 'free')->first();
        if ($free) {
            Enrollment::firstOrCreate(['user_id' => $user->id, 'plan_id' => $free->id], ['status' => 'active', 'starts_at' => now()]);
        }
    }
}
