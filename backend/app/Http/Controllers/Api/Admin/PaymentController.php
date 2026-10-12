<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Verifikasi pembayaran manual. Satu-satunya tempat status pembayaran berubah
 * (arsitektur siap diganti payment gateway: cukup panggil logika verify() dari webhook).
 */
class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Payment::with('user:id,name,email', 'plan:id,name,code');
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return response()->json($q->orderByDesc('id')->paginate(50));
    }

    public function verify(Request $request, Payment $payment): JsonResponse
    {
        DB::transaction(function () use ($request, $payment) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            abort_unless($payment->status === 'pending', 422, 'Pembayaran ini sudah diproses.');

            $plan = $payment->plan;
            // Perpanjang dari akhir masa aktif paket yang sama bila masih berjalan.
            $current = Enrollment::active()->where('enrollments.user_id', $payment->user_id)
                ->where('plan_id', $plan->id)->whereNotNull('ends_at')->orderByDesc('ends_at')->first();
            $start = $current ? $current->ends_at : now();
            $ends = $plan->duration_days ? $start->copy()->addDays($plan->duration_days) : null;

            $enrollment = Enrollment::create([
                'user_id' => $payment->user_id, 'plan_id' => $plan->id, 'status' => 'active',
                'starts_at' => now(), 'ends_at' => $ends, 'granted_by' => $request->user()->id,
            ]);
            $payment->forceFill([
                'status' => 'verified', 'verified_by' => $request->user()->id,
                'verified_at' => now(), 'enrollment_id' => $enrollment->id,
            ])->save();
            AuditLogger::log($request->user(), 'payment.verified', $payment, ['enrollment_id' => $enrollment->id]);
        });

        return response()->json($payment->fresh()->load('user:id,name,email', 'plan:id,name,code'));
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $d = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        abort_unless($payment->status === 'pending', 422, 'Pembayaran ini sudah diproses.');
        $payment->forceFill([
            'status' => 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now(),
            'note' => $d['note'] ?? $payment->note,
        ])->save();
        AuditLogger::log($request->user(), 'payment.rejected', $payment);

        return response()->json($payment->fresh()->load('user:id,name,email', 'plan:id,name,code'));
    }
}
