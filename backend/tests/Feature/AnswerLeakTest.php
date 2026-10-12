<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

/** Kunci jawaban & pembahasan tidak boleh sampai ke browser sebelum submit. */
class AnswerLeakTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    public function test_question_list_never_contains_keys_or_explanations(): void
    {
        $user = $this->student();
        $this->mcq();

        $body = $this->as($user)->getJson('/api/questions')->assertOk()->getContent();

        $this->assertStringNotContainsString('is_correct', $body);
        $this->assertStringNotContainsString('explanation', $body);
        $this->assertStringNotContainsString('correct_option_ids', $body);
    }

    public function test_in_progress_attempt_does_not_leak(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->as($user)->postJson('/api/attempts', ['question_ids' => [$q->id]])->assertCreated()->json('id');

        $body = $this->as($user)->getJson("/api/attempts/$id")->assertOk()->getContent();

        $this->assertStringNotContainsString('is_correct', $body);
        $this->assertStringNotContainsString('explanation', $body);
        $this->assertStringNotContainsString('Soal sejenis', $body);
        $this->assertStringContainsString('Soal contoh', $body); // isi soal tetap tampil
    }

    public function test_results_are_blocked_until_submitted(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->as($user)->postJson('/api/attempts', ['question_ids' => [$q->id]])->assertCreated()->json('id');

        $this->as($user)->getJson("/api/attempts/$id/results")->assertStatus(409);
    }

    public function test_after_submit_results_include_key_and_seven_part_explanation(): void
    {
        $user = $this->student();
        $q = $this->mcq();
        $id = $this->as($user)->postJson('/api/attempts', ['question_ids' => [$q->id]])->assertCreated()->json('id');
        $this->as($user)->postJson("/api/attempts/$id/submit", [])->assertOk();

        $res = $this->as($user)->getJson("/api/attempts/$id/results")->assertOk();
        $res->assertJsonPath('items.0.correct_option_ids.0', $this->correctOptionId($q));
        $res->assertJsonCount(7, 'items.0.explanation');
    }

    public function test_student_cannot_read_another_students_attempt(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $q = $this->mcq();
        $id = $this->as($owner)->postJson('/api/attempts', ['question_ids' => [$q->id]])->assertCreated()->json('id');
        $this->as($owner)->postJson("/api/attempts/$id/submit", [])->assertOk();

        $this->as($other)->getJson("/api/attempts/$id")->assertForbidden();
        $this->as($other)->getJson("/api/attempts/$id/results")->assertForbidden();
        $this->as($other)->putJson("/api/attempts/$id/answers", ['answers' => []])->assertForbidden();
    }
}
