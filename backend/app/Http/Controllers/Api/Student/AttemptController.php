<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAnswersRequest;
use App\Http\Requests\Student\StartAttemptRequest;
use App\Http\Requests\Student\SubmitAttemptRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttemptController extends Controller
{
    public function __construct(private AttemptService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = QuizAttempt::where('user_id', $request->user()->id)->orderByDesc('id')->limit(50)->get()
            ->map(fn ($a) => [
                'id' => $a->id, 'mode' => $a->mode, 'status' => $a->status,
                'title' => $a->meta['title'] ?? 'Latihan',
                'score' => $a->score, 'total_count' => $a->total_count,
                'started_at' => $a->started_at, 'submitted_at' => $a->submitted_at,
            ]);

        return response()->json(['attempts' => $rows]);
    }

    public function store(StartAttemptRequest $request): JsonResponse
    {
        $user = $request->user();
        $d = $request->validated();

        if (! empty($d['quiz_id'])) {
            $quiz = Quiz::findOrFail($d['quiz_id']);
            if (! $user->isAdmin()) {
                abort_unless($quiz->status === 'published', 404);
            }
            Gate::authorize('view', $quiz); // kuis berbayar → 403 tanpa enrollment
            $attempt = $this->service->startFromQuiz($user, $quiz);
        } elseif (! empty($d['question_ids'])) {
            $questions = Question::with('topic', 'module')->findMany($d['question_ids']);
            foreach ($questions as $q) {
                if (! $user->isAdmin()) {
                    abort_unless($q->isVisible(), 404);
                }
                Gate::authorize('view', $q);
            }
            $attempt = $this->service->startFromQuestions($user, $questions->pluck('id')->all());
        } else {
            $attempt = $this->service->startFromFilters($user, $d);
        }

        return response()->json(['id' => $attempt->id], 201);
    }

    public function show(Request $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('view', $attempt);
        if ($attempt->isSubmitted()) {
            return response()->json(['id' => $attempt->id, 'status' => 'submitted']);
        }

        return response()->json($this->service->inProgressPayload($attempt));
    }

    public function save(SaveAnswersRequest $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('update', $attempt);
        $this->service->saveAnswers($attempt, $request->validated()['answers']);

        return response()->json(['saved_at' => now()]);
    }

    public function submit(SubmitAttemptRequest $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('update', $attempt);
        $attempt = $this->service->submit($attempt, $request->validated()['answers'] ?? null);

        return response()->json($this->service->resultsPayload($attempt));
    }

    public function results(Request $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('view', $attempt);

        // resultsPayload menolak (409) bila belum submitted → kunci tidak bocor.
        return response()->json($this->service->resultsPayload($attempt));
    }

    public function selfAssess(Request $request, QuizAttempt $attempt, int $question): JsonResponse
    {
        Gate::authorize('update', $attempt);
        $correct = $request->validate(['correct' => ['required', 'boolean']])['correct'];
        $this->service->selfAssess($attempt, $question, (bool) $correct);

        return response()->json($this->service->resultsPayload($attempt->refresh())['attempt']);
    }

    public function retry(Request $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('view', $attempt);
        $request->validate(['only_wrong' => ['nullable', 'boolean']]);
        $new = $this->service->retry($request->user(), $attempt, $request->boolean('only_wrong'));

        return response()->json(['id' => $new->id], 201);
    }
}
