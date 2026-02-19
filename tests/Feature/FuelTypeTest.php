<?php

namespace Tests\Feature;

use App\Models\FuelType;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class FuelTypeTest extends ApiTestCase
{
    /** @test */
    public function it_can_list_fuel_types()
    {
        // 1. إنشاء بيانات وهمية
        FuelType::factory()->count(3)->create();

        // 2. طلب الـ API (بصفة Admin افتراضياً)
        $response = $this->getJson('/api/fuel-types');

        // 3. التحقق من النتيجة
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data') // نتوقع 3 عناصر داخل مفتاح data
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'name', 'current_price', 'tanks_count']
                     ]
                 ]);
    }

    /** @test */
    public function admin_can_create_fuel_type()
    {
        $data = [
            'name' => 'بنزين 98 سوبر',
            'current_price' => 2.55,
            'description' => 'وقود عالي الجودة',
        ];

        // المدير هو المستخدم الافتراضي، فلا داعي لـ actingAs
        $response = $this->postJson('/api/fuel-types', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'بنزين 98 سوبر']);

        // التأكد من وجوده في قاعدة البيانات
        $this->assertDatabaseHas('fuel_types', ['name' => 'بنزين 98 سوبر']);
    }

    /** @test */
    public function supervisor_cannot_create_fuel_type()
    {
        // المشرف لديه صلاحية view فقط للبنية التحتية، وليس create
        Sanctum::actingAs($this->supervisorUser);

        $data = [
            'name' => 'وقود نووي',
            'current_price' => 1000,
        ];

        $response = $this->postJson('/api/fuel-types', $data);

        // يجب أن يرفض النظام الطلب (Forbidden)
        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // إرسال بيانات فارغة
        $response = $this->postJson('/api/fuel-types', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'current_price']);
    }

    /** @test */
    public function it_prevents_duplicate_fuel_names()
    {
        // إنشاء نوع وقود
        FuelType::factory()->create(['name' => 'ديزل']);

        // محاولة إنشاء نفس النوع مرة أخرى
        $response = $this->postJson('/api/fuel-types', [
            'name' => 'ديزل',
            'current_price' => 1.5,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}
