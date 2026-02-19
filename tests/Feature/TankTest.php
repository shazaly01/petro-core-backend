<?php

namespace Tests\Feature;

use App\Models\FuelType;
use App\Models\Tank;
use App\Models\Pump;
use App\Models\Nozzle;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class TankTest extends ApiTestCase
{
    /** @test */
    public function it_can_list_tanks_with_fuel_type_details()
    {
        // 1. إنشاء نوع وقود وخزان تابع له
        $fuel = FuelType::factory()->create(['name' => 'ديزل ممتاز']);
        Tank::factory()->create([
            'fuel_type_id' => $fuel->id,
            'name' => 'خزان 1',
            'current_stock' => 5000
        ]);

        // 2. طلب القائمة (بصفة Admin افتراضياً)
        $response = $this->getJson('/api/tanks');

        // 3. التحقق: يجب أن يعود الخزان ومعه تفاصيل نوع الوقود
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'خزان 1'])
                 ->assertJsonFragment(['name' => 'ديزل ممتاز']); // التأكد من تحميل علاقة نوع الوقود
    }

    /** @test */
    public function admin_can_create_valid_tank()
    {
        $fuel = FuelType::factory()->create();

        $data = [
            'fuel_type_id' => $fuel->id,
            'name' => 'الخزان الرئيسي',
            'code' => 'TNK-TEST-01',
            'capacity' => 20000,
            'current_stock' => 5000, // قيمة صحيحة (أقل من السعة)
            'alert_threshold' => 1000,
        ];

        $response = $this->postJson('/api/tanks', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['code' => 'TNK-TEST-01']);

        $this->assertDatabaseHas('tanks', ['code' => 'TNK-TEST-01']);
    }

    /** @test */
    public function it_fails_if_stock_exceeds_capacity()
    {
        // اختبار قاعدة التحقق (lte:capacity) التي وضعناها في TankRequest
        $fuel = FuelType::factory()->create();

        $data = [
            'fuel_type_id' => $fuel->id,
            'name' => 'خزان فائض',
            'capacity' => 1000,   // السعة صغيرة
            'current_stock' => 2000, // المخزون أكبر من السعة (خطأ)
        ];

        $response = $this->postJson('/api/tanks', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['current_stock']);
    }

    /** @test */
    public function supervisor_cannot_delete_tank()
    {
        // التأكد من الصلاحيات (Policy)

        // إنشاء خزان
        $tank = Tank::factory()->create();

        // تسجيل الدخول كمشرف (ليس لديه صلاحية حذف بنية تحتية)
        Sanctum::actingAs($this->supervisorUser);

        // محاولة الحذف
        $response = $this->deleteJson("/api/tanks/{$tank->id}");

        // يجب أن يرفض (Forbidden)
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_cannot_delete_tank_with_active_nozzles()
    {
        // اختبار منطق الحماية في TankController::destroy

        // 1. إعداد السلسلة: وقود -> خزان -> مضخة -> مسدس
        $tank = Tank::factory()->create();
        $pump = Pump::factory()->create();

        // ربط المسدس بالخزان (هذا ما يمنع الحذف)
        Nozzle::factory()->create([
            'pump_id' => $pump->id,
            'tank_id' => $tank->id
        ]);

        // 2. محاولة حذف الخزان (بصفة Admin)
        $response = $this->deleteJson("/api/tanks/{$tank->id}");

        // 3. التوقع: فشل العملية لأن الخزان مستخدم
        // الرسالة يجب أن تطابق ما كتبناه في TankController
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'لا يمكن حذف الخزان لأنه يغذي مسدسات وقود قائمة.']);

        // التأكد أن الخزان لم يحذف من قاعدة البيانات
        $this->assertDatabaseHas('tanks', ['id' => $tank->id]);
    }
}
