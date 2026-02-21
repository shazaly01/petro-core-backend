<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // الرصيد الدفتري (في النظام قبل الجرد)
            $table->decimal('system_stock', 18, 2);
            // الرصيد الفعلي (الذي أدخله المشرف بعد القياس بالمسطرة)
            $table->decimal('actual_stock', 18, 2);
            // الفرق (سالب يعني عجز، موجب يعني زيادة)
            $table->decimal('difference', 18, 2);

            // سبب التسوية (مثال: تبخر طبيعي، تسريب، خطأ قراءة سابق...)
            $table->string('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
