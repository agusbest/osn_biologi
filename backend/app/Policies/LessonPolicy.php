<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AccessService;

class LessonPolicy
{
    public function __construct(private AccessService $access)
    {
    }

    /** Konten berbayar: wajib enrollment aktif dengan level cukup. */
    public function view(User $user, Lesson $lesson): bool
    {
        $lesson->loadMissing('module');

        return $this->access->can($user, (int) $lesson->module->access_level);
    }
}
