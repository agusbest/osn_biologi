<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(), 'type' => 'mcq', 'difficulty' => 'fondasi',
            'body' => '<p>Soal contoh: berapakah 1 + 1?</p>',
            'explanation' => [
                'correct_answer' => '<p>B</p>', 'concept_tested' => '<p>Penjumlahan</p>',
                'solution_steps' => '<p>1 + 1 = 2</p>', 'why_others_wrong' => '<p>Lainnya salah.</p>',
                'practical_trick' => '<p>Hitung.</p>', 'common_mistakes' => '<p>Salah hitung.</p>',
                'similar_questions' => '<p>2 + 2?</p>',
            ],
            'access_level' => 0, 'status' => 'published',
        ];
    }

    /** Buat soal pilihan ganda dengan opsi A–C; kunci = B. */
    public function withOptions(): static
    {
        return $this->afterCreating(function (Question $q) {
            foreach ([['A', '1', false], ['B', '2', true], ['C', '3', false]] as $i => [$l, $t, $c]) {
                $q->options()->create(['label' => $l, 'text' => $t, 'is_correct' => $c, 'sort' => $i]);
            }
        });
    }
}
