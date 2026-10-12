<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    public function test_lesson_html_is_sanitized_on_save(): void
    {
        $module = Module::factory()->create();
        $res = $this->as($this->admin())->postJson('/api/admin/lessons', [
            'module_id' => $module->id, 'title' => 'Uji XSS', 'difficulty' => 'fondasi', 'est_minutes' => 5,
            'explanation' => '<p onclick="x()">Halo</p><script>alert(1)</script><img src=x onerror=alert(1)>',
        ])->assertCreated();

        $saved = Lesson::find($res->json('id'))->explanation;
        $this->assertStringNotContainsString('<script', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertStringNotContainsString('onerror', $saved);
        $this->assertStringContainsString('Halo', $saved);
    }

    public function test_new_content_starts_as_draft_and_status_cannot_be_forced_on_create(): void
    {
        $module = Module::factory()->create();
        $res = $this->as($this->admin())->postJson('/api/admin/lessons', [
            'module_id' => $module->id, 'title' => 'Baru', 'difficulty' => 'fondasi', 'est_minutes' => 5, 'status' => 'published',
        ])->assertCreated();

        $this->assertEquals('draft', Lesson::find($res->json('id'))->status);
    }

    public function test_status_workflow_enforces_order_and_completeness(): void
    {
        $admin = $this->admin();
        $lesson = Lesson::factory()->create(['status' => 'draft']);

        // draft → published langsung tidak boleh
        $this->as($admin)->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'published'])->assertStatus(422);
        $this->as($admin)->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'review'])->assertOk();
        $this->as($admin)->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'published'])->assertOk();
        $this->as($admin)->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'archived'])->assertOk();
        $this->assertEquals('archived', $lesson->fresh()->status);
    }

    public function test_cannot_publish_incomplete_lesson_or_question(): void
    {
        $admin = $this->admin();
        $lesson = Lesson::factory()->create(['status' => 'review', 'explanation' => '']);
        $this->as($admin)->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'published'])->assertStatus(422);

        $q = Question::factory()->create(['status' => 'review', 'explanation' => ['correct_answer' => '<p>A</p>']]);
        $res = $this->as($admin)->patchJson("/api/admin/questions/{$q->id}/status", ['status' => 'published'])->assertStatus(422);
        $this->assertNotEmpty($res->json('errors.status'));
    }

    public function test_student_cannot_change_status(): void
    {
        $lesson = Lesson::factory()->create(['status' => 'draft']);
        $this->as($this->student())->patchJson("/api/admin/lessons/{$lesson->id}/status", ['status' => 'review'])->assertForbidden();
    }

    public function test_published_content_cannot_be_deleted_before_archiving(): void
    {
        $lesson = Lesson::factory()->create(['status' => 'published']);
        $this->as($this->admin())->deleteJson("/api/admin/lessons/{$lesson->id}")->assertStatus(422);
    }

    public function test_admin_creates_question_with_options_and_tags(): void
    {
        $topic = Topic::factory()->create();
        $res = $this->as($this->admin())->postJson('/api/admin/questions', [
            'topic_id' => $topic->id, 'type' => 'mcq', 'difficulty' => 'pendalaman', 'body' => '<p>Soal?</p>', 'access_level' => 0,
            'options' => [
                ['label' => 'A', 'text' => 'x', 'is_correct' => false],
                ['label' => 'B', 'text' => 'y', 'is_correct' => true],
            ],
            'explanation' => ['correct_answer' => '<p>B</p>'],
            'tags' => ['Hardy-Weinberg', 'hardy-weinberg', 'genetika'],
        ])->assertCreated();

        $q = Question::find($res->json('id'));
        $this->assertEquals(2, $q->options()->count());
        $this->assertEquals(2, $q->tags()->count()); // duplikat digabung
        $this->assertEquals('draft', $q->status);
    }

    public function test_question_requires_exactly_one_correct_option(): void
    {
        $topic = Topic::factory()->create();
        $this->as($this->admin())->postJson('/api/admin/questions', [
            'topic_id' => $topic->id, 'type' => 'mcq', 'difficulty' => 'fondasi', 'body' => '<p>S</p>', 'access_level' => 0,
            'options' => [['label' => 'A', 'text' => 'x', 'is_correct' => true], ['label' => 'B', 'text' => 'y', 'is_correct' => true]],
        ])->assertStatus(422)->assertJsonValidationErrors('options');
    }

    public function test_payment_flow_student_cannot_self_verify(): void
    {
        $plan = $this->plan('full');
        $student = $this->student();

        // status/amount dari klien diabaikan
        $res = $this->as($student)->postJson('/api/payments', [
            'plan_id' => $plan->id, 'method' => 'transfer', 'reference' => 'TRX-1', 'status' => 'verified', 'amount' => 1,
        ])->assertCreated();
        $payment = Payment::find($res->json('id'));
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals($plan->price, $payment->amount);
        $this->assertEquals(0, $this->as($student)->getJson('/api/auth/me')->json('user.access_level'));

        $this->as($student)->postJson("/api/admin/payments/{$payment->id}/verify")->assertForbidden();

        // pembayaran ganda yang masih pending ditolak
        $this->as($student)->postJson('/api/payments', ['plan_id' => $plan->id, 'method' => 'transfer', 'reference' => 'TRX-2'])->assertStatus(422);
    }

    public function test_admin_verification_grants_access_once(): void
    {
        $plan = $this->plan('full');
        $student = $this->student();
        $payment = Payment::create(['user_id' => $student->id, 'plan_id' => $plan->id, 'amount' => $plan->price, 'status' => 'pending', 'method' => 'transfer', 'reference' => 'X']);
        $admin = $this->admin();

        $this->as($admin)->postJson("/api/admin/payments/{$payment->id}/verify")->assertOk()->assertJsonPath('status', 'verified');
        $this->assertEquals(1, $this->as($student)->getJson('/api/auth/me')->json('user.access_level'));
        $this->assertEquals(1, Enrollment::where('user_id', $student->id)->where('plan_id', $plan->id)->count());

        $this->as($admin)->postJson("/api/admin/payments/{$payment->id}/verify")->assertStatus(422);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.verified']);
    }

    public function test_rejected_payment_grants_nothing(): void
    {
        $plan = $this->plan('full');
        $student = $this->student();
        $payment = Payment::create(['user_id' => $student->id, 'plan_id' => $plan->id, 'amount' => 1, 'status' => 'pending']);

        $this->as($this->admin())->postJson("/api/admin/payments/{$payment->id}/reject", ['note' => 'bukti tidak sesuai'])->assertOk();
        $this->assertEquals(0, $this->as($student)->getJson('/api/auth/me')->json('user.access_level'));
    }

    public function test_free_plan_cannot_be_purchased(): void
    {
        $free = $this->plan('free');
        $this->as($this->student())->postJson('/api/payments', ['plan_id' => $free->id, 'method' => 'transfer', 'reference' => 'x'])->assertStatus(422);
    }

    public function test_upload_rejects_svg_and_oversized_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->as($admin)->postJson('/api/admin/uploads', [
            'file' => UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml'), 'alt_text' => 'x',
        ])->assertStatus(422);
        $this->as($admin)->postJson('/api/admin/uploads', [
            'file' => UploadedFile::fake()->create('besar.png', 5000, 'image/png'), 'alt_text' => 'x',
        ])->assertStatus(422);
    }

    public function test_settings_drive_recommendation_rules(): void
    {
        $admin = $this->admin();
        $this->as($admin)->putJson('/api/admin/settings', [
            'recommendation_rules' => ['min_answers' => 2, 'tiers' => [
                ['below' => 40, 'key' => 'dasar', 'label' => 'A', 'actions' => ['x']],
                ['below' => 101, 'key' => 'lanjutan', 'label' => 'B', 'actions' => ['y']],
            ]],
        ])->assertOk();

        $this->as($admin)->getJson('/api/admin/settings')->assertOk()->assertJsonPath('recommendation_rules.min_answers', 2);
    }
}
