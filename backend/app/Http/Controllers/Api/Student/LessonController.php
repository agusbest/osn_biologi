<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson, CurriculumService $curriculum): JsonResponse
    {
        $user = $request->user();
        $lesson->load('module.topic', 'resources');

        // 1) Konten non-published tidak ada bagi siswa (404). 2) Konten berbayar tanpa enrollment → 403.
        if (! $user->isAdmin()) {
            abort_unless($lesson->isVisible(), 404);
        }
        Gate::authorize('view', $lesson);

        $progress = LessonProgress::firstOrNew(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $progress->last_opened_at = now();
        $progress->status = $progress->status ?? 'in_progress';
        $progress->save();

        $flat = $curriculum->flat($curriculum->tree($user));
        $idx = collect($flat)->search(fn ($l) => $l['id'] === $lesson->id);
        $nav = fn ($l) => $l ? ['id' => $l['id'], 'title' => $l['title'], 'locked' => $l['locked']] : null;

        return response()->json([
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'difficulty' => $lesson->difficulty,
                'est_minutes' => $lesson->est_minutes,
                'objectives' => $lesson->objectives,
                'explanation' => $lesson->explanation,
                'key_concepts' => $lesson->key_concepts,
                'osn_thinking' => $lesson->osn_thinking,
                'worked_example' => $lesson->worked_example,
                'practice' => $lesson->practice,
                'summary' => $lesson->summary,
                'glossary' => $lesson->glossary ?? [],
                'reference_list' => $lesson->reference_list ?? [],
                'resources' => $lesson->resources->map(fn ($r) => [
                    'id' => $r->id, 'url' => Storage::disk('public')->url($r->path), 'alt_text' => $r->alt_text, 'caption' => $r->caption,
                ]),
            ],
            'module' => ['id' => $lesson->module->id, 'title' => $lesson->module->title],
            'topic' => ['id' => $lesson->module->topic->id, 'title' => $lesson->module->topic->title, 'number' => $lesson->module->topic->number],
            'progress' => $progress->status,
            'prev' => $idx !== false && $idx > 0 ? $nav($flat[$idx - 1]) : null,
            'next' => $idx !== false && isset($flat[$idx + 1]) ? $nav($flat[$idx + 1]) : null,
        ]);
    }

    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $this->guard($request, $lesson);
        LessonProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['status' => 'completed', 'completed_at' => now(), 'last_opened_at' => now()],
        );

        return response()->json(['progress' => 'completed']);
    }

    public function uncomplete(Request $request, Lesson $lesson): JsonResponse
    {
        $this->guard($request, $lesson);
        LessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)
            ->update(['status' => 'in_progress', 'completed_at' => null]);

        return response()->json(['progress' => 'in_progress']);
    }

    private function guard(Request $request, Lesson $lesson): void
    {
        if (! $request->user()->isAdmin()) {
            abort_unless($lesson->isVisible(), 404);
        }
        Gate::authorize('view', $lesson);
    }
}
