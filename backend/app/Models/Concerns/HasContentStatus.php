<?php

namespace App\Models\Concerns;

/**
 * Alur status konten: draft → review → published → archived.
 * Hanya konten "published" yang boleh terlihat oleh siswa.
 */
trait HasContentStatus
{
    public static function statuses(): array
    {
        return ['draft', 'review', 'published', 'archived'];
    }

    public static function allowedTransitions(string $from): array
    {
        return [
            'draft' => ['review'],
            'review' => ['draft', 'published'],
            'published' => ['review', 'archived'],
            'archived' => ['draft'],
        ][$from] ?? [];
    }

    public function scopePublished($query)
    {
        return $query->where($this->getTable() . '.status', 'published');
    }

    /** Daftar masalah yang menghalangi penerbitan (kosong = siap terbit). */
    public function publishProblems(): array
    {
        return [];
    }
}
