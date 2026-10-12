<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\TopicRequest;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;

class TopicController extends AdminResourceController
{
    protected string $model = Topic::class;

    protected string $requestClass = TopicRequest::class;

    protected array $filterColumns = ['status', 'type'];

    protected string $orderColumn = 'sort';

    protected function prepare(array $data, ?Model $model): array
    {
        $data['slug'] = $model?->slug ?? $this->uniqueSlug(Topic::class, $data['title']);
        $data['sort'] ??= $data['number'] ?? 0;

        return $data;
    }
}
