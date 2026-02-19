<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Island>
 */
class IslandFactory extends Factory
{
    public function definition(): array
    {
        return [
            // أسماء مثل: الجزيرة A، الجزيرة B
            'name' => 'الجزيرة ' . fake()->unique()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'code' => fake()->unique()->bothify('ISL-###'), // كود مثل ISL-045
            'is_active' => true,
        ];
    }
}
