<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request, SettingsService $settings): JsonResponse
    {
        return response()->json([
            'user' => AuthController::payload($request->user()),
            'limits' => [
                'daily_target_min' => $settings->get('daily_target_min'),
                'daily_target_max' => $settings->get('daily_target_max'),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return response()->json(['user' => AuthController::payload($user->fresh())]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi saat ini salah.']);
        }
        $user->update(['password' => $data['password']]);

        return response()->json(['message' => 'Kata sandi diperbarui.']);
    }
}
