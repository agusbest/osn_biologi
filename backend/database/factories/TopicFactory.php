<?php

namespace Database\Factories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Topic> */
class TopicFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'number' => $n % 100, 'slug' => "topik-$n", 'title' => "Topik contoh $n", 'description' => 'Contoh.',
            'type' => 'theory', 'status' => 'published', 'sort' => 0,
        ];
    }
}
