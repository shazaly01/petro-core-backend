<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class ApiTestCase extends BaseTestCase
{
    use RefreshDatabase;

    // تعريف المستخدمين بأدوارهم الجديدة
    protected User $superAdmin;
    protected User $adminUser;     // مدير المحطة
    protected User $supervisorUser; // مشرف الوردية (بديل Data Entry)
    protected User $auditorUser;   // المحاسب/المراقب
    protected User $workerUser;    // عامل المضخة

    protected function setUp(): void
    {
        parent::setUp();

        // 1. تشغيل الـ Seeder لإنشاء الصلاحيات والأدوار
        $this->seed(PermissionSeeder::class);

        // 2. إنشاء مستخدمين وتعيين الأدوار لهم
        // ملاحظة: نستخدم assignRole للنص الصريح كما عرفناه في PermissionSeeder

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        $this->supervisorUser = User::factory()->create();
        $this->supervisorUser->assignRole('Supervisor'); // هذا الدور مهم جداً للعمليات

        $this->auditorUser = User::factory()->create();
        $this->auditorUser->assignRole('Auditor');

        $this->workerUser = User::factory()->create();
        $this->workerUser->assignRole('Worker');

        // 3. الدخول الافتراضي كمدير (لأن معظم الاختبارات تتطلب صلاحيات)
        Sanctum::actingAs($this->adminUser);
    }
}
