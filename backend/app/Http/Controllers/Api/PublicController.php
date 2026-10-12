<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Plan;
use App\Models\Topic;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public static function planPayload(Plan $p): array
    {
        return [
            'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'description' => $p->description,
            'price' => $p->price, 'duration_days' => $p->duration_days,
            'access_level' => $p->access_level, 'features' => $p->features ?? [],
        ];
    }

    /** Data landing page: paket, kurikulum ringkas, dan info materi demo gratis. */
    public function landing(SettingsService $settings): JsonResponse
    {
        $topics = Topic::published()->orderBy('sort')->withCount(['modules' => fn ($q) => $q->where('status', 'published')])->get()
            ->map(fn ($t) => [
                'id' => $t->id, 'number' => $t->number, 'title' => $t->title,
                'description' => $t->description, 'type' => $t->type, 'module_count' => $t->modules_count,
            ]);

        $demo = $this->demoLesson($settings);

        return response()->json([
            'site_name' => $settings->get('site_name'),
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get()->map(fn ($p) => self::planPayload($p)),
            'topics' => $topics,
            'demo' => $demo ? ['id' => $demo->id, 'title' => $demo->title, 'est_minutes' => $demo->est_minutes, 'difficulty' => $demo->difficulty] : null,
        ]);
    }

    /** Materi demo gratis (publik): hanya lesson terbit pada modul level 0. */
    public function demo(SettingsService $settings): JsonResponse
    {
        $lesson = $this->demoLesson($settings);
        abort_unless($lesson, 404, 'Materi demo belum tersedia.');
        $lesson->load('module.topic');

        return response()->json(['lesson' => [
            'id' => $lesson->id, 'title' => $lesson->title, 'difficulty' => $lesson->difficulty,
            'est_minutes' => $lesson->est_minutes, 'objectives' => $lesson->objectives,
            'explanation' => $lesson->explanation, 'key_concepts' => $lesson->key_concepts,
            'summary' => $lesson->summary, 'glossary' => $lesson->glossary ?? [],
            'topic' => $lesson->module->topic->title, 'module' => $lesson->module->title,
        ]]);
    }

    private function demoLesson(SettingsService $settings): ?Lesson
    {
        $free = fn ($q) => $q->visible()->whereHas('module', fn ($m) => $m->where('access_level', 0));

        if ($id = $settings->get('demo_lesson_id')) {
            $l = $free(Lesson::query())->find($id);
            if ($l) {
                return $l;
            }
        }

        return $free(Lesson::query())->orderBy('id')->first();
    }
}
