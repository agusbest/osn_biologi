<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\PlanRequest;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends AdminResourceController
{
    protected string $model = Plan::class;

    protected string $requestClass = PlanRequest::class;

    protected string $searchColumn = 'name';

    protected string $orderColumn = 'sort';

    protected function prepare(array $data, ?Model $model): array
    {
        $data['sort'] ??= 0;
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        abort_if($plan->code === 'free', 422, 'Paket Free tidak boleh dihapus.');
        abort_if(\App\Models\Enrollment::where('plan_id', $plan->id)->exists(), 422, 'Paket sudah dipakai enrollment. Nonaktifkan saja.');

        return parent::destroy($request, $id);
    }
}
