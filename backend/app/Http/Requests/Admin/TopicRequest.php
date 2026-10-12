<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['nullable', 'integer', 'between:0,99'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['theory', 'practicum'])],
            'sort' => ['nullable', 'integer', 'between:0,9999'],
            'syllabus_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
