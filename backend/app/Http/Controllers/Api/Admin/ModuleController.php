<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\ModuleRequest;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;

class ModuleController extends AdminResourceController
{
    protected string $model = Module::class;

    protected string $requestClass = ModuleRequest::class;

    protected array $filterColumns = ['status', 'topic_id', 'access_level'];

    protected array $with = ['topic:id,title,number'];

    protected string $orderColumn = 'sort';

    protected function prepare(array $data, ?Model $model): array
    {
        $data['slug'] = $model?->slug ?? $this->uniqueSlug(Module::class, $data['title']);
        $data['sort'] ??= 0;

        return $data;
    }
}
