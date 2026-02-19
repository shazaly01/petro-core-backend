<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),

            // مبلغ عشوائي بين 10 و 200 (مبلغ تعبئة سيارة واحدة)
            'amount' => fake()->randomFloat(2, 10, 200),

            'payment_method' => fake()->randomElement(['cash', 'visa', 'sadad', 'cheque']),
            'reference_number' => fake()->optional()->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
