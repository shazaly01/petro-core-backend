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

            // 🛑 استبدال المصروفات بالسندات المالية الشاملة
            'voucher.view', 'voucher.create', 'voucher.update', 'voucher.delete',

            'assignment.view', 'assignment.create', 'assignment.update', 'assignment.delete',
            'supply.view', 'supply.create', 'supply.update', 'supply.delete',
            'transaction.view', 'transaction.create', 'transaction.update', 'transaction.delete',
            'setting.view', 'setting.update',

            // 🛑 الصلاحيات الخاصة بالمخزون والتسويات الجردية
            'inventory_adjustment.view', 'inventory_adjustment.create', 'inventory_adjustment.update', 'inventory_adjustment.delete',
            'stock_movement.view', // عرض دفتر الأستاذ (كشف حساب الخزان)

            // 💰 الصلاحيات الخاصة بالخزينة (الماليات)
            'safe.view', 'safe.create', 'safe.update', 'safe.delete',
            'safe.withdraw', // يمكن إبقاؤها إذا أردنا حماية زر "سحب/تصفير" بداخل شاشة السندات
            'safe_transaction.view', // عرض دفتر أستاذ الخزينة (حركات الدخول والخروج للقراءة فقط)
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

            // 🛑 إعطاء المشرف صلاحية عرض وإنشاء السندات (مصروفات، تسويات، إلخ)
            'voucher.view', 'voucher.create',

            'assignment.view', 'assignment.create', 'assignment.update',
            'supply.view', 'supply.create',
            'transaction.view', 'transaction.create',
            'infrastructure.view', // مشاهدة هيكل المحطة فقط

            // 🛑 صلاحيات الجرد للمشرف
            'inventory_adjustment.view', 'inventory_adjustment.create',
            'stock_movement.view',

            // 💰 صلاحيات الخزينة للمشرف
            'safe.view', // رؤية الخزينة ورصيدها
            'safe.withdraw', // تصفير الخزينة وتسليم النقدية
            'safe_transaction.view', // رؤية حركات الخزينة الخاصة بوردتيه أو محطته
        ]);

        // د. Worker: عامل المضخة
        Role::firstOrCreate(['name' => 'Worker', 'guard_name' => $guardName]);
    }
}
