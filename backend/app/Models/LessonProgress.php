<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'last_opened_at' => 'datetime'];
    }
}
