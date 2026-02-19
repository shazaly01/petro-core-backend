<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class PaymentTest extends ApiTestCase
{
    #[Test]
    public function an_admin_can_view_a_list_of_payments(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        Payment::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/payments');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function payments_can_be_filtered_by_project_id(): void
    {
        // Arrange
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        Payment::factory()->count(2)->create(['project_id' => $projectA->id]);
        Payment::factory()->count(3)->create(['project_id' => $projectB->id]);

        // Act
        $response = $this->getJson("/api/payments?project_id={$projectA->id}");

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_data_entry_user_can_create_a_payment(): void
    {
        // Arrange
        Sanctum::actingAs($this->dataEntryUser);
        $project = Project::factory()->create();
        // نستخدم make() لإنشاء بيانات وهمية دون حفظها في قاعدة البيانات
        $paymentData = Payment::factory()->make(['project_id' => $project->id])->toArray();

        // Act
        $response = $this->postJson('/api/payments', $paymentData);

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('payments', [
            'project_id' => $project->id,
            // نقارن المبلغ بعد تقريبه لتجنب مشاكل الفاصلة العائمة
            'amount' => round($paymentData['amount'], 2),
        ]);
    }

    #[Test]
    public function an_auditor_cannot_create_a_payment(): void
    {
        // Arrange
        Sanctum::actingAs($this->auditorUser);
        $paymentData = Payment::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/payments', $paymentData);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function an_admin_can_update_a_payment(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $payment = Payment::factory()->create();
        $updateData = ['amount' => 9999.99, 'notes' => 'Updated Note'];

        // Act
        $response = $this->putJson("/api/payments/{$payment->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => 9999.99,
            'notes' => 'Updated Note',
        ]);
    }

    #[Test]
    public function a_super_admin_can_delete_a_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create();

        // Act
        $response = $this->deleteJson("/api/payments/{$payment->id}");

        // Assert
        $response->assertNoContent();
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }
}
