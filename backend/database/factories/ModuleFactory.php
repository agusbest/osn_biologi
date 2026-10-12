<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Module> */
class ModuleFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'topic_id' => Topic::factory(), 'slug' => "modul-$n", 'title' => "Modul contoh $n",
            'summary' => 'Contoh.', 'access_level' => 0, 'status' => 'published', 'sort' => 0,
        ];
    }

    public function paid(int $level = 1): static
    {
        return $this->state(fn () => ['access_level' => $level]);
    }
}
