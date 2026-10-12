<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Basis CRUD CMS. Status konten TIDAK bisa diubah lewat store/update:
 * perpindahan draft → review → published → archived hanya lewat StatusController.
 */
abstract class AdminResourceController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string */
    protected string $requestClass;

    protected array $with = [];

    protected array $filterColumns = [];

    protected string $searchColumn = 'title';

    protected string $orderColumn = 'id';

    protected string $orderDir = 'asc';

    protected array $listColumns = ['*'];

    protected function prepare(array $data, ?Model $model): array
    {
        return $data;
    }

    protected function saved(Model $model, array $data, bool $creating): void
    {
    }

    protected function present(Model $model): mixed
    {
        return $model;
    }

    /** Hapus nilai null untuk kolom ber-default (mis. syllabus_version) agar tidak melanggar NOT NULL. */
    private function clean(array $data): array
    {
        if (array_key_exists('syllabus_version', $data) && $data['syllabus_version'] === null) {
            unset($data['syllabus_version']);
        }

        return $data;
    }

    protected function name(): string
    {
        return Str::snake(class_basename($this->model));
    }

    public function index(Request $request): JsonResponse
    {
        $q = ($this->model)::query()->select($this->listColumns)->with($this->with);
        foreach ($this->filterColumns as $col) {
            if ($request->filled($col)) {
                $q->where($col, $request->input($col));
            }
        }
        if ($request->filled('q') && $this->searchColumn) {
            $q->whereLike($this->searchColumn, '%' . $request->input('q') . '%');
        }

        $page = $q->orderBy($this->orderColumn, $this->orderDir)
            ->paginate(min(200, max(1, (int) $request->input('per_page', 50))));

        return response()->json($page);
    }

    public function show($id): JsonResponse
    {
        $model = ($this->model)::findOrFail($id);

        return response()->json($this->present($model));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->clean($this->prepare(app($this->requestClass)->validated(), null));
        $model = ($this->model)::create($data);
        $this->saved($model, $data, true);
        AuditLogger::log($request->user(), $this->name() . '.created', $model);

        return response()->json($this->present($model->fresh()), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $model = ($this->model)::findOrFail($id);
        $data = $this->clean($this->prepare(app($this->requestClass)->validated(), $model));
        $model->update($data);
        $this->saved($model, $data, false);
        AuditLogger::log($request->user(), $this->name() . '.updated', $model);

        return response()->json($this->present($model->fresh()));
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $model = ($this->model)::findOrFail($id);
        abort_if(
            isset($model->status) && $model->status === 'published',
            422, 'Konten yang sudah terbit tidak bisa dihapus. Arsipkan dulu.'
        );
        $model->delete();
        AuditLogger::log($request->user(), $this->name() . '.deleted', $model);

        return response()->json(['ok' => true]);
    }

    protected function uniqueSlug(string $modelClass, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($modelClass::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
