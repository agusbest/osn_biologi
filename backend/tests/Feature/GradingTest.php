<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class GradingTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    private function start($user, array $payload)
    {
        return $this->as($user)->postJson('/api/attempts', $payload)->assertCreated()->json('id');
    }

    public function test_correct_answer_scores_100_and_wrong_scores_0(): void
    {
        $user = $this->student();
        $q = $this->mcq();

        $id = $this->start($user, ['question_ids' => [$q->id]]);
        $res = $this->as($user)->postJson("/api/attempts/$id/submit", [
            'answers' => [['question_id' => $q->id, 'option_id' => $this->correctOptionId($q)]],
        ])->assertOk();
        $this->assertEquals(100, $res->json('attempt.score'));
        $this->assertTrue($res->json('items.0.is_correct'));

        $id2 = $this->start($user, ['question_ids' => [$q->id]]);
        $res2 = $this->as($user)->postJson("/api/attempts/$id2/submit", [
            'answers' => [['question_id' => $q->id, 'option_id' => $this->wrongOptionId($q)]],
        ])->assertOk();
        $this->assertEquals(0, $res2->json('attempt.score'));
        $this->assertFalse($res2->json('items.0.is_correct'));
    }

    public function test_unanswered_question_counts_as_wrong(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->start($user, ['question_ids' => [$q->id]]);

        $res = $this->as($user)->postJson("/api/attempts/$id/submit", [])->assertOk();
        $this->assertEquals(0, $res->json('attempt.score'));
    }

    public function test_option_from_another_question_is_ignored(): void
    {
        $user = $this->student();
        $a = $this->mcq();
        $b = $this->mcq();
        $id = $this->start($user, ['question_ids' => [$a->id]]);

        $res = $this->as($user)->postJson("/api/attempts/$id/submit", [
            'answers' => [['question_id' => $a->id, 'option_id' => $this->correctOptionId($b)]],
        ])->assertOk();
        $this->assertEquals(0, $res->json('attempt.score'));
    }

    public function test_quiz_weights_are_applied(): void
    {
        $user = $this->student();
        $a = $this->mcq();
        $b = $this->mcq();
        $quiz = Quiz::create(['title' => 'Kuis', 'status' => 'published', 'access_level' => 0]);
        $quiz->items()->create(['question_id' => $a->id, 'sort' => 0, 'weight' => 3]);
        $quiz->items()->create(['question_id' => $b->id, 'sort' => 1, 'weight' => 1]);

        $id = $this->start($user, ['quiz_id' => $quiz->id]);
        $res = $this->as($user)->postJson("/api/attempts/$id/submit", ['answers' => [
            ['question_id' => $a->id, 'option_id' => $this->correctOptionId($a)],
            ['question_id' => $b->id, 'option_id' => $this->wrongOptionId($b)],
        ]])->assertOk();

        $this->assertEquals(75, $res->json('attempt.score')); // 3 dari 4 bobot
    }

    public function test_essay_is_not_auto_graded_but_can_be_self_assessed(): void
    {
        $user = $this->student();
        $essay = Question::factory()->create(['type' => 'essay']);
        $id = $this->start($user, ['question_ids' => [$essay->id]]);

        $res = $this->as($user)->postJson("/api/attempts/$id/submit", [
            'answers' => [['question_id' => $essay->id, 'answer_text' => 'jawaban saya']],
        ])->assertOk();
        $this->assertNull($res->json('attempt.score'));
        $this->assertNull($res->json('items.0.is_correct'));

        $this->as($user)->postJson("/api/attempts/$id/answers/{$essay->id}/self-assess", ['correct' => true])->assertOk();
        $this->as($user)->getJson("/api/attempts/$id/results")->assertOk()->assertJsonPath('attempt.score', 100);
    }

    public function test_cannot_submit_twice(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->start($user, ['question_ids' => [$q->id]]);
        $this->as($user)->postJson("/api/attempts/$id/submit", [])->assertOk();
        $this->as($user)->postJson("/api/attempts/$id/submit", [])->assertStatus(409);
    }

    public function test_answers_after_deadline_are_not_graded(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $quiz = Quiz::create(['title' => 'Bertimer', 'status' => 'published', 'access_level' => 0, 'duration_minutes' => 5]);
        $quiz->items()->create(['question_id' => $q->id, 'sort' => 0, 'weight' => 1]);
        $id = $this->start($user, ['quiz_id' => $quiz->id]);

        // Waktu dihitung server: majukan jam melewati tenggat + toleransi.
        $this->travel(10)->minutes();
        $res = $this->as($user)->postJson("/api/attempts/$id/submit", [
            'answers' => [['question_id' => $q->id, 'option_id' => $this->correctOptionId($q)]],
        ])->assertOk();

        $this->assertEquals(0, $res->json('attempt.score'));
        $this->assertTrue($res->json('attempt.timed_out'));
    }

    public function test_retry_creates_new_attempt_with_same_questions(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->start($user, ['question_ids' => [$q->id]]);
        $this->as($user)->postJson("/api/attempts/$id/submit", [])->assertOk();

        $new = $this->as($user)->postJson("/api/attempts/$id/retry", ['only_wrong' => true])->assertCreated()->json('id');
        $this->assertNotEquals($id, $new);
        $this->as($user)->getJson("/api/attempts/$new")->assertOk()->assertJsonCount(1, 'items');
    }
}
