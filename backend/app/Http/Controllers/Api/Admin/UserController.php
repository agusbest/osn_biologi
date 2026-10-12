<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Plan;
use App\Models\User;
use App\Services\AccessService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request, AccessService $access): JsonResponse
    {
        $q = User::query();
        if ($request->filled('role')) {
            $q->where('role', $request->input('role'));
        }
        if ($s = $request->input('q')) {
            $q->where(fn ($w) => $w->whereLike('name', "%$s%")->orWhereLike('email', "%$s%"));
        }
        $page = $q->orderByDesc('id')->paginate(min(100, (int) $request->input('per_page', 30)));
        $page->getCollection()->transform(fn (User $u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role,
            'access_level' => $access->level($u), 'created_at' => $u->created_at,
        ]);

        return response()->json($page);
    }

    public function show(User $user, AccessService $access): JsonResponse
    {
        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'daily_target_minutes', 'created_at']) + ['access_level' => $access->level($user)],
            'enrollments' => $user->enrollments()->with('plan:id,name,code')->orderByDesc('id')->get(),
            'payments' => $user->payments()->with('plan:id,name')->orderByDesc('id')->get(),
            'attempt_count' => $user->attempts()->where('status', 'submitted')->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in(['student', 'admin'])],
        ]);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => $d['password']]);
        $user->forceFill(['role' => $d['role']])->save();
        if ($free = Plan::where('code', 'free')->first()) {
            Enrollment::create(['user_id' => $user->id, 'plan_id' => $free->id, 'status' => 'active', 'starts_at' => now()]);
        }
        AuditLogger::log($request->user(), 'user.created', $user, ['role' => $d['role']]);

        return response()->json(['id' => $user->id], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $d = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'role' => ['sometimes', Rule::in(['student', 'admin'])],
        ]);
        abort_if(
            isset($d['role']) && $d['role'] !== $user->role && $user->id === $request->user()->id,
            422, 'Anda tidak dapat mengubah peran akun sendiri.'
        );
        if (isset($d['name'])) {
            $user->name = $d['name'];
        }
        if (isset($d['role'])) {
            $user->role = $d['role'];
        }
        $user->save();
        AuditLogger::log($request->user(), 'user.updated', $user, $d);

        return response()->json(['ok' => true]);
    }
}
