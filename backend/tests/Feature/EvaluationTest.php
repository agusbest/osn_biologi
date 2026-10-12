<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    private function answer(User $u, Question $q, bool $correct): int
    {
        $id = $this->as($u)->postJson('/api/attempts', ['question_ids' => [$q->id]])->assertCreated()->json('id');
        $this->as($u)->postJson("/api/attempts/$id/submit", ['answers' => [[
            'question_id' => $q->id, 'option_id' => $correct ? $this->correctOptionId($q) : $this->wrongOptionId($q),
        ]]])->assertOk();

        return $id;
    }

    public function test_topic_scores_weak_topics_and_default_recommendation_tiers(): void
    {
        $user = $this->student();
        $weak = Topic::factory()->create(['title' => 'Topik Lemah']);
        $strong = Topic::factory()->create(['title' => 'Topik Kuat']);

        foreach ([true, false, false, false] as $ok) {        // 25%
            $this->answer($user, $this->mcq(['topic_id' => $weak->id]), $ok);
        }
        foreach ([true, true, true, true, false] as $ok) {    // 80%
            $this->answer($user, $this->mcq(['topic_id' => $strong->id]), $ok);
        }

        $res = $this->as($user)->getJson('/api/evaluation')->assertOk();
        $byTitle = collect($res->json('recommendations'))->keyBy('title');
        $this->assertEquals(25.0, $byTitle['Topik Lemah']['accuracy']);
        $this->assertEquals('dasar', $byTitle['Topik Lemah']['tier']);      // < 60
        $this->assertEquals('lanjutan', $byTitle['Topik Kuat']['tier']);    // >= 80

        $dash = $this->as($user)->getJson('/api/dashboard')->assertOk();
        $this->assertEquals(['Topik Lemah'], collect($dash->json('weak_topics'))->pluck('title')->all());
    }

    public function test_middle_tier_is_applications(): void
    {
        $user = $this->student();
        $topic = Topic::factory()->create(['title' => 'Topik Tengah']);
        foreach ([true, true, true, false] as $ok) {          // 75%
            $this->answer($user, $this->mcq(['topic_id' => $topic->id]), $ok);
        }

        $this->as($user)->getJson('/api/evaluation')->assertOk()->assertJsonPath('recommendations.0.tier', 'aplikasi');
    }

    public function test_rules_are_read_from_settings_not_hardcoded(): void
    {
        app(SettingsService::class)->set('recommendation_rules', ['min_answers' => 1, 'tiers' => [
            ['below' => 30, 'key' => 'dasar', 'label' => 'D', 'actions' => ['a']],
            ['below' => 101, 'key' => 'lanjutan', 'label' => 'L', 'actions' => ['b']],
        ]]);
        $user = $this->student();
        $topic = Topic::factory()->create();
        $this->answer($user, $this->mcq(['topic_id' => $topic->id]), false);

        // 0% → tier pertama; dan min_answers=1 sehingga langsung muncul
        $this->as($user)->getJson('/api/evaluation')->assertJsonPath('recommendations.0.tier', 'dasar');
    }

    public function test_wrong_answers_are_recorded_and_exclude_correct_ones(): void
    {
        $user = $this->student();
        $wrong = $this->mcq(['body' => '<p>SOAL-SALAH</p>']);
        $right = $this->mcq(['body' => '<p>SOAL-BENAR</p>']);
        $this->answer($user, $wrong, false);
        $this->answer($user, $right, true);

        $body = $this->as($user)->getJson('/api/evaluation')->assertOk()->getContent();
        $this->assertStringContainsString('SOAL-SALAH', $body);
        $this->assertStringNotContainsString('SOAL-BENAR', $body);
    }

    public function test_bank_status_and_filters(): void
    {
        $user = $this->student();
        $a = $this->mcq(['difficulty' => 'fondasi']);
        $b = $this->mcq(['difficulty' => 'tantangan']);
        $this->answer($user, $a, false);

        $this->as($user)->getJson('/api/questions?status=salah')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $a->id);
        $this->as($user)->getJson('/api/questions?status=belum')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $b->id);
        $this->as($user)->getJson('/api/questions?difficulty=tantangan')->assertOk()->assertJsonCount(1, 'data');

        $this->as($user)->postJson("/api/questions/{$b->id}/bookmark", ['kind' => 'favorite'])->assertCreated();
        $this->as($user)->getJson('/api/questions?status=favorit')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.favorite', true);
    }

    public function test_progress_and_continue_learning(): void
    {
        $user = $this->student();
        $module = Module::factory()->create();
        $first = Lesson::factory()->create(['module_id' => $module->id, 'sort' => 1]);
        $second = Lesson::factory()->create(['module_id' => $module->id, 'sort' => 2]);

        $dash = $this->as($user)->getJson('/api/dashboard')->assertOk();
        $this->assertEquals(0, $dash->json('progress_percent'));
        $this->assertEquals($first->id, $dash->json('continue.lesson_id'));

        $this->as($user)->postJson("/api/lessons/{$first->id}/complete")->assertOk();
        $dash = $this->as($user)->getJson('/api/dashboard')->assertOk();
        $this->assertEquals(50, $dash->json('progress_percent'));
        $this->assertEquals($second->id, $dash->json('continue.lesson_id'));
        $this->assertEquals(0, $dash->json('modules_done'));

        $this->as($user)->postJson("/api/lessons/{$second->id}/complete")->assertOk();
        $this->assertEquals(1, $this->as($user)->getJson('/api/dashboard')->json('modules_done'));
    }

    public function test_profile_daily_target_respects_admin_range(): void
    {
        $user = $this->student();
        $this->as($user)->putJson('/api/profile', ['daily_target_minutes' => 45])->assertOk()->assertJsonPath('user.daily_target_minutes', 45);
        $this->as($user)->putJson('/api/profile', ['daily_target_minutes' => 5])->assertStatus(422);
        $this->as($user)->putJson('/api/profile', ['daily_target_minutes' => 600])->assertStatus(422);
    }
}
