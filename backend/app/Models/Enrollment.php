<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function scopeActive($query)
    {
        return $query->where('enrollments.status', 'active')
            ->where(fn ($q) => $q->whereNull('enrollments.starts_at')->orWhere('enrollments.starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('enrollments.ends_at')->orWhere('enrollments.ends_at', '>', now()));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
