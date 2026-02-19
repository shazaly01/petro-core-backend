<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tank>
 */
class TankFactory extends Factory
{
    public function definition(): array
    {
        // توليد سعة عشوائية بين 10 آلاف و 50 ألف لتر
        $capacity = fake()->numberBetween(10000, 50000);

        // توليد مخزون حالي بين 10% و 90% من السعة
        $currentStock = fake()->numberBetween($capacity * 0.1, $capacity * 0.9);

        return [
            // إذا لم يتم تمرير fuel_type_id، قم بإنشاء واحد جديد
            'fuel_type_id' => FuelType::factory(),

            'name' => 'خزان ' . fake()->unique()->numberBetween(1, 20),
            'code' => fake()->unique()->bothify('TNK-###'),
            'capacity' => $capacity,
            'current_stock' => $currentStock,
            'alert_threshold' => 1000, // تنبيه عند وصوله لـ 1000 لتر
        ];
    }
}
