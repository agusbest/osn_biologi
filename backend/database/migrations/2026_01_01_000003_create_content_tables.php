<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "courses" disiapkan untuk multi-kursus di masa depan; MVP memakai satu kursus.
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->timestamps();
        });

        Schema::create('topics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedTinyInteger('number')->nullable();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('type', 20)->default('theory'); // theory|practicum
            $t->string('status', 20)->default('draft')->index();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('summary')->nullable();
            $t->unsignedTinyInteger('access_level')->default(0);
            $t->string('status', 20)->default('draft')->index();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('module_id')->constrained()->cascadeOnDelete();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('difficulty', 20)->default('fondasi'); // fondasi|pendalaman|tantangan
            $t->unsignedSmallInteger('est_minutes')->default(10);
            $t->longText('objectives')->nullable();
            $t->longText('explanation')->nullable();
            $t->longText('key_concepts')->nullable();
            $t->longText('osn_thinking')->nullable();
            $t->longText('worked_example')->nullable();
            $t->longText('practice')->nullable();
            $t->longText('summary')->nullable();
            $t->json('glossary')->nullable();
            $t->json('reference_list')->nullable();
            $t->string('status', 20)->default('draft')->index();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('lesson_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('type', 20)->default('image');
            $t->string('path');
            $t->string('alt_text')->nullable();
            $t->string('caption')->nullable();
            $t->unsignedInteger('size')->default(0);
            $t->timestamps();
        });

        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $t->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20)->default('mcq'); // mcq|truefalse|essay
            $t->string('difficulty', 20)->default('fondasi');
            $t->longText('body');
            $t->json('explanation')->nullable(); // 7 bagian pembahasan
            $t->unsignedTinyInteger('access_level')->default(0);
            $t->string('status', 20)->default('draft')->index();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->timestamps();
            $t->index(['topic_id', 'status']);
        });

        Schema::create('question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('label', 5);
            $t->text('text');
            $t->boolean('is_correct')->default(false);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('question_tags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('tag', 60);
            $t->unique(['question_id', 'tag']);
        });

        Schema::create('quizzes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('duration_minutes')->nullable(); // dikonfigurasi admin
            $t->unsignedTinyInteger('access_level')->default(0);
            $t->string('status', 20)->default('draft')->index();
            $t->string('syllabus_version', 20)->default('2026.1');
            $t->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->unsignedTinyInteger('weight')->default(1); // bobot dikonfigurasi admin
            $t->unique(['quiz_id', 'question_id']);
        });
    }

    public function down(): void
    {
        foreach (['quiz_questions', 'quizzes', 'question_tags', 'question_options', 'questions', 'lesson_resources', 'lessons', 'modules', 'topics', 'courses'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
