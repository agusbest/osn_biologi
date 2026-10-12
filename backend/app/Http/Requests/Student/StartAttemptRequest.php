<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => ['nullable', 'integer', 'exists:quizzes,id'],
            'question_ids' => ['nullable', 'array', 'max:50'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'difficulty' => ['nullable', Rule::in(['fondasi', 'pendalaman', 'tantangan'])],
            'type' => ['nullable', Rule::in(['mcq', 'truefalse', 'essay'])],
            'status' => ['nullable', Rule::in(['belum', 'benar', 'salah', 'favorit', 'sulit'])],
            'count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
