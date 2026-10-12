<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Question;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class QuestionController extends AdminResourceController
{
    protected string $model = Question::class;

    protected string $requestClass = QuestionRequest::class;

    protected array $filterColumns = ['status', 'topic_id', 'module_id', 'type', 'difficulty'];

    protected string $searchColumn = 'body';

    protected array $with = ['topic:id,title', 'tags'];

    protected array $listColumns = ['id', 'topic_id', 'module_id', 'type', 'difficulty', 'body', 'access_level', 'status', 'updated_at'];

    protected function prepare(array $data, ?Model $model): array
    {
        $data['body'] = HtmlSanitizer::clean($data['body']);

        $explanation = [];
        foreach (array_keys(Question::EXPLANATION_KEYS) as $k) {
            $explanation[$k] = HtmlSanitizer::clean(Arr::get($data, "explanation.$k")) ?? '';
        }
        $data['explanation'] = $explanation;

        return Arr::except($data, ['options', 'tags']);
    }

    protected function saved(Model $model, array $data, bool $creating): void
    {
        $request = request();
        $type = $model->type;

        // Opsi: pembaruan di tempat (jaga id) agar riwayat jawaban tetap terkait.
        $keep = [];
        if ($type !== 'essay') {
            foreach (array_values($request->input('options', [])) as $i => $o) {
                $attrs = [
                    'label' => $o['label'], 'text' => $o['text'],
                    'is_correct' => (bool) ($o['is_correct'] ?? false), 'sort' => $i,
                ];
                $row = ! empty($o['id']) ? $model->options()->find($o['id']) : null;
                if ($row) {
                    $row->update($attrs);
                } else {
                    $row = $model->options()->create($attrs);
                }
                $keep[] = $row->id;
            }
        }
        $model->options()->whereNotIn('id', $keep)->delete();

        $model->tags()->delete();
        foreach (collect($request->input('tags', []))->map(fn ($t) => mb_strtolower(trim($t)))->filter()->unique() as $tag) {
            $model->tags()->create(['tag' => $tag]);
        }
    }

    protected function present(Model $model): mixed
    {
        return $model->load('topic:id,title', 'module:id,title', 'options', 'tags');
    }
}
