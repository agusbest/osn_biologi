<?php

namespace Tests\Concerns;

use App\Models\Enrollment;
use App\Models\Plan;
use App\Models\Question;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait BuildsContent
{
    protected function plan(string $code): Plan
    {
        return match ($code) {
            'free' => Plan::firstOrCreate(['code' => 'free'], Plan::factory()->free()->make()->toArray()),
            default => Plan::firstOrCreate(['code' => $code], Plan::factory()->full()->make(['code' => $code])->toArray()),
        };
    }

    protected function student(?string $planCode = null): User
    {
        $u = User::factory()->create();
        Enrollment::create(['user_id' => $u->id, 'plan_id' => $this->plan('free')->id, 'status' => 'active', 'starts_at' => now()]);
        if ($planCode && $planCode !== 'free') {
            $this->enroll($u, $planCode);
        }

        return $u;
    }

    protected function enroll(User $u, string $planCode = 'full', $ends = null): Enrollment
    {
        return Enrollment::create([
            'user_id' => $u->id, 'plan_id' => $this->plan($planCode)->id, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => $ends ?? now()->addDays(30),
        ]);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function as(User $u): static
    {
        Sanctum::actingAs($u, ['*']);

        return $this;
    }

    /** Soal pilihan ganda: opsi A=1, B=2 (benar), C=3. */
    protected function mcq(array $attrs = []): Question
    {
        return Question::factory()->withOptions()->create($attrs);
    }

    protected function correctOptionId(Question $q): int
    {
        return $q->options()->where('is_correct', true)->value('id');
    }

    protected function wrongOptionId(Question $q): int
    {
        return $q->options()->where('is_correct', false)->value('id');
    }
}
