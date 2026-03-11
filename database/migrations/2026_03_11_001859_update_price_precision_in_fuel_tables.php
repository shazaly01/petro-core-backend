<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تحديث سعر الوقود الحالي في جدول أنواع الوقود
        Schema::table('fuel_types', function (Blueprint $table) {
            $table->decimal('current_price', 18, 4)->default(0)->change();
        });

        // 2. تحديث سعر الوحدة في جدول التعيينات (المبيعات)
        Schema::table('assignments', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 4)->default(0)->change();
        });

        // 3. تحديث سعر التكلفة في سجلات التوريد
        Schema::table('supply_logs', function (Blueprint $table) {
            $table->decimal('cost_price', 18, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // التراجع إلى 3 خانات عشرية في حال الرغبة في العودة للوضع السابق
        Schema::table('fuel_types', function (Blueprint $table) {
            $table->decimal('current_price', 18, 3)->default(0)->change();
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 3)->default(0)->change();
        });

        Schema::table('supply_logs', function (Blueprint $table) {
            $table->decimal('cost_price', 18, 3)->nullable()->change();
        });
    }
};
