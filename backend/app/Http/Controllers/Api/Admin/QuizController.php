<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\QuizRequest;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class QuizController extends AdminResourceController
{
    protected string $model = Quiz::class;

    protected string $requestClass = QuizRequest::class;

    protected array $filterColumns = ['status', 'topic_id'];

    protected function prepare(array $data, ?Model $model): array
    {
        return Arr::except($data, ['items']);
    }

    protected function saved(Model $model, array $data, bool $creating): void
    {
        $items = request()->input('items');
        if ($items === null) {
            return;
        }
        $model->items()->delete();
        $seen = [];
        foreach (array_values($items) as $i => $it) {
            if (in_array($it['question_id'], $seen, true)) {
                continue;
            }
            $seen[] = $it['question_id'];
            $model->items()->create(['question_id' => $it['question_id'], 'sort' => $i, 'weight' => $it['weight'] ?? 1]);
        }
    }

    protected function present(Model $model): mixed
    {
        return $model->load('items.question:id,body,type,difficulty');
    }
}
