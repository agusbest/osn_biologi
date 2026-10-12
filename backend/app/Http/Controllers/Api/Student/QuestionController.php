<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\AccessService;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    /** Bank soal: daftar ringkas. Tidak pernah memuat kunci/pembahasan. */
    public function index(Request $request, AttemptService $attempts, AccessService $access): JsonResponse
    {
        $user = $request->user();
        $f = $request->validate([
            'topic_id' => ['nullable', 'integer'], 'module_id' => ['nullable', 'integer'],
            'difficulty' => ['nullable', Rule::in(['fondasi', 'pendalaman', 'tantangan'])],
            'type' => ['nullable', Rule::in(['mcq', 'truefalse', 'essay'])],
            'status' => ['nullable', Rule::in(['belum', 'benar', 'salah', 'favorit', 'sulit'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // Soal terkunci tetap ditampilkan sebagai daftar (tanpa isi) agar siswa tahu ada konten di paket lain.
        $level = $access->level($user);
        $q = Question::query();
        if (! $user->isAdmin()) {
            $q->visible();
        }
        foreach (['topic_id', 'module_id', 'difficulty', 'type'] as $col) {
            if (! empty($f[$col])) {
                $q->where("questions.$col", $f[$col]);
            }
        }
        if (! empty($f['status'])) {
            // status memakai logika yang sama dengan pembuat latihan; sisi akses ditangani di bawah.
            $ids = $attempts->filteredQuery($user, $f)->pluck('questions.id');
            $q->whereIn('questions.id', $ids);
        }

        $page = $q->with('topic:id,title')->orderBy('questions.id')->paginate($f['per_page'] ?? 20);
        $ids = $page->getCollection()->pluck('id')->all();
        $last = $attempts->lastResults($user, $ids);
        $marks = Bookmark::where('user_id', $user->id)->whereIn('question_id', $ids)->get()->groupBy('question_id');

        $page->getCollection()->transform(function (Question $x) use ($level, $last, $marks) {
            $locked = $x->access_level > $level;

            return [
                'id' => $x->id,
                'type' => $x->type,
                'difficulty' => $x->difficulty,
                'topic_id' => $x->topic_id,
                'topic' => $x->topic?->title,
                'locked' => $locked,
                'excerpt' => $locked ? null : Str::limit(trim(strip_tags($x->body)), 160),
                'last_result' => array_key_exists($x->id, $last) ? ($last[$x->id] ? 'benar' : 'salah') : 'belum',
                'favorite' => isset($marks[$x->id]) && $marks[$x->id]->contains('kind', 'favorite'),
                'hard' => isset($marks[$x->id]) && $marks[$x->id]->contains('kind', 'hard'),
            ];
        });

        return response()->json($page);
    }

    public function bookmark(Request $request, Question $question): JsonResponse
    {
        $kind = $request->validate(['kind' => ['required', Rule::in(['favorite', 'hard'])]])['kind'];
        $this->authorizeQuestion($request, $question);
        Bookmark::firstOrCreate(['user_id' => $request->user()->id, 'question_id' => $question->id, 'kind' => $kind]);

        return response()->json(['ok' => true], 201);
    }

    public function unbookmark(Request $request, Question $question, string $kind): JsonResponse
    {
        abort_unless(in_array($kind, ['favorite', 'hard'], true), 404);
        Bookmark::where(['user_id' => $request->user()->id, 'question_id' => $question->id, 'kind' => $kind])->delete();

        return response()->json(['ok' => true]);
    }

    public function quizzes(Request $request, AccessService $access): JsonResponse
    {
        $user = $request->user();
        $level = $access->level($user);
        $q = Quiz::query()->withCount('items');
        if (! $user->isAdmin()) {
            $q->published();
        }

        return response()->json(['quizzes' => $q->orderBy('id')->get()->map(fn ($z) => [
            'id' => $z->id, 'title' => $z->title, 'description' => $z->description,
            'duration_minutes' => $z->duration_minutes, 'question_count' => $z->items_count,
            'locked' => $z->access_level > $level,
        ])]);
    }

    private function authorizeQuestion(Request $request, Question $question): void
    {
        if (! $request->user()->isAdmin()) {
            abort_unless($question->isVisible(), 404);
        }
        Gate::authorize('view', $question);
    }
}
