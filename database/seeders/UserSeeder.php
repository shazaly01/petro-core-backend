<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'api';

        // إنشاء الرتب الأساسية لضمان وجودها قبل التعيين
        $roles = ['Super Admin', 'Admin', 'Supervisor', 'Worker'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
        }

        // 1. Super Admin
        $superAdmin = User::create([
            'full_name' => 'مدير النظام الأعلى',
            'username' => 'superadmin',
            'email' => 'superadmin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        // 2. Admin (مدير المحطة)
        $admin = User::create([
            'full_name' => 'مدير المحطة',
            'username' => 'admin',
            'email' => 'admin@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');

        // 3. Supervisor (مشرف وردية)
        $supervisor = User::create([
            'full_name' => 'مشرف الوردية الأولى',
            'username' => 'supervisor1',
            'email' => 'supervisor1@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $supervisor->assignRole('Supervisor');

        // 4. Worker (عامل مضخة)
        $worker = User::create([
            'full_name' => 'العامل أحمد',
            'username' => 'worker1',
            'email' => 'worker1@app.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $worker->assignRole('Worker');
    }
}
