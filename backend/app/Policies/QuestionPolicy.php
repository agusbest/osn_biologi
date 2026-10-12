<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use App\Services\AccessService;

class QuestionPolicy
{
    public function __construct(private AccessService $access)
    {
    }

    public function view(User $user, Question $question): bool
    {
        return $this->access->can($user, (int) $question->access_level);
    }
}
