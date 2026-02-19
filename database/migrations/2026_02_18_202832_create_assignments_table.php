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
    Schema::create('assignments', function (Blueprint $table) {
        $table->id();

        // الربط بالوردية العامة
        $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();

        // الموظف المستلم (نفترض جدول users)
        $table->foreignId('user_id')->constrained('users');

        // المسدس المستلم
        $table->foreignId('nozzle_id')->constrained('nozzles');

        // التوقيت
        $table->timestamp('start_at'); // وقت الاستلام
        $table->timestamp('end_at')->nullable(); // وقت التسليم (فارغ في البداية)

        // العدادات
        $table->decimal('start_counter', 18, 2); // قراءة البداية (تؤخذ آلياً)
        $table->decimal('end_counter', 18, 2)->nullable(); // قراءة النهاية (تعبأ عند التسليم)

        // الحسابات (تعبأ عند التسليم)
        $table->decimal('sold_liters', 18, 2)->default(0); // الفرق بين العدادين
        $table->decimal('unit_price', 18, 3)->default(0); // سعر اللتر المعتمد في هذه الفترة
        $table->decimal('total_amount', 18, 3)->default(0); // المبلغ المطلوب (لترات × سعر)

        // الحالة: نشط (يعمل الآن) أو مكتمل
        $table->string('status')->default('active');

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
