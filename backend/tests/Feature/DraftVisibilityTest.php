<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

/** Konten selain "published" tidak boleh terlihat siswa. */
class DraftVisibilityTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    public function test_draft_lesson_is_404_for_students_and_missing_from_curriculum(): void
    {
        $lesson = Lesson::factory()->create(['status' => 'draft', 'title' => 'JUDUL-DRAFT-RAHASIA']);
        $student = $this->student();

        $this->as($student)->getJson("/api/lessons/{$lesson->id}")->assertNotFound();
        $this->assertStringNotContainsString('JUDUL-DRAFT-RAHASIA', $this->as($student)->getJson('/api/curriculum')->getContent());
    }

    public function test_review_and_archived_lessons_are_hidden(): void
    {
        $student = $this->student();
        foreach (['review', 'archived'] as $status) {
            $lesson = Lesson::factory()->create(['status' => $status]);
            $this->as($student)->getJson("/api/lessons/{$lesson->id}")->assertNotFound();
        }
    }

    public function test_published_lesson_in_draft_module_is_hidden(): void
    {
        $module = Module::factory()->create(['status' => 'draft']);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'status' => 'published']);

        $this->as($this->student())->getJson("/api/lessons/{$lesson->id}")->assertNotFound();
    }

    public function test_published_lesson_in_draft_topic_is_hidden(): void
    {
        $topic = Topic::factory()->create(['status' => 'draft']);
        $module = Module::factory()->create(['topic_id' => $topic->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->as($this->student())->getJson("/api/lessons/{$lesson->id}")->assertNotFound();
    }

    public function test_draft_questions_are_not_listed_or_usable(): void
    {
        $draft = $this->mcq(['status' => 'draft', 'body' => '<p>SOAL-DRAFT-RAHASIA</p>']);
        $student = $this->student();

        $this->assertStringNotContainsString('SOAL-DRAFT-RAHASIA', $this->as($student)->getJson('/api/questions')->getContent());
        $this->as($student)->postJson('/api/attempts', ['question_ids' => [$draft->id]])->assertNotFound();
    }

    public function test_admin_can_still_preview_draft(): void
    {
        $lesson = Lesson::factory()->create(['status' => 'draft']);
        $this->as($this->admin())->getJson("/api/lessons/{$lesson->id}")->assertOk();
    }

    public function test_landing_demo_only_shows_published_free_content(): void
    {
        Lesson::factory()->create(['status' => 'draft', 'title' => 'DEMO-DRAFT']);
        $paid = Module::factory()->paid(1)->create();
        Lesson::factory()->create(['module_id' => $paid->id, 'title' => 'DEMO-BERBAYAR']);

        $body = $this->getJson('/api/public/landing')->assertOk()->getContent();
        $this->assertStringNotContainsString('DEMO-DRAFT', $body);
        $this->assertStringNotContainsString('DEMO-BERBAYAR', $body);
        $this->getJson('/api/public/demo')->assertNotFound();
    }
}
