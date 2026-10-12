<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StorePaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\AccessService;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request, AccessService $access, SettingsService $settings): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'access_level' => $access->level($user),
            'payment_instructions' => $settings->get('payment_instructions'),
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get()->map(fn ($p) => PublicController::planPayload($p)),
            'enrollments' => Enrollment::with('plan')->where('user_id', $user->id)->orderByDesc('id')->get()->map(fn ($e) => [
                'id' => $e->id, 'plan' => $e->plan->name, 'status' => $e->status,
                'starts_at' => $e->starts_at, 'ends_at' => $e->ends_at,
                'active' => Enrollment::active()->whereKey($e->id)->exists(),
            ]),
            'payments' => Payment::with('plan')->where('user_id', $user->id)->orderByDesc('id')->get()->map(fn ($p) => [
                'id' => $p->id, 'plan' => $p->plan->name, 'amount' => $p->amount, 'status' => $p->status,
                'method' => $p->method, 'reference' => $p->reference, 'created_at' => $p->created_at,
            ]),
        ]);
    }

    /** Siswa hanya MENGAJUKAN pembayaran; status & nominal ditentukan server/admin. */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $d = $request->validated();
        $plan = Plan::findOrFail($d['plan_id']);

        abort_if($plan->price <= 0, 422, 'Paket ini gratis dan tidak perlu pembayaran.');
        abort_if(
            Payment::where('user_id', $user->id)->where('plan_id', $plan->id)->where('status', 'pending')->exists(),
            422, 'Pembayaran untuk paket ini masih menunggu verifikasi.'
        );

        $payment = Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'amount' => $plan->price,
            'status' => 'pending', 'method' => $d['method'], 'reference' => $d['reference'], 'note' => $d['note'] ?? null,
        ]);
        AuditLogger::log($user, 'payment.submitted', $payment, ['plan' => $plan->code, 'amount' => $plan->price]);

        return response()->json(['id' => $payment->id, 'status' => 'pending'], 201);
    }
}
