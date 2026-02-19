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
    Schema::create('tanks', function (Blueprint $table) {
        $table->id();
        // الربط مع نوع الوقود (إذا حذف النوع، لا نحذف الخزان بل نجعله null للحفاظ على البيانات)
        $table->foreignId('fuel_type_id')->nullable()->constrained('fuel_types')->nullOnDelete();

        $table->string('name'); // مثال: خزان رقم 1
        $table->string('code')->nullable(); // كود الخزان

        // السعة الكلية للخزان
        $table->decimal('capacity', 18, 2)->default(0);

        // الرصيد الحالي (المخزون الفعلي)
        $table->decimal('current_stock', 18, 2)->default(0);

        // حد الطلب (عندما يصل المخزون لهذا الرقم يعطيك تنبيه)
        $table->decimal('alert_threshold', 18, 2)->nullable();

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};
