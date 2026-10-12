<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Policies\LessonPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Services\AccessService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessService::class);
    }

    public function boot(): void
    {
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);

        // Rate limiting login: 5 percobaan/menit per kombinasi email + IP.
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)
            ->by(strtolower((string) $r->input('email')) . '|' . $r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));

        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('frontend.url'), '/')
            . '/reset-password?token=' . $token . '&email=' . urlencode($user->getEmailForPasswordReset()));
    }
}
