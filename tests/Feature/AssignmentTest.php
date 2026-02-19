<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use App\Models\Nozzle;
use App\Models\Tank;
use App\Models\FuelType;
use App\Models\Assignment;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class AssignmentTest extends ApiTestCase
{
    /** @test */
    public function supervisor_can_start_assignment_for_worker()
    {
        // 1. تجهيز المتطلبات
        $shift = Shift::factory()->create(['supervisor_id' => $this->supervisorUser->id]);
        $worker = User::factory()->create();
        $nozzle = Nozzle::factory()->create(['current_counter' => 5000]);

        Sanctum::actingAs($this->supervisorUser);

        // 2. طلب بدء تكليف
        $response = $this->postJson('/api/assignments', [
            'shift_id' => $shift->id,
            'user_id' => $worker->id,
            'nozzle_id' => $nozzle->id,
            'start_counter' => 5000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('assignments', [
            'nozzle_id' => $nozzle->id,
            'status' => 'active',
            'start_counter' => 5000
        ]);
    }

/** @test */
    public function assignment_completion_calculates_all_values_correctly()
    {
        // ... نفس كود التجهيز ...
        $fuel = FuelType::factory()->create(['current_price' => 2.0]);
        $tank = Tank::factory()->create(['fuel_type_id' => $fuel->id, 'current_stock' => 10000]);
        $nozzle = Nozzle::factory()->create(['tank_id' => $tank->id, 'current_counter' => 1000]);

        $assignment = Assignment::factory()->create([
            'nozzle_id' => $nozzle->id,
            'start_counter' => 1000,
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->supervisorUser);

        // التعديل هنا: أضفنا start_counter للطلب لكي ينجح الـ Validation
        $response = $this->patchJson("/api/assignments/{$assignment->id}", [
            'status' => 'completed',
            'start_counter' => 1000, // <--- أضف هذا السطر
            'end_counter' => 1200,
        ]);

        $response->assertStatus(200);

        // ... باقي التحققات ...
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'sold_liters' => 200,
            'total_amount' => 400,
        ]);
    }

    /** @test */
    public function cannot_start_assignment_on_busy_nozzle()
    {
        // اختبار منع ازدواجية العمل على نفس المسدس
        $nozzle = Nozzle::factory()->create();

        // تكليف نشط حالياً
        Assignment::factory()->create(['nozzle_id' => $nozzle->id, 'status' => 'active']);

        Sanctum::actingAs($this->supervisorUser);

        $response = $this->postJson('/api/assignments', [
            'nozzle_id' => $nozzle->id,
            'user_id' => User::factory()->create()->id,
            'shift_id' => Shift::factory()->create()->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'هذا المسدس مشغول حالياً مع موظف آخر.']);
    }
}
