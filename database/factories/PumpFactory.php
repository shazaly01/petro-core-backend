<?php

namespace Database\Factories;

use App\Models\Island;
use Illuminate\Database\Eloquent\Factories\Factory;

class PumpFactory extends Factory
{
    public function definition(): array
    {
        return [
            // نقوم بإنشاء جزيرة افتراضية إذا لم يتم تمرير واحدة
            'island_id' => Island::factory(),

            'name' => 'مضخة ' . fake()->unique()->numberBetween(1, 50),
            'code' => fake()->unique()->bothify('PMP-###'), // مثال: PMP-102
            'model' => fake()->randomElement(['Tokheim', 'Gilbarco', 'Wayne', 'Sankipetrol']),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
