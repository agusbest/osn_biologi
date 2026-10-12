<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,600'],
            'access_level' => ['required', 'integer', 'between:0,2'],
            'syllabus_version' => ['nullable', 'string', 'max:20'],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'items.*.weight' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
