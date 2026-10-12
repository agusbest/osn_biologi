<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Sengaja tidak ada "status"/"amount": nominal diambil dari paket di server.
        return [
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'method' => ['required', Rule::in(['transfer', 'qris', 'ewallet', 'lainnya'])],
            'reference' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
