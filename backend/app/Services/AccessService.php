<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Level akses = level tertinggi dari paket pada enrollment aktif.
 * 0 = Free/Demo, 1 = Full Course, 2 = Premium. Admin = 99.
 * Dipakai oleh Policy sehingga konten berbayar dijaga di backend, bukan hanya disembunyikan di menu.
 */
class AccessService
{
    public function level(?User $user): int
    {
        if (! $user) {
            return 0;
        }
        if ($user->isAdmin()) {
            return 99;
        }

        $max = Enrollment::query()->active()
            ->where('enrollments.user_id', $user->id)
            ->join('plans', 'plans.id', '=', 'enrollments.plan_id')
            ->max('plans.access_level');

        return (int) ($max ?? 0);
    }

    public function can(?User $user, int $requiredLevel): bool
    {
        return $this->level($user) >= $requiredLevel;
    }
}
