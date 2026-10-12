<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'plan-' . fake()->unique()->numberBetween(1, 99999),
            'name' => 'Paket Contoh',
            'price' => 100000,
            'duration_days' => 30,
            'access_level' => 1,
            'features' => ['Contoh fitur'],
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => ['code' => 'free', 'name' => 'Free', 'price' => 0, 'duration_days' => null, 'access_level' => 0]);
    }

    public function full(): static
    {
        return $this->state(fn () => ['code' => 'full', 'name' => 'Full Course', 'access_level' => 1]);
    }
}
