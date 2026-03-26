<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'display_name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
