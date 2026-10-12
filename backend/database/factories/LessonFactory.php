<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lesson> */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'module_id' => Module::factory(), 'slug' => "lesson-$n", 'title' => "Materi contoh $n",
            'difficulty' => 'fondasi', 'est_minutes' => 10,
            'objectives' => '<ul><li>Contoh target</li></ul>', 'explanation' => '<p>Contoh penjelasan.</p>',
            'summary' => '<p>Contoh ringkasan.</p>', 'status' => 'published', 'sort' => 0,
        ];
    }
}
