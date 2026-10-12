<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Topic;
use App\Models\User;

class CurriculumService
{
    public function __construct(private AccessService $access)
    {
    }

    /**
     * Pohon kurikulum untuk siswa: hanya konten terbit. Status per modul/lesson:
     * belum | sedang | selesai | terkunci.
     */
    public function tree(User $user): array
    {
        $level = $this->access->level($user);
        $progress = LessonProgress::where('user_id', $user->id)->pluck('status', 'lesson_id');

        $topics = Topic::published()->orderBy('sort')->with([
            'modules' => fn ($q) => $q->published()->orderBy('sort')->with([
                'lessons' => fn ($l) => $l->published()->orderBy('sort')
                    ->select(['id', 'module_id', 'title', 'slug', 'difficulty', 'est_minutes', 'sort']),
            ]),
        ])->get();

        return $topics->map(function ($topic) use ($level, $progress) {
            $modules = $topic->modules->map(function ($m) use ($level, $progress) {
                $locked = $m->access_level > $level;
                $lessons = $m->lessons->map(function ($l) use ($locked, $progress) {
                    $p = $progress[$l->id] ?? null;

                    return [
                        'id' => $l->id,
                        'title' => $l->title,
                        'difficulty' => $l->difficulty,
                        'est_minutes' => $l->est_minutes,
                        'locked' => $locked,
                        'status' => $locked ? 'terkunci' : match ($p) {
                            'completed' => 'selesai',
                            'in_progress' => 'sedang',
                            default => 'belum',
                        },
                    ];
                })->values();

                $done = $lessons->where('status', 'selesai')->count();
                $started = $lessons->whereIn('status', ['selesai', 'sedang'])->count();

                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'summary' => $m->summary,
                    'access_level' => $m->access_level,
                    'locked' => $locked,
                    'lesson_count' => $lessons->count(),
                    'completed_count' => $done,
                    'status' => $locked ? 'terkunci'
                        : ($lessons->count() > 0 && $done === $lessons->count() ? 'selesai' : ($started > 0 ? 'sedang' : 'belum')),
                    'lessons' => $lessons,
                ];
            })->values();

            return [
                'id' => $topic->id,
                'number' => $topic->number,
                'title' => $topic->title,
                'description' => $topic->description,
                'type' => $topic->type,
                'modules' => $modules,
            ];
        })->all();
    }

    /** Daftar lesson datar berurutan (topik → modul → lesson) dari pohon. */
    public function flat(array $tree): array
    {
        $out = [];
        foreach ($tree as $topic) {
            foreach ($topic['modules'] as $module) {
                foreach ($module['lessons'] as $lesson) {
                    $out[] = $lesson + ['module_id' => $module['id'], 'topic_id' => $topic['id']];
                }
            }
        }

        return $out;
    }
}
