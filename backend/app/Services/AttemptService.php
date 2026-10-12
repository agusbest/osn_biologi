<?php

namespace App\Services;

use App\Models\AttemptAnswer;
use App\Models\Bookmark;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seluruh penilaian dilakukan di server. Kunci jawaban & pembahasan tidak pernah
 * dikirim ke browser sebelum attempt berstatus "submitted".
 */
class AttemptService
{
    /** Toleransi jaringan setelah tenggat (detik). */
    public const GRACE_SECONDS = 30;

    public function __construct(private AccessService $access, private SettingsService $settings)
    {
    }

    // ---------------------------------------------------------------- memulai

    public function startFromQuiz(User $user, Quiz $quiz): QuizAttempt
    {
        $items = $quiz->items()->with('question')->get()
            ->filter(fn ($i) => $i->question && ($user->isAdmin() || $i->question->isVisible()));

        abort_if($items->isEmpty(), 422, 'Kuis ini belum memiliki soal yang tersedia.');

        return $this->create(
            $user, 'quiz', $quiz->id,
            $items->map(fn ($i) => [$i->question_id, max(1, (int) $i->weight)])->all(),
            $quiz->duration_minutes,
            ['title' => $quiz->title],
        );
    }

    /** @param int[] $ids */
    public function startFromQuestions(User $user, array $ids, array $meta = []): QuizAttempt
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        abort_if(empty($ids), 422, 'Tidak ada soal yang dipilih.');

