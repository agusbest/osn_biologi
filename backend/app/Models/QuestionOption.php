<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
