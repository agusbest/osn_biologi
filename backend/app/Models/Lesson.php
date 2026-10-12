<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasContentStatus, HasFactory;

    public const DIFFICULTIES = ['fondasi', 'pendalaman', 'tantangan'];

    /** Kolom HTML (rich text) yang wajib disanitasi saat disimpan. */
    public const HTML_FIELDS = ['objectives', 'explanation', 'key_concepts', 'osn_thinking', 'worked_example', 'practice', 'summary'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['glossary' => 'array', 'reference_list' => 'array', 'est_minutes' => 'integer'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class);
    }

    /** Terbit + modul terbit + topik terbit. */
    public function scopeVisible($query)
    {
        return $query->where('lessons.status', 'published')
            ->whereHas('module', fn ($m) => $m->where('status', 'published')
                ->whereHas('topic', fn ($t) => $t->where('status', 'published')));
    }

    public function isVisible(): bool
    {
        $this->loadMissing('module.topic');

        return $this->status === 'published'
            && $this->module?->status === 'published'
            && $this->module?->topic?->status === 'published';
    }

    public function publishProblems(): array
    {
        $p = [];
        foreach (['objectives' => 'Target pembelajaran', 'explanation' => 'Penjelasan sederhana', 'summary' => 'Ringkasan cepat'] as $f => $label) {
            if (trim(strip_tags((string) $this->{$f})) === '') {
                $p[] = "Bagian \"$label\" masih kosong.";
            }
        }

        return $p;
    }
}
