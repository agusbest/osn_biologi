<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

/** Siswa hanya boleh mengakses percobaan miliknya sendiri. */
class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }

    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }
}
