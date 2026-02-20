<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pump_id')->constrained()->cascadeOnDelete(); // المضخة بدلاً من المسدس

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // قراءات المسدس الأول (بداية ونهاية)
            $table->decimal('start_counter_1', 10, 2)->default(0);
            $table->decimal('end_counter_1', 10, 2)->nullable();

            // قراءات المسدس الثاني (بداية ونهاية)
            $table->decimal('start_counter_2', 10, 2)->default(0);
            $table->decimal('end_counter_2', 10, 2)->nullable();

            // التسعير والماليات (تم زيادة الدقة للأسعار والمبالغ 10,3)
            $table->decimal('unit_price', 10, 3)->default(0);
            $table->decimal('expected_amount', 12, 3)->nullable(); // المبلغ المحسوب برمجياً
            $table->decimal('cash_amount', 12, 3)->nullable();     // النقدية
            $table->decimal('bank_amount', 12, 3)->nullable();     // الشبكة/البنك
            $table->decimal('difference', 12, 3)->nullable();      // العجز أو الزيادة

            $table->enum('status', ['active', 'completed'])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
