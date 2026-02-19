<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelType>
 */
class FuelTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            // نستخدم names واقعية بدلاً من كلمات عشوائية
            'name' => fake()->unique()->randomElement(['بنزين 91', 'بنزين 95', 'ديزل', 'غاز', 'كيروسين']),
            'current_price' => fake()->randomFloat(2, 2.18, 5.50), // سعر بين 2 و 5
            'description' => fake()->sentence(),
        ];
    }
}
