<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\Student;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    // ------------------------------------------------------------ publik
    Route::get('public/landing', [PublicController::class, 'landing']);
    Route::get('public/demo', [PublicController::class, 'demo']);

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    });

    // ------------------------------------------------------------ terautentikasi
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('dashboard', Student\DashboardController::class);
        Route::get('curriculum', Student\CurriculumController::class);

        Route::get('lessons/{lesson}', [Student\LessonController::class, 'show']);
        Route::post('lessons/{lesson}/complete', [Student\LessonController::class, 'complete']);
        Route::delete('lessons/{lesson}/complete', [Student\LessonController::class, 'uncomplete']);

        Route::get('questions', [Student\QuestionController::class, 'index']);
        Route::post('questions/{question}/bookmark', [Student\QuestionController::class, 'bookmark']);
        Route::delete('questions/{question}/bookmark/{kind}', [Student\QuestionController::class, 'unbookmark']);
        Route::get('quizzes', [Student\QuestionController::class, 'quizzes']);

        Route::get('attempts', [Student\AttemptController::class, 'index']);
        Route::post('attempts', [Student\AttemptController::class, 'store']);
        Route::get('attempts/{attempt}', [Student\AttemptController::class, 'show']);
        Route::put('attempts/{attempt}/answers', [Student\AttemptController::class, 'save']);
        Route::post('attempts/{attempt}/submit', [Student\AttemptController::class, 'submit']);
        Route::get('attempts/{attempt}/results', [Student\AttemptController::class, 'results']);
        Route::post('attempts/{attempt}/answers/{question}/self-assess', [Student\AttemptController::class, 'selfAssess']);
        Route::post('attempts/{attempt}/retry', [Student\AttemptController::class, 'retry']);

        Route::get('evaluation', Student\EvaluationController::class);

        Route::get('profile', [Student\ProfileController::class, 'show']);
        Route::put('profile', [Student\ProfileController::class, 'update']);
        Route::put('profile/password', [Student\ProfileController::class, 'password']);

        Route::get('billing', [Student\BillingController::class, 'index']);
        Route::post('payments', [Student\BillingController::class, 'store']);

        // -------------------------------------------------------- admin
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('stats', [Admin\StatsController::class, 'stats']);
            Route::get('audit-logs', [Admin\StatsController::class, 'auditLogs']);

            Route::apiResource('topics', Admin\TopicController::class);
            Route::apiResource('modules', Admin\ModuleController::class);
            Route::apiResource('lessons', Admin\LessonController::class);
            Route::apiResource('questions', Admin\QuestionController::class);
            Route::apiResource('quizzes', Admin\QuizController::class);
            Route::apiResource('plans', Admin\PlanController::class);

            Route::patch('{type}/{id}/status', [Admin\StatusController::class, 'update'])
                ->where('type', 'topics|modules|lessons|questions|quizzes')->whereNumber('id');

            Route::post('uploads', [Admin\UploadController::class, 'store']);
            Route::delete('uploads/{id}', [Admin\UploadController::class, 'destroy'])->whereNumber('id');

            Route::get('users', [Admin\UserController::class, 'index']);
            Route::post('users', [Admin\UserController::class, 'store']);
            Route::get('users/{user}', [Admin\UserController::class, 'show']);
            Route::patch('users/{user}', [Admin\UserController::class, 'update']);

            Route::get('enrollments', [Admin\EnrollmentController::class, 'index']);
            Route::post('enrollments', [Admin\EnrollmentController::class, 'store']);
            Route::patch('enrollments/{enrollment}', [Admin\EnrollmentController::class, 'update']);

            Route::get('payments', [Admin\PaymentController::class, 'index']);
            Route::post('payments/{payment}/verify', [Admin\PaymentController::class, 'verify']);
            Route::post('payments/{payment}/reject', [Admin\PaymentController::class, 'reject']);

            Route::get('settings', [Admin\SettingController::class, 'show']);
            Route::put('settings', [Admin\SettingController::class, 'update']);
        });
    });
});
