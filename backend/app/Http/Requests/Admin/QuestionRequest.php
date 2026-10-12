<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'type' => ['required', Rule::in(['mcq', 'truefalse', 'essay'])],
            'difficulty' => ['required', Rule::in(['fondasi', 'pendalaman', 'tantangan'])],
            'body' => ['required', 'string', 'max:30000'],
            'access_level' => ['required', 'integer', 'between:0,2'],
            'syllabus_version' => ['nullable', 'string', 'max:20'],
            'options' => ['exclude_if:type,essay', 'required', 'array', 'max:8'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['required', 'string', 'max:5'],
            'options.*.text' => ['required', 'string', 'max:1000'],
            'options.*.is_correct' => ['boolean'],
            'explanation' => ['nullable', 'array'],
            'explanation.*' => ['nullable', 'string', 'max:30000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:60'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $type = $this->input('type');
            if ($type === 'essay') {
                return;
            }
            $opts = collect($this->input('options', []));
            $correct = $opts->where('is_correct', true)->count();
            if ($type === 'truefalse' && $opts->count() !== 2) {
                $v->errors()->add('options', 'Soal benar/salah harus punya tepat 2 opsi.');
            }
            if ($type === 'mcq' && $opts->count() < 2) {
                $v->errors()->add('options', 'Pilihan ganda minimal punya 2 opsi.');
            }
            if ($correct !== 1) {
                $v->errors()->add('options', 'Tandai tepat satu opsi sebagai kunci jawaban.');
            }
        }];
    }
}
