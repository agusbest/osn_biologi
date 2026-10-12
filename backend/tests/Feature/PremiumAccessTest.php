<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

/** Konten berbayar dijaga di backend (bukan hanya disembunyikan di menu). */
class PremiumAccessTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    private function paidLesson(): Lesson
    {
        $module = Module::factory()->paid(1)->create();

        return Lesson::factory()->create(['module_id' => $module->id]);
    }

    public function test_free_student_gets_403_on_paid_lesson(): void
    {
        $lesson = $this->paidLesson();
        $this->as($this->student())->getJson("/api/lessons/{$lesson->id}")->assertForbidden();
        $this->as($this->student())->postJson("/api/lessons/{$lesson->id}/complete")->assertForbidden();
    }

    public function test_student_with_active_enrollment_can_open_paid_lesson(): void
    {
        $lesson = $this->paidLesson();
        $this->as($this->student('full'))->getJson("/api/lessons/{$lesson->id}")->assertOk()
            ->assertJsonPath('lesson.id', $lesson->id);
    }

    public function test_expired_enrollment_loses_access(): void
    {
        $lesson = $this->paidLesson();
        $user = $this->student();
        $this->enroll($user, 'full', now()->subDay());

        $this->as($user)->getJson("/api/lessons/{$lesson->id}")->assertForbidden();
    }

    public function test_cancelled_enrollment_loses_access(): void
    {
        $lesson = $this->paidLesson();
        $user = $this->student();
        $this->enroll($user, 'full')->update(['status' => 'cancelled']);

        $this->as($user)->getJson("/api/lessons/{$lesson->id}")->assertForbidden();
    }

    public function test_premium_level_content_needs_premium_plan(): void
    {
        $module = Module::factory()->paid(2)->create();
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->as($this->student('full'))->getJson("/api/lessons/{$lesson->id}")->assertForbidden();
    }

    public function test_locked_lesson_is_listed_as_locked_in_curriculum_without_content(): void
    {
        $lesson = $this->paidLesson();
        $res = $this->as($this->student())->getJson('/api/curriculum')->assertOk();

        $this->assertStringContainsString('terkunci', $res->getContent());
        $this->assertStringNotContainsString('Contoh penjelasan', $res->getContent());
    }

    public function test_free_student_cannot_start_attempt_with_paid_question_or_quiz(): void
    {
        $user = $this->student();
        $paid = $this->mcq(['access_level' => 1]);
        $this->as($user)->postJson('/api/attempts', ['question_ids' => [$paid->id]])->assertForbidden();

        $quiz = Quiz::create(['title' => 'Berbayar', 'status' => 'published', 'access_level' => 1]);
        $quiz->items()->create(['question_id' => $paid->id, 'sort' => 0, 'weight' => 1]);
        $this->as($user)->postJson('/api/attempts', ['quiz_id' => $quiz->id])->assertForbidden();
    }

    public function test_bank_filters_never_draw_paid_questions_for_free_student(): void
    {
        $user = $this->student();
        $this->mcq(['access_level' => 1]);

        $this->as($user)->postJson('/api/attempts', ['count' => 5])->assertStatus(422); // tidak ada soal yang boleh diakses
    }

    public function test_paid_question_text_is_hidden_in_list_for_free_student(): void
    {
        $this->mcq(['access_level' => 1, 'body' => '<p>RAHASIA-BERBAYAR</p>']);
        $res = $this->as($this->student())->getJson('/api/questions')->assertOk();

        $this->assertStringNotContainsString('RAHASIA-BERBAYAR', $res->getContent());
        $res->assertJsonPath('data.0.locked', true);
    }

    public function test_guest_gets_401(): void
    {
        $lesson = $this->paidLesson();
        $this->getJson("/api/lessons/{$lesson->id}")->assertUnauthorized();
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
