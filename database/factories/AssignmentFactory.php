<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use App\Models\Nozzle;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        // محاكاة منطقية:
        // 1. نبدأ بقراءة عشوائية للعداد
        $startCounter = fake()->randomFloat(2, 1000, 50000);

        // 2. نحدد كمية مباعة (بين 0 و 1000 لتر في المناوبة الواحدة)
        $soldLiters = fake()->randomFloat(2, 50, 1000);

        // 3. نحسب العداد النهائي
        $endCounter = $startCounter + $soldLiters;

        // 4. سعر اللتر (بين 2 و 3 ريال)
        $unitPrice = fake()->randomFloat(2, 2.18, 2.33);

        return [
            'shift_id' => Shift::factory(), // ينشئ وردية جديدة إذا لم تمرر
            'user_id' => User::factory(),   // ينشئ عامل جديد
            'nozzle_id' => Nozzle::factory(), // ينشئ مسدس جديد

            'start_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_at' => null, // مبدئياً مفتوحة

            'start_counter' => $startCounter,
            'end_counter' => 0, // سيتم تحديثه عند الإغلاق
            'sold_liters' => 0,
            'unit_price' => 0,
            'total_amount' => 0,
            'status' => 'active',
        ];
    }

    /**
     * حالة: تكليف مكتمل (Completed)
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $start = $attributes['start_at'];
            $end = (clone $start)->modify('+8 hours');

            $startCounter = $attributes['start_counter'];
            // بيع ما بين 100 إلى 500 لتر
            $soldLiters = fake()->randomFloat(2, 100, 500);
            $endCounter = $startCounter + $soldLiters;
            $unitPrice = 2.33; // سعر ثابت للتجربة
            $total = $soldLiters * $unitPrice;

            return [
                'status' => 'completed',
                'end_at' => $end,
                'end_counter' => $endCounter,
                'sold_liters' => $soldLiters,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
            ];
        });
    }
}
