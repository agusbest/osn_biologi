<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $html = ['nullable', 'string', 'max:100000'];

        return [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:150'],
            'difficulty' => ['required', Rule::in(['fondasi', 'pendalaman', 'tantangan'])],
            'est_minutes' => ['required', 'integer', 'between:1,600'],
            'objectives' => $html,
            'explanation' => $html,
            'key_concepts' => $html,
            'osn_thinking' => $html,
            'worked_example' => $html,
            'practice' => $html,
            'summary' => $html,
            'glossary' => ['nullable', 'array', 'max:100'],
            'glossary.*.term' => ['required', 'string', 'max:100'],
            'glossary.*.definition' => ['required', 'string', 'max:1000'],
            'reference_list' => ['nullable', 'array', 'max:50'],
            'reference_list.*.text' => ['required', 'string', 'max:500'],
            'reference_list.*.url' => ['nullable', 'url:http,https', 'max:500'],
            'sort' => ['nullable', 'integer', 'between:0,9999'],
            'syllabus_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
