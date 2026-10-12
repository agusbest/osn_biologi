<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedInteger('price')->default(0);
            $t->unsignedInteger('duration_days')->nullable(); // null = tanpa batas
            $t->unsignedTinyInteger('access_level')->default(0); // 0 free, 1 full, 2 premium
            $t->json('features')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20)->default('active'); // active|cancelled
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('amount')->default(0);
            $t->string('status', 20)->default('pending'); // pending|verified|rejected
            $t->string('method', 30)->nullable();
            $t->string('reference')->nullable();
            $t->text('note')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('plans');
    }
};
