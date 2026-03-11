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
        Schema::table('supply_logs', function (Blueprint $table) {
            // تعديل سعر التكلفة ليدعم 4 خانات عشرية بدلاً من 3
            $table->decimal('cost_price', 18, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_logs', function (Blueprint $table) {
            // التراجع إلى 3 خانات عشرية في حال الحاجة
            $table->decimal('cost_price', 18, 3)->nullable()->change();
        });
    }
};
