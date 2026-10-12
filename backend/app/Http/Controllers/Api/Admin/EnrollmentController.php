<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Plan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Enrollment::with('user:id,name,email', 'plan:id,name,code');
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->input('user_id'));
        }

        return response()->json($q->orderByDesc('id')->paginate(50));
    }

    /** Pemberian akses manual oleh admin (mis. beasiswa / pembayaran di luar sistem). */
    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);
        $plan = Plan::findOrFail($d['plan_id']);
        $ends = $d['ends_at'] ?? ($plan->duration_days ? now()->addDays($plan->duration_days) : null);
        $e = Enrollment::create([
            'user_id' => $d['user_id'], 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => $ends, 'granted_by' => $request->user()->id,
        ]);
        AuditLogger::log($request->user(), 'enrollment.granted', $e, ['user' => User::find($d['user_id'])?->email, 'plan' => $plan->code]);

        return response()->json($e, 201);
    }

    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        $d = $request->validate([
            'status' => ['sometimes', Rule::in(['active', 'cancelled'])],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ]);
        $enrollment->update($d);
        AuditLogger::log($request->user(), 'enrollment.updated', $enrollment, $d);

        return response()->json($enrollment->fresh());
    }
}
