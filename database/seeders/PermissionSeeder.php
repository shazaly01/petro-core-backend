<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. تنظيف الكاش للصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. تعريف الحارس
        $guardName = 'api';

        // 3. قائمة الصلاحيات الجديدة الخاصة بمحطة الوقود
        $permissions = [
            // لوحة التحكم
            'dashboard.view',
            'reports.view', // تقارير المبيعات والمخزون

            // إدارة المستخدمين والأدوار
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',

            // البنية التحتية (أنواع الوقود، الخزانات، الجزر، المضخات، المسدسات)
            'infrastructure.view',
            'infrastructure.create',
            'infrastructure.update',
            'infrastructure.delete',

            // إدارة الورديات (فتح، إغلاق، مراجعة)
            'shift.view',
            'shift.create', // فتح وردية
            'shift.close',  // إغلاق وردية (حساسة)
            'shift.delete', // نادراً ما تستخدم

            // إدارة التكليفات (توزيع العمال على المضخات)
            'assignment.view',
            'assignment.create',
            'assignment.update', // تعديل عداد أو تغيير عامل
            'assignment.delete',

            // التوريد (تعبئة الخزانات)
            'supply.view',
            'supply.create',
            'supply.update',
            'supply.delete',

            // المعاملات المالية
            'transaction.view',
            'transaction.create', // تسجيل دفع إلكتروني
            'transaction.update',
            'transaction.delete',

            // إعدادات النظام
            'setting.view', 'setting.update',
        ];

        // 4. إنشاء الصلاحيات في قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guardName]);
        }

        // --- توزيع الأدوار ---

        // الدور 1: Super Admin
        // ملاحظة: يُفضل استخدام Gate::before في AuthServiceProvider لمنحه كل شيء
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guardName]);

        // الدور 2: Admin (مدير المحطة)
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guardName]);
        // المدير يحصل على جميع الصلاحيات المعرفة أعلاه
        $adminRole->syncPermissions(Permission::where('guard_name', $guardName)->get());

        // الدور 3: Supervisor (مشرف الوردية) - بديل لـ Data Entry ولكنه أنسب للمشروع
        // مشرف الوردية وظيفته: فتح وردية، توزيع عمال، تسجيل توريد، إغلاق وردية.
        // لكن لا يحق له حذف بنية تحتية أو تلاعب بالإعدادات.
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guardName]);

        $supervisorPermissions = [
            'dashboard.view',
            'reports.view',
            'shift.view', 'shift.create', 'shift.close',
            'assignment.view', 'assignment.create', 'assignment.update',
            'supply.view', 'supply.create',
            'transaction.view', 'transaction.create',
            'infrastructure.view', // مشاهدة فقط للخزانات والمضخات
        ];
        $supervisorRole->syncPermissions($supervisorPermissions);


        // الدور 4: Auditor (المحاسب / المراقب المالي)
        // يشاهد كل شيء ولا يعدل شيء
        $auditorRole = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => $guardName]);

        // نعطيه كل شيء ينتهي بـ .view
        $auditorPermissions = Permission::where('guard_name', $guardName)
                                        ->where('name', 'like', '%.view')
                                        ->get();
        $auditorRole->syncPermissions($auditorPermissions);

        // الدور 5: Worker (عامل المضخة) - جديد
        // قد يحتاج فقط لتطبيق الموبايل لتسجيل الدفع الإلكتروني إن وجد
        $workerRole = Role::firstOrCreate(['name' => 'Worker', 'guard_name' => $guardName]);
        // صلاحيات محدودة جداً (إن لزم الأمر مستقبلاً)
    }
}
