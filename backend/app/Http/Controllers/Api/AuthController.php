<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Enrollment;
use App\Models\Plan;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public static function payload(User $u): array
    {
        $level = app(AccessService::class)->level($u);

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'daily_target_minutes' => $u->daily_target_minutes,
            'access_level' => $level,
        ];
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        // role default 'student' dari database; tidak pernah dari input.

        if ($free = Plan::where('code', 'free')->first()) {
            Enrollment::create(['user_id' => $user->id, 'plan_id' => $free->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => null]);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => self::payload($user->fresh()),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => self::payload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => self::payload($request->user())]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Respons seragam agar tidak membocorkan email yang terdaftar.
        return response()->json(['message' => 'Jika email terdaftar, tautan reset kata sandi telah dikirim.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete(); // cabut semua sesi lama
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        return response()->json(['message' => 'Kata sandi berhasil diubah. Silakan masuk.']);
    }
}
