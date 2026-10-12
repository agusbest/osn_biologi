<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\AnalyticsService;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics, AttemptService $attempts): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'accuracy' => $analytics->overallAccuracy($user),
            'topic_scores' => $analytics->topicScores($user),
            'recommendations' => $analytics->recommendations($user),
            'wrong_history' => $attempts->wrongHistory($user),
            'recent_attempts' => QuizAttempt::where('user_id', $user->id)->where('status', 'submitted')
                ->orderByDesc('submitted_at')->limit(10)->get()->map(fn ($a) => [
                    'id' => $a->id, 'title' => $a->meta['title'] ?? 'Latihan', 'score' => $a->score,
                    'total_count' => $a->total_count, 'submitted_at' => $a->submitted_at,
                ]),
        ]);
    }
}
