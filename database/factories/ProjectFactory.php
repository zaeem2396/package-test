<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->catchPhrase(),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'archived']),
        ];
    }
}
