<?php

namespace Database\Factories;

use App\Models\Pump;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

class NozzleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pump_id' => Pump::factory(),
            'tank_id' => Tank::factory(),

            'code' => fake()->unique()->bothify('NZL-####'),

            // قراءة العداد: رقم كبير عشوائي (بين 1000 و نصف مليون)
            'current_counter' => fake()->randomFloat(2, 1000, 500000),

            'is_active' => true,
        ];
    }
}
