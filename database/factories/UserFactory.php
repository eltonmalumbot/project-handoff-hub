<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['github_id' => (string) fake()->unique()->numberBetween(10000, 99999999), 'github_login' => fake()->userName(), 'name' => fake()->name()];
    }
}
