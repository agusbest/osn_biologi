<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\LessonRequest;
use App\Models\Lesson;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;

class LessonController extends AdminResourceController
{
    protected string $model = Lesson::class;

    protected string $requestClass = LessonRequest::class;

    protected array $filterColumns = ['status', 'module_id', 'difficulty'];

    protected array $with = ['module:id,title,topic_id'];

    protected array $listColumns = ['id', 'module_id', 'title', 'difficulty', 'est_minutes', 'status', 'sort', 'updated_at'];

    protected function prepare(array $data, ?Model $model): array
    {
        $data['slug'] = $model?->slug ?? $this->uniqueSlug(Lesson::class, $data['title']);
        $data['sort'] ??= 0;
        // Cegah XSS: semua kolom rich-text disanitasi di server sebelum disimpan.
        foreach (Lesson::HTML_FIELDS as $f) {
            if (array_key_exists($f, $data)) {
                $data[$f] = HtmlSanitizer::clean($data[$f]);
            }
        }

        return $data;
    }

    protected function present(Model $model): mixed
    {
        return $model->load('module:id,title,topic_id', 'resources');
    }
}
