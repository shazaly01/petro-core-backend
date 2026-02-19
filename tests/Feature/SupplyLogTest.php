<?php

namespace Tests\Feature;

use App\Models\Tank;
use App\Models\SupplyLog;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class SupplyLogTest extends ApiTestCase
{
    /** @test */
    public function supervisor_can_record_fuel_supply_and_inventory_increases()
    {
        // 1. تجهيز خزان بمخزون ابتدائي (مثلاً 1000 لتر)
        $tank = Tank::factory()->create(['current_stock' => 1000]);

        Sanctum::actingAs($this->supervisorUser);

        // 2. تسجيل توريد شحنة (5000 لتر)
        $response = $this->postJson('/api/supply-logs', [
            'tank_id' => $tank->id,
            'quantity' => 5000,
            'cost_price' => 1.80,
            'driver_name' => 'أحمد السائق',
            'truck_plate_number' => 'أ ب ج 123',
            'invoice_number' => 'INV-999',
            'stock_before' => 1000,
        ]);

        $response->assertStatus(201);

        // 3. التحقق من زيادة المخزون في قاعدة البيانات (1000 + 5000 = 6000)
        $this->assertEquals(6000, $tank->fresh()->current_stock);

        $this->assertDatabaseHas('supply_logs', [
            'invoice_number' => 'INV-999',
            'quantity' => 5000
        ]);
    }

    /** @test */
    public function deleting_supply_log_reverts_inventory()
    {
        // اختبار المنطق المعقد في SupplyLogController::destroy

        // 1. خزان به 6000 لتر
        $tank = Tank::factory()->create(['current_stock' => 6000]);

        // 2. سجل توريد سابق بقيمة 5000 لتر
        $log = SupplyLog::factory()->create([
            'tank_id' => $tank->id,
            'quantity' => 5000
        ]);

        Sanctum::actingAs($this->adminUser);

        // 3. حذف السجل (بسبب خطأ في الإدخال مثلاً)
        $response = $this->deleteJson("/api/supply-logs/{$log->id}");

        $response->assertStatus(204);

        // 4. التوقع: المخزون يجب أن يعود كما كان قبل هذا السجل (6000 - 5000 = 1000)
        $this->assertEquals(1000, $tank->fresh()->current_stock);
    }

    /** @test */
    public function it_validates_positive_quantity()
    {
        $tank = Tank::factory()->create();

        Sanctum::actingAs($this->supervisorUser);

        // محاولة إدخال كمية صفر أو سالبة
        $response = $this->postJson('/api/supply-logs', [
            'tank_id' => $tank->id,
            'quantity' => -100,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['quantity']);
    }
}
