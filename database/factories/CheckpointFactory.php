<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckpointFactory extends Factory
{
    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'title' => fake()->sentence(3), 'occurred_at' => now(), 'branch' => 'main'];
    }
}
