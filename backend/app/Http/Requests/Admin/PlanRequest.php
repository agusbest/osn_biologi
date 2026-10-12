<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('plan');

        return [
            'code' => ['required', 'alpha_dash', 'max:40', Rule::unique('plans', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'between:1,3650'],
            'access_level' => ['required', 'integer', 'between:0,2'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:200'],
            'is_active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'between:0,999'],
        ];
    }
}
