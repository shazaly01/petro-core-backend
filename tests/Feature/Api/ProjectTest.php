<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class ProjectTest extends ApiTestCase
{
    #[Test]
    public function an_admin_can_view_a_list_of_projects(): void
    {
        // Arrange: تسجيل الدخول كـ Admin وإنشاء بعض المشاريع
        Sanctum::actingAs($this->adminUser);
        Project::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/projects');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function projects_can_be_filtered_by_company_id(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Project::factory()->count(2)->create(['company_id' => $companyA->id]);
        Project::factory()->count(3)->create(['company_id' => $companyB->id]);

        // Act: طلب المشاريع الخاصة بالشركة A فقط
        $response = $this->getJson("/api/projects?company_id={$companyA->id}");

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_data_entry_user_can_create_a_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->dataEntryUser);
        $company = Company::factory()->create();
        $projectData = Project::factory()->make(['company_id' => $company->id])->toArray();

        // Act
        $response = $this->postJson('/api/projects', $projectData);

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('projects', [
            'name' => $projectData['name'],
            'company_id' => $company->id,
        ]);
    }

    #[Test]
    public function an_auditor_cannot_create_a_project(): void
    {
        // Arrange: تسجيل الدخول كمشاهد فقط
        Sanctum::actingAs($this->auditorUser);
        $projectData = Project::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/projects', $projectData);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function an_admin_can_update_a_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $project = Project::factory()->create();
        $updateData = ['name' => 'Updated Project Name'];

        // Act
        $response = $this->putJson("/api/projects/{$project->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
        ]);
    }

    #[Test]
    public function a_super_admin_can_delete_a_project(): void
    {
        // Arrange: Super Admin مسجل دخوله افتراضيًا
        $project = Project::factory()->create();

        // Act
        $response = $this->deleteJson("/api/projects/{$project->id}");

        // Assert
        $response->assertNoContent();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
