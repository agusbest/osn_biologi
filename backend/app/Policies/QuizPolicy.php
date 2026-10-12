<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use App\Services\AccessService;

class QuizPolicy
{
    public function __construct(private AccessService $access)
    {
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->access->can($user, (int) $quiz->access_level);
    }
}
