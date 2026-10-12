<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasContentStatus, HasFactory;

    public const TYPES = ['mcq', 'truefalse', 'essay'];

    /** Tujuh bagian pembahasan wajib. */
    public const EXPLANATION_KEYS = [
        'correct_answer' => 'Jawaban benar',
        'concept_tested' => 'Konsep yang diuji',
        'solution_steps' => 'Langkah penyelesaian',
        'why_others_wrong' => 'Alasan opsi lain salah',
        'practical_trick' => 'Trik praktis',
        'common_mistakes' => 'Kesalahan umum',
        'similar_questions' => 'Soal sejenis',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['explanation' => 'array', 'access_level' => 'integer'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(QuestionTag::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('questions.status', 'published')
            ->whereHas('topic', fn ($t) => $t->where('status', 'published'))
            ->where(fn ($q) => $q->whereNull('questions.module_id')
                ->orWhereHas('module', fn ($m) => $m->where('status', 'published')));
    }

    public function isVisible(): bool
    {
        $this->loadMissing('topic', 'module');

        return $this->status === 'published'
            && $this->topic?->status === 'published'
            && ($this->module === null || $this->module->status === 'published');
    }

    public function publishProblems(): array
    {
        $p = [];
        $this->loadMissing('options');
        if (trim(strip_tags((string) $this->body)) === '') {
            $p[] = 'Teks soal kosong.';
        }
        if ($this->type !== 'essay') {
            $n = $this->options->count();
            $correct = $this->options->where('is_correct', true)->count();
            if ($this->type === 'truefalse' && $n !== 2) {
                $p[] = 'Soal benar/salah harus punya tepat 2 opsi.';
            }
            if ($this->type === 'mcq' && $n < 2) {
                $p[] = 'Pilihan ganda minimal punya 2 opsi.';
            }
            if ($correct !== 1) {
                $p[] = 'Harus ada tepat satu kunci jawaban.';
            }
        }
        foreach (self::EXPLANATION_KEYS as $k => $label) {
            if (trim(strip_tags((string) ($this->explanation[$k] ?? ''))) === '') {
                $p[] = "Pembahasan bagian \"$label\" belum diisi.";
            }
        }

        return $p;
    }
}
