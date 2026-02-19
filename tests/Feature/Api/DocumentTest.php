<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class DocumentTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // إنشاء قرص تخزين وهمي للاختبارات
        Storage::fake('public');
    }

    #[Test]
    public function an_admin_can_view_a_list_of_documents_for_a_company(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $company = Company::factory()->create();
        Document::factory()->count(3)->create(['company_id' => $company->id]);

        // Act
        $response = $this->getJson("/api/documents?company_id={$company->id}");

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_data_entry_user_can_upload_a_document_for_a_company(): void
    {
        // Arrange
        Sanctum::actingAs($this->dataEntryUser);
        $company = Company::factory()->create();
        // إنشاء ملف وهمي
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $documentData = [
            'name' => 'Company Registration',
            'company_id' => $company->id,
            'file' => $file,
        ];

        // Act
        $response = $this->postJson('/api/documents', $documentData);

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Company Registration');

        // التأكد من أن السجل تم إنشاؤه في قاعدة البيانات
        $document = Document::first();
        $this->assertNotNull($document);
        $this->assertEquals('Company Registration', $document->name);

        // التأكد من أن الملف تم تخزينه في الـ storage الوهمي
        Storage::disk('public')->assertExists($document->file_path);
    }

    #[Test]
    public function an_auditor_cannot_upload_a_document(): void
    {
        // Arrange
        Sanctum::actingAs($this->auditorUser);
        $company = Company::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $response = $this->postJson('/api/documents', [
            'name' => 'Test Doc',
            'company_id' => $company->id,
            'file' => $file,
        ]);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function a_super_admin_can_delete_a_document(): void
    {
        // Arrange
        $document = Document::factory()->create();
        // التأكد من وجود الملف قبل الحذف
        Storage::disk('public')->put($document->file_path, 'dummy content');
        Storage::disk('public')->assertExists($document->file_path);

        // Act
        $response = $this->deleteJson("/api/documents/{$document->id}");

        // Assert
        $response->assertNoContent();
        // التأكد من أن السجل تم حذفه (حذف ناعم)
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        // التأكد من أن الملف الفعلي قد تم حذفه من الـ storage
        Storage::disk('public')->assertMissing($document->file_path);
    }
}
