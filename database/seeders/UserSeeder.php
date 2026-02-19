<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // استيراد موديل الرتب

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تحديد الـ guard المستخدم (api لأن خطأك ظهر هناك)
        $guard = 'api';

        // 1. إنشاء مستخدم Super Admin
        $superAdmin = User::create([
            'full_name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        // التأكد من وجود الرتبة ثم التعيين
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $superAdmin->assignRole('Super Admin');


        // 2. إنشاء مستخدم Admin
        $adminUser = User::create([
            'full_name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $adminUser->assignRole('Admin');


        // 3. إنشاء مستخدم Data Entry
        $dataEntryUser = User::create([
            'full_name' => 'Data Entry User',
            'username' => 'dataentry',
            'email' => 'dataentry@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        Role::firstOrCreate(['name' => 'Data Entry', 'guard_name' => $guard]);
        $dataEntryUser->assignRole('Data Entry');


        // 4. إنشاء مستخدم Auditor
        $auditorUser = User::create([
            'full_name' => 'Auditor User',
            'username' => 'auditor',
            'email' => 'auditor@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => $guard]);
        $auditorUser->assignRole('Auditor');
    }
}