        return $this->create($user, 'bank', null, array_map(fn ($id) => [$id, 1], $ids), null, $meta + ['title' => 'Latihan soal']);
    }

    public function startFromFilters(User $user, array $f): QuizAttempt
    {
        $max = (int) $this->settings->get('practice_max_count');
        $count = min($max, max(1, (int) ($f['count'] ?? $this->settings->get('practice_default_count'))));

        $ids = $this->filteredQuery($user, $f)->inRandomOrder()->limit($count)->pluck('questions.id')->all();
        abort_if(empty($ids), 422, 'Tidak ada soal yang cocok dengan filter yang dipilih.');

        return $this->startFromQuestions($user, $ids, ['filters' => Arr::only($f, ['topic_id', 'module_id', 'difficulty', 'type', 'status', 'count']), 'title' => 'Latihan soal']);
    }

    /** Soal yang boleh dilihat pengguna (terbit + sesuai level akses) dengan filter. */
    public function filteredQuery(User $user, array $f)
    {
        $level = $this->access->level($user);
        $q = Question::query()->where('questions.access_level', '<=', $level);
        if (! $user->isAdmin()) {
            $q->visible();
        }

        foreach (['topic_id', 'module_id', 'difficulty', 'type'] as $col) {
            if (! empty($f[$col])) {
                $q->where("questions.$col", $f[$col]);
            }
        }

        $status = $f['status'] ?? null;
        if ($status === 'favorit' || $status === 'sulit') {
            $kind = $status === 'favorit' ? 'favorite' : 'hard';
            $q->whereIn('questions.id', Bookmark::where('user_id', $user->id)->where('kind', $kind)->select('question_id'));
        } elseif ($status === 'belum') {
            $q->whereNotIn('questions.id', $this->answeredIdsQuery($user));
        } elseif ($status === 'benar' || $status === 'salah') {
            $want = $status === 'benar';
            $ids = collect($this->lastResults($user))->filter(fn ($ok) => $ok === $want)->keys();
            $q->whereIn('questions.id', $ids);
        }

        return $q;
    }

    private function answeredIdsQuery(User $user)
    {
        return DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->where('qa.user_id', $user->id)->where('qa.status', 'submitted')
            ->whereNotNull('aa.is_correct')->select('aa.question_id');
    }

    /** Hasil jawaban terakhir per soal: [question_id => bool]. */
    public function lastResults(User $user, ?array $questionIds = null): array
    {
        $rows = DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->where('qa.user_id', $user->id)->where('qa.status', 'submitted')
            ->whereNotNull('aa.is_correct')
            ->when($questionIds !== null, fn ($q) => $q->whereIn('aa.question_id', $questionIds))
            ->orderBy('aa.id')->get(['aa.question_id', 'aa.is_correct']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->question_id] = (bool) $r->is_correct; // yang terakhir menang
        }

        return $map;
    }

    /** @param array<int,array{0:int,1:int}> $pairs [question_id, weight] */
    private function create(User $user, string $mode, ?int $quizId, array $pairs, ?int $minutes, array $meta): QuizAttempt
    {
        return DB::transaction(function () use ($user, $mode, $quizId, $pairs, $minutes, $meta) {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'mode' => $mode,
                'status' => 'in_progress',
                'started_at' => now(),
                'deadline_at' => $minutes ? now()->addMinutes($minutes) : null,
                'total_count' => count($pairs),
                'meta' => $meta,
            ]);
            foreach (array_values($pairs) as $i => [$qid, $w]) {
                AttemptAnswer::create([
                    'attempt_id' => $attempt->id, 'question_id' => $qid, 'weight' => $w, 'sort' => $i,
                ]);
            }

            return $attempt;
        });
    }

    // ----------------------------------------------------------- tampilan aman

    /** Payload soal untuk siswa: TANPA kunci dan TANPA pembahasan. */
    public function safeQuestion(Question $q): array
    {
        return [
            'id' => $q->id,
            'type' => $q->type,
            'difficulty' => $q->difficulty,
            'body' => $q->body,
            'topic_id' => $q->topic_id,
            'topic' => $q->topic?->title,
            'options' => $q->type === 'essay' ? [] : $q->options->map(fn ($o) => [
                'id' => $o->id, 'label' => $o->label, 'text' => $o->text,
            ])->values()->all(),
        ];
    }

    public function inProgressPayload(QuizAttempt $attempt): array
    {
        $attempt->load(['answers.question.options', 'answers.question.topic']);

        return [
            'id' => $attempt->id,
            'mode' => $attempt->mode,
            'status' => $attempt->status,
            'title' => $attempt->meta['title'] ?? 'Latihan',
            'started_at' => $attempt->started_at,
            'deadline_at' => $attempt->deadline_at,
            'server_time' => now(),
            'autosave_seconds' => (int) $this->settings->get('autosave_seconds'),
            'items' => $attempt->answers->map(fn ($a) => [
                'question' => $this->safeQuestion($a->question),
                'answer' => ['option_id' => $a->option_id, 'answer_text' => $a->answer_text],
            ])->values()->all(),
        ];
    }

    // ----------------------------------------------------------- menyimpan

    /** Autosave. Setelah tenggat+toleransi, perubahan ditolak. */
    public function saveAnswers(QuizAttempt $attempt, array $answers): void
    {
        abort_if($attempt->isSubmitted(), 409, 'Latihan ini sudah dikirim.');
        abort_if($this->isPastDeadline($attempt), 422, 'Waktu pengerjaan sudah habis.');

        $rows = $attempt->answers()->with('question.options')->get()->keyBy('question_id');

        foreach ($answers as $a) {
            $row = $rows[(int) ($a['question_id'] ?? 0)] ?? null;
            if (! $row) {
                continue; // soal bukan bagian dari attempt ini: abaikan
            }
            $q = $row->question;
            if ($q->type === 'essay') {
                $row->answer_text = isset($a['answer_text']) ? Str::limit((string) $a['answer_text'], 5000, '') : null;
                $row->option_id = null;
            } else {
                $optionId = $a['option_id'] ?? null;
                if ($optionId !== null && ! $q->options->contains('id', (int) $optionId)) {
                    continue; // opsi bukan milik soal ini
                }
                $row->option_id = $optionId;
                $row->answer_text = null;
            }
            $row->answered_at = now();
            $row->save();
        }
    }

    private function isPastDeadline(QuizAttempt $attempt): bool
    {
        return $attempt->deadline_at && now()->greaterThan($attempt->deadline_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    // ----------------------------------------------------------- menilai

    public function submit(QuizAttempt $attempt, ?array $answers = null): QuizAttempt
    {
        abort_if($attempt->isSubmitted(), 409, 'Latihan ini sudah dikirim.');

        $late = $this->isPastDeadline($attempt);

        return DB::transaction(function () use ($attempt, $answers, $late) {
            // Jawaban yang datang setelah tenggat tidak dipakai; yang tersimpan sebelumnya tetap dinilai.
            if ($answers && ! $late) {
                $this->saveAnswers($attempt, $answers);
            }

            $rows = $attempt->answers()->with('question.options')->get();
            foreach ($rows as $row) {
                if ($row->question->type === 'essay') {
                    $row->is_correct = null; // uraian dinilai mandiri setelah melihat pembahasan
                } else {
                    $opt = $row->question->options->firstWhere('id', $row->option_id);
                    $row->is_correct = (bool) ($opt?->is_correct);
                }
                $row->save();
            }

            $now = now();
            $attempt->forceFill([
                'status' => 'submitted',
                'submitted_at' => $now,
                'timed_out' => $late || ($attempt->deadline_at && $now->greaterThan($attempt->deadline_at)),
                'duration_seconds' => (int) max(0, floor($attempt->started_at->diffInSeconds($now))),
            ])->save();

            return $this->recompute($attempt);
        });
    }

    public function recompute(QuizAttempt $attempt): QuizAttempt
    {
        $graded = $attempt->answers()->whereNotNull('is_correct')->get();
        $totalW = $graded->sum('weight');
        $okW = $graded->where('is_correct', true)->sum('weight');

        $attempt->forceFill([
            'graded_count' => $graded->count(),
            'correct_count' => $graded->where('is_correct', true)->count(),
            'score' => $totalW > 0 ? round($okW / $totalW * 100, 2) : null,
        ])->save();

        return $attempt;
    }

    public function selfAssess(QuizAttempt $attempt, int $questionId, bool $correct): void
    {
        abort_unless($attempt->isSubmitted(), 409, 'Selesaikan latihan dulu.');
        $row = $attempt->answers()->with('question')->where('question_id', $questionId)->firstOrFail();
        abort_unless($row->question->type === 'essay', 422, 'Penilaian mandiri hanya untuk soal uraian.');

        $row->forceFill(['is_correct' => $correct, 'self_assessed' => true])->save();
        $this->recompute($attempt);
    }

    // ----------------------------------------------------------- hasil

    /** Hanya boleh dipanggil untuk attempt yang sudah submitted. */
    public function resultsPayload(QuizAttempt $attempt): array
    {
        abort_unless($attempt->isSubmitted(), 409, 'Hasil tersedia setelah latihan dikirim.');
        $attempt->load(['answers.question.options', 'answers.question.tags', 'answers.question.topic']);

        $items = $attempt->answers->map(function ($a) {
            $q = $a->question;
            $explanation = [];
            foreach (Question::EXPLANATION_KEYS as $key => $label) {
                $explanation[] = ['key' => $key, 'label' => $label, 'html' => $q->explanation[$key] ?? ''];
            }

            return [
                'question' => $this->safeQuestion($q) + ['tags' => $q->tags->pluck('tag')->reject(fn ($t) => str_starts_with($t, 'seed:'))->values()->all()],
                'chosen_option_id' => $a->option_id,
                'answer_text' => $a->answer_text,
                'is_correct' => $a->is_correct,
                'self_assessed' => $a->self_assessed,
                'correct_option_ids' => $q->options->where('is_correct', true)->pluck('id')->values()->all(),
                'explanation' => $explanation,
            ];
        })->values();

        $byTopic = $attempt->answers->whereNotNull('is_correct')->groupBy(fn ($a) => $a->question->topic_id)
            ->map(fn ($g) => [
                'topic_id' => $g->first()->question->topic_id,
                'title' => $g->first()->question->topic?->title,
                'answered' => $g->count(),
                'correct' => $g->where('is_correct', true)->count(),
                'accuracy' => round($g->where('is_correct', true)->count() / $g->count() * 100, 1),
            ])->values();

        return [
            'attempt' => [
                'id' => $attempt->id,
                'mode' => $attempt->mode,
                'title' => $attempt->meta['title'] ?? 'Latihan',
                'score' => $attempt->score,
                'correct_count' => $attempt->correct_count,
                'graded_count' => $attempt->graded_count,
                'total_count' => $attempt->total_count,
                'ungraded_count' => $attempt->total_count - $attempt->graded_count,
                'duration_seconds' => $attempt->duration_seconds,
                'timed_out' => $attempt->timed_out,
                'submitted_at' => $attempt->submitted_at,
            ],
            'items' => $items,
            'topic_breakdown' => $byTopic,
        ];
    }

    /** Percobaan ulang dengan soal yang sama (atau hanya yang salah). */
    public function retry(User $user, QuizAttempt $attempt, bool $onlyWrong): QuizAttempt
    {
        abort_unless($attempt->isSubmitted(), 409, 'Selesaikan latihan dulu.');
        $rows = $attempt->answers()->with('question')->get();
        if ($onlyWrong) {
            $rows = $rows->filter(fn ($a) => $a->is_correct === false);
        }
        $rows = $rows->filter(fn ($a) => $a->question->isVisible() && $this->access->can($user, $a->question->access_level));
        abort_if($rows->isEmpty(), 422, 'Tidak ada soal yang bisa diulang.');

        return $this->create(
            $user, $attempt->mode, $attempt->quiz_id,
            $rows->map(fn ($a) => [$a->question_id, $a->weight])->values()->all(),
            $attempt->quiz_id ? $attempt->quiz?->duration_minutes : null,
            ['title' => ($attempt->meta['title'] ?? 'Latihan') . ' (ulang)', 'retry_of' => $attempt->id],
        );
    }

    /** Riwayat jawaban salah (hanya soal; pembahasan dibuka lewat halaman hasil attempt). */
    public function wrongHistory(User $user, int $limit = 50): Collection
    {
        return AttemptAnswer::query()
            ->join('quiz_attempts as qa', 'qa.id', '=', 'attempt_answers.attempt_id')
            ->join('questions as q', 'q.id', '=', 'attempt_answers.question_id')
            ->join('topics as t', 't.id', '=', 'q.topic_id')
            ->where('qa.user_id', $user->id)->where('qa.status', 'submitted')
            ->where('attempt_answers.is_correct', false)
            ->orderByDesc('attempt_answers.id')->limit($limit)
            ->get(['attempt_answers.id', 'attempt_answers.attempt_id', 'attempt_answers.question_id', 'q.body', 'q.difficulty', 't.title as topic', 'qa.submitted_at'])
            ->map(fn ($r) => [
                'attempt_id' => $r->attempt_id,
                'question_id' => $r->question_id,
                'excerpt' => Str::limit(trim(strip_tags($r->body)), 140),
                'difficulty' => $r->difficulty,
                'topic' => $r->topic,
                'submitted_at' => $r->submitted_at,
            ]);
    }
}
