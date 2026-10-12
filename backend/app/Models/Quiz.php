<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasContentStatus;

    protected $guarded = [];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort');
    }

    public function publishProblems(): array
    {
        return $this->items()->count() === 0 ? ['Kuis belum memiliki soal.'] : [];
    }
}
