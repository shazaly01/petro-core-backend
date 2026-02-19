<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends ApiTestCase
{
   // في tests/Feature/Api/DashboardTest.php

#[Test]
public function an_admin_can_view_dashboard_statistics(): void
{
    // Arrange
    $companies = Company::factory()->count(5)->create();

    // --- [التصحيح هنا] ---
    // نربط المشاريع بشركة موجودة من الشركات التي أنشأناها
    $project1 = Project::factory()->create([
        'contract_value' => 10000,
        'company_id' => $companies->first()->id,
    ]);
    $project2 = Project::factory()->create([
        'contract_value' => 25000,
        'company_id' => $companies->last()->id,
    ]);
    // --- [نهاية التصحيح] ---

    Payment::factory()->create(['project_id' => $project1->id, 'amount' => 2000]);
    Payment::factory()->create(['project_id' => $project2->id, 'amount' => 5000]);

    Sanctum::actingAs($this->adminUser);
    $response = $this->getJson('/api/dashboard');

    // Assert
    $response->assertOk();
    $response->assertJson([
        'data' => [
            'companies_count' => 5, // الآن يجب أن تكون 5
            'projects_count' => 2,
            'total_contracts_value' => 35000,
            'total_payments_value' => 7000,
        ]
    ]);
}


    #[Test]
    public function an_auditor_cannot_view_dashboard(): void
    {
        // Arrange: تسجيل الدخول كمستخدم ليس لديه صلاحية dashboard.view
        // (بافتراض أن Auditor ليس لديه هذه الصلاحية)
        Sanctum::actingAs($this->auditorUser);

        // Act
        $response = $this->getJson('/api/dashboard');

        // Assert
        $response->assertForbidden();
    }
}
