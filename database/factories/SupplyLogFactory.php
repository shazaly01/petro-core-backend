<?php

namespace Database\Factories;

use App\Models\Tank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyLogFactory extends Factory
{
    public function definition(): array
    {
        // كمية الشحنة (بين 10,000 و 30,000 لتر)
        $quantity = fake()->randomElement([10000, 15000, 20000, 32000]);

        $stockBefore = fake()->numberBetween(1000, 5000);

        return [
            'tank_id' => Tank::factory(),
            'supervisor_id' => User::factory(),

            'quantity' => $quantity,
            'cost_price' => fake()->randomFloat(2, 1.5, 2.0), // سعر التكلفة

            'driver_name' => fake()->name(),
            'truck_plate_number' => fake()->bothify('ABC-####'),
            'invoice_number' => fake()->bothify('INV-#####'),

            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + $quantity,
        ];
    }
}
