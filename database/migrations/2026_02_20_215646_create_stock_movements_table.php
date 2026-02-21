<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->restrictOnDelete();

            // نوع الحركة: in (توريد)، out (مبيعات)، adjustment (تسوية/عجز)
            $table->enum('type', ['in', 'out', 'adjustment']);

            // الكمية والأرصدة (استخدمنا خانتين عشريتين للترات)
            $table->decimal('quantity', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);

            // هذا السطر السحري ينشئ عمودين: trackable_id و trackable_type
            // لربط الحركة إما بـ (Assignment) أو بـ (SupplyLog)
            $table->morphs('trackable');

            // من قام بهذه الحركة؟
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->text('notes')->nullable(); // ملاحظات اختيارية (مثلاً سبب التسوية)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
