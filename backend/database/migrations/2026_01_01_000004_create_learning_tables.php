<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quiz_id')->nullable()->constrained()->nullOnDelete();
            $t->string('mode', 20)->default('bank'); // bank|quiz
            $t->string('status', 20)->default('in_progress'); // in_progress|submitted
            $t->timestamp('started_at')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('deadline_at')->nullable();
            $t->boolean('timed_out')->default(false);
            $t->decimal('score', 5, 2)->nullable();
            $t->unsignedSmallInteger('correct_count')->default(0);
            $t->unsignedSmallInteger('graded_count')->default(0);
            $t->unsignedSmallInteger('total_count')->default(0);
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });

        Schema::create('attempt_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $t->text('answer_text')->nullable();
            $t->boolean('is_correct')->nullable(); // null = belum dinilai (uraian)
            $t->boolean('self_assessed')->default(false);
            $t->unsignedTinyInteger('weight')->default(1);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
            $t->unique(['attempt_id', 'question_id']);
            $t->index('question_id');
        });

        Schema::create('lesson_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20)->default('in_progress'); // in_progress|completed
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('last_opened_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });

        Schema::create('bookmarks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 20); // favorite|hard
            $t->timestamps();
            $t->unique(['user_id', 'question_id', 'kind']);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 80)->index();
            $t->string('subject_type', 80)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'settings', 'bookmarks', 'lesson_progress', 'attempt_answers', 'quiz_attempts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
