<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. تنظيف الكاش
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        // 2. قائمة الصلاحيات الشاملة
        $permissions = [
            'dashboard.view', 'reports.view',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',
            'infrastructure.view', 'infrastructure.create', 'infrastructure.update', 'infrastructure.delete',
            'shift.view', 'shift.create', 'shift.close', 'shift.delete',
            'assignment.view', 'assignment.create', 'assignment.update', 'assignment.delete',
            'supply.view', 'supply.create', 'supply.update', 'supply.delete',
            'transaction.view', 'transaction.create', 'transaction.update', 'transaction.delete',
            'setting.view', 'setting.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guardName]);
        }

        // --- إعداد الأدوار ---

        // أ. Super Admin: يمتلك كل شيء تلقائياً
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guardName]);

        // ب. Admin: مدير النظام (صلاحيات كاملة)
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guardName]);
        $adminRole->syncPermissions(Permission::where('guard_name', $guardName)->get());

        // ج. Supervisor: مشرف الميدان (إدارة العمليات اليومية)
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guardName]);
        $supervisorRole->syncPermissions([
            'dashboard.view', 'reports.view',
            'shift.view', 'shift.create', 'shift.close',
            'assignment.view', 'assignment.create', 'assignment.update',
            'supply.view', 'supply.create',
            'transaction.view', 'transaction.create',
            'infrastructure.view', // مشاهدة هيكل المحطة فقط
        ]);

        // د. Worker: عامل المضخة
        Role::firstOrCreate(['name' => 'Worker', 'guard_name' => $guardName]);
    }
}
