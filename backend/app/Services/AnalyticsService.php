<?php

namespace App\Services;

use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(
        private SettingsService $settings,
        private CurriculumService $curriculum,
    ) {
    }

    /** Skor per topik (hanya jawaban yang sudah dinilai pada percobaan yang sudah dikirim). */
    public function topicScores(User $user): array
    {
        return DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aa.question_id')
            ->join('topics as t', 't.id', '=', 'q.topic_id')
            ->where('qa.user_id', $user->id)
            ->where('qa.status', 'submitted')
            ->whereNotNull('aa.is_correct')
            ->groupBy('t.id', 't.title', 't.number')
            ->orderBy('t.number')
            ->selectRaw('t.id as topic_id, t.title, t.number, COUNT(*) as answered, SUM(CASE WHEN aa.is_correct = true THEN 1 ELSE 0 END) as correct')
            ->get()
            ->map(fn ($r) => [
                'topic_id' => (int) $r->topic_id,
                'title' => $r->title,
                'number' => $r->number,
                'answered' => (int) $r->answered,
                'correct' => (int) $r->correct,
                'accuracy' => round($r->correct / max(1, $r->answered) * 100, 1),
            ])->all();
    }

    public function overallAccuracy(User $user): ?float
    {
        $r = DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->where('qa.user_id', $user->id)->where('qa.status', 'submitted')
            ->whereNotNull('aa.is_correct')
            ->selectRaw('COUNT(*) as n, SUM(CASE WHEN aa.is_correct = true THEN 1 ELSE 0 END) as c')->first();

        return $r && $r->n > 0 ? round($r->c / $r->n * 100, 1) : null;
    }

    /** Rekomendasi per topik berdasarkan aturan di pengaturan (bukan hardcode). */
    public function recommendations(User $user, ?array $tree = null): array
    {
        $rules = $this->settings->get('recommendation_rules');
        $min = (int) ($rules['min_answers'] ?? 3);
        $tree ??= $this->curriculum->tree($user);
        $byTopic = collect($tree)->keyBy('id');

        $out = [];
        foreach ($this->topicScores($user) as $row) {
            if ($row['answered'] < $min) {
                continue;
            }
            $tier = $this->settings->tierFor($row['accuracy']);
            $modules = collect($byTopic[$row['topic_id']]['modules'] ?? [])
                ->where('locked', false)->where('status', '!=', 'selesai')
                ->take(3)->map(fn ($m) => ['id' => $m['id'], 'title' => $m['title'], 'first_lesson_id' => $m['lessons'][0]['id'] ?? null])
                ->values()->all();

            $out[] = $row + [
                'tier' => $tier['key'],
                'label' => $tier['label'],
                'actions' => $tier['actions'],
                'modules' => $modules,
            ];
        }
        usort($out, fn ($a, $b) => $a['accuracy'] <=> $b['accuracy']);

        return $out;
    }

    public function studiedMinutesToday(User $user): int
    {
        $lessons = LessonProgress::where('lesson_progress.user_id', $user->id)
            ->where('lesson_progress.status', 'completed')
            ->whereDate('lesson_progress.completed_at', now()->toDateString())
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')->sum('lessons.est_minutes');
        $attempts = DB::table('quiz_attempts')->where('user_id', $user->id)
            ->whereDate('submitted_at', now()->toDateString())->sum('duration_seconds');

        return (int) round($lessons + $attempts / 60);
    }

    public function dashboard(User $user): array
    {
        $tree = $this->curriculum->tree($user);
        $flat = $this->curriculum->flat($tree);
        $open = collect($flat)->where('locked', false);

        $modules = collect($tree)->flatMap(fn ($t) => $t['modules'])->where('locked', false)->where('lesson_count', '>', 0);
        $modulesDone = $modules->where('status', 'selesai')->count();
        $done = $open->where('status', 'selesai')->count();

        $continue = null;
        $lastOpened = LessonProgress::where('user_id', $user->id)->where('status', 'in_progress')
            ->orderByDesc('last_opened_at')->pluck('lesson_id');
        foreach ($lastOpened as $id) {
            if ($l = $open->firstWhere('id', $id)) {
                $continue = $l;
                break;
            }
        }
        $continue ??= $open->firstWhere('status', '!=', 'selesai');

        $scores = $this->topicScores($user);
        $min = (int) ($this->settings->get('recommendation_rules')['min_answers'] ?? 3);
        $threshold = (float) collect($this->settings->get('recommendation_rules')['tiers'])->min('below');

        return [
            'progress_percent' => $open->count() ? round($done / $open->count() * 100, 1) : 0,
            'lessons_done' => $done,
            'lessons_total' => $open->count(),
            'modules_done' => $modulesDone,
            'modules_total' => $modules->count(),
            'accuracy' => $this->overallAccuracy($user),
            'continue' => $continue ? [
                'lesson_id' => $continue['id'],
                'title' => $continue['title'],
                'status' => $continue['status'],
            ] : null,
            'topic_scores' => $scores,
            'weak_topics' => collect($scores)->filter(fn ($s) => $s['answered'] >= $min && $s['accuracy'] < $threshold)->values()->all(),
            'daily' => [
                'target_minutes' => $user->daily_target_minutes,
                'studied_minutes' => $this->studiedMinutesToday($user),
            ],
        ];
    }

    public function adminStats(): array
    {
        $min = (int) ($this->settings->get('recommendation_rules')['min_answers'] ?? 3);

        $hardQuestions = DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aa.question_id')
            ->where('qa.status', 'submitted')->whereNotNull('aa.is_correct')
            ->groupBy('q.id', 'q.body')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->selectRaw('q.id, q.body, COUNT(*) as answered, SUM(CASE WHEN aa.is_correct = false THEN 1 ELSE 0 END) as wrong')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'excerpt' => mb_substr(trim(strip_tags($r->body)), 0, 120),
                'answered' => (int) $r->answered,
                'wrong' => (int) $r->wrong,
                'wrong_rate' => round($r->wrong / $r->answered * 100, 1),
            ])->sortByDesc('wrong_rate')->take(10)->values()->all();

        // "Materi tersulit" = modul dengan akurasi jawaban terendah pada soal yang terkait.
        $hardModules = DB::table('attempt_answers as aa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'aa.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aa.question_id')
            ->join('modules as m', 'm.id', '=', 'q.module_id')
            ->where('qa.status', 'submitted')->whereNotNull('aa.is_correct')
            ->groupBy('m.id', 'm.title')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->selectRaw('m.id, m.title, COUNT(*) as answered, SUM(CASE WHEN aa.is_correct = true THEN 1 ELSE 0 END) as correct')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => $r->title,
                'answered' => (int) $r->answered,
                'accuracy' => round($r->correct / $r->answered * 100, 1),
            ])->sortBy('accuracy')->take(10)->values()->all();

        return [
            'counts' => [
                'students' => User::where('role', 'student')->count(),
                'active_enrollments' => \App\Models\Enrollment::active()->count(),
                'pending_payments' => \App\Models\Payment::where('status', 'pending')->count(),
                'published_lessons' => \App\Models\Lesson::where('status', 'published')->count(),
                'published_questions' => \App\Models\Question::where('status', 'published')->count(),
                'in_review' => \App\Models\Lesson::where('status', 'review')->count() + \App\Models\Question::where('status', 'review')->count(),
            ],
            'hardest_questions' => $hardQuestions,
            'hardest_modules' => $hardModules,
        ];
    }
}
