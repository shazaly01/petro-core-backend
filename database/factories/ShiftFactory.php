<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supervisor_id' => User::factory(), // ينشئ مستخدم جديد كمشرف
            'start_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_at' => null,
            'status' => 'open',
            'total_expected_cash' => 0,
            'total_actual_cash' => 0,
            'difference' => 0,
            'handover_notes' => null,
        ];
    }

    /**
     * حالة لإنشاء وردية مغلقة (Closed Shift)
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $start = $attributes['start_at'];
            $end = (clone $start)->modify('+8 hours'); // الوردية استمرت 8 ساعات

            $expected = fake()->randomFloat(2, 5000, 20000);
            // العجز أو الفائض بسيط (بين -50 و +50)
            $actual = $expected + fake()->numberBetween(-50, 50);

            return [
                'status' => 'closed',
                'end_at' => $end,
                'total_expected_cash' => $expected,
                'total_actual_cash' => $actual,
                'difference' => $actual - $expected,
                'handover_notes' => fake()->sentence(),
            ];
        });
    }
}
