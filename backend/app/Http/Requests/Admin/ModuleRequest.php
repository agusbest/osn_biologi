<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'access_level' => ['required', 'integer', 'between:0,2'],
            'sort' => ['nullable', 'integer', 'between:0,9999'],
            'syllabus_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
