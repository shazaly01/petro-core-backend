<?php

namespace Tests\Feature\Api\Reports;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class CompanyStatementTest extends ApiTestCase
{
    #[Test]
    public function an_admin_can_generate_a_company_statement_report(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $company = Company::factory()->create();

        // المشروع الأول: له دفعتان
        $project1 = Project::factory()->create([
            'company_id' => $company->id,
            'contract_value' => 100000,
        ]);
        Payment::factory()->create(['project_id' => $project1->id, 'amount' => 20000]);
        Payment::factory()->create(['project_id' => $project1->id, 'amount' => 15000]); // Total: 35000

        // المشروع الثاني: له دفعة واحدة
        $project2 = Project::factory()->create([
            'company_id' => $company->id,
            'contract_value' => 50000,
        ]);
        Payment::factory()->create(['project_id' => $project2->id, 'amount' => 10000]);

        // مشروع لشركة أخرى (للتأكد من أنه لا يدخل في الحساب)
        Project::factory()->create();

        // Act
        $response = $this->getJson("/api/reports/company-statement/{$company->id}");

        // Assert
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'projects' => [
                    [
                        'id' => $project1->id,
                        'name' => $project1->name,
                        'contract_value' => 100000,
                        'total_paid' => 35000,
                        'remaining' => 65000, // 100000 - 35000
                    ],
                    [
                        'id' => $project2->id,
                        'name' => $project2->name,
                        'contract_value' => 50000,
                        'total_paid' => 10000,
                        'remaining' => 40000, // 50000 - 10000
                    ],
                ],
                'summary' => [
                    'total_contracts_value' => 150000, // 100000 + 50000
                    'total_payments_received' => 45000, // 35000 + 10000
                    'total_remaining' => 105000, // 150000 - 45000
                ]
            ]
        ]);
    }
}
