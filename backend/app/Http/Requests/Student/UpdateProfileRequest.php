<?php

namespace App\Http\Requests\Student;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $s = app(SettingsService::class);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            // Rentang target harian diatur admin (default 30–60 menit).
            'daily_target_minutes' => ['sometimes', 'integer', 'min:' . $s->get('daily_target_min'), 'max:' . $s->get('daily_target_max')],
        ];
    }
}
