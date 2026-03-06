<?php

namespace Database\Seeders;

use App\Models\Safe;
use Illuminate\Database\Seeder;

class SafeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // نستخدم firstOrCreate لضمان عدم تكرار الخزينة إذا تم تشغيل الـ Seeder أكثر من مرة
        // نحدد ID = 1 لأن الـ SafeService يعتمد عليه
        Safe::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'الخزينة الرئيسية',
                'balance' => 0.000, // رصيد البداية دائماً صفر (يتم إدخال الرصيد الافتتاحي عبر السندات)
                // إذا كان لديك حقول أخرى في جدول safes مثل حالة الخزنة (status) يمكنك إضافتها هنا
                // 'status' => 'active',
            ]
        );
    }
}
