<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase; // <-- استخدام الكلاس الأساسي الذي أنشأناه
use PHPUnit\Framework\Attributes\Test;

class CompanyTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_a_list_of_companies(): void
    {
        Company::factory()->count(5)->create();

        $response = $this->getJson('/api/companies');

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function an_auditor_cannot_create_a_company(): void
    {
        // Arrange: تسجيل الدخول كمستخدم Auditor (مشاهد فقط)
        Sanctum::actingAs($this->auditorUser);
        $companyData = Company::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/companies', $companyData);

        // Assert
        $response->assertForbidden(); // 403 Forbidden
    }

    #[Test]
    public function a_data_entry_user_can_create_a_new_company(): void
    {
        // Arrange: تسجيل الدخول كمدخل بيانات
        Sanctum::actingAs($this->dataEntryUser);
        $companyData = Company::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/companies', $companyData);

        // Assert
        $response->assertCreated(); // 201 Created
        $this->assertDatabaseHas('companies', ['name' => $companyData['name']]);
    }

    #[Test]
    public function an_admin_can_update_a_company(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $company = Company::factory()->create();
        $updateData = ['name' => 'New Updated Name'];

        // Act
        $response = $this->putJson("/api/companies/{$company->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'New Updated Name']);
    }

    #[Test]
    public function a_super_admin_can_delete_a_company(): void
    {
        // Arrange: Super Admin مسجل دخوله افتراضيًا
        $company = Company::factory()->create();

        // Act
        $response = $this->deleteJson("/api/companies/{$company->id}");

        // Assert
        $response->assertNoContent(); // 204 No Content
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    #[Test]
    public function an_admin_cannot_force_delete_a_company(): void
    {
        // Arrange: حتى الـ Admin لا يملك صلاحية الحذف
        Sanctum::actingAs($this->adminUser);
        $company = Company::factory()->create();

        // Act
        $response = $this->deleteJson("/api/companies/{$company->id}");

        // Assert
        $response->assertForbidden();
    }
}
