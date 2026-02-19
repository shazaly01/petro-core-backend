<?php

namespace Tests\Feature;

use App\Models\Shift;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class ShiftTest extends ApiTestCase
{
    /** @test */
    public function supervisor_can_open_a_new_shift()
    {
        // الدخول كمشرف (Supervisor)
        Sanctum::actingAs($this->supervisorUser);

        $response = $this->postJson('/api/shifts', [
            'start_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['status' => 'open']);

        $this->assertDatabaseHas('shifts', [
            'supervisor_id' => $this->supervisorUser->id,
            'status' => 'open'
        ]);
    }

    /** @test */
    public function supervisor_can_close_their_shift()
    {
        // 1. إنشاء وردية مفتوحة للمشرف
        $shift = Shift::factory()->create([
            'supervisor_id' => $this->supervisorUser->id,
            'status' => 'open',
            'start_at' => now()->subHours(8)
        ]);

        Sanctum::actingAs($this->supervisorUser);

        // 2. محاولة إغلاق الوردية
        $response = $this->patchJson("/api/shifts/{$shift->id}", [
            'status' => 'closed',
            'total_actual_cash' => 5000,
            'handover_notes' => 'الوردية انتهت بسلام'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'closed']);

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'status' => 'closed',
            'total_actual_cash' => 5000
        ]);
    }

    /** @test */
    public function supervisor_cannot_edit_a_closed_shift()
    {
        // اختبار السياسة (Policy): منع التعديل بعد الإغلاق
        $shift = Shift::factory()->closed()->create([
            'supervisor_id' => $this->supervisorUser->id
        ]);

        Sanctum::actingAs($this->supervisorUser);

        $response = $this->patchJson("/api/shifts/{$shift->id}", [
            'handover_notes' => 'محاولة تعديل غير قانونية'
        ]);

        // يجب أن يرفض لأن الوردية مغلقة والمشرف ليس Admin
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_edit_a_closed_shift_for_corrections()
    {
        // المدير يملك صلاحية تصحيح الأخطاء حتى بعد الإغلاق
        $shift = Shift::factory()->closed()->create();

        Sanctum::actingAs($this->adminUser);

        $response = $this->patchJson("/api/shifts/{$shift->id}", [
            'handover_notes' => 'تصحيح مدير النظام'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('shifts', ['handover_notes' => 'تصحيح مدير النظام']);
    }
}
