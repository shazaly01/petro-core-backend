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
    Schema::create('shifts', function (Blueprint $table) {
        $table->id();

        // المشرف الذي فتح الوردية
        // نفترض أن جدول المستخدمين اسمه users
        $table->foreignId('supervisor_id')->constrained('users');

        $table->timestamp('start_at'); // وقت فتح الوردية
        $table->timestamp('end_at')->nullable(); // وقت الإغلاق (فارغ في البداية)

        // حالة الوردية: مفتوحة، مغلقة، مراجعة
        $table->string('status')->default('open');

        // ملخصات مالية (تعبأ عند الإغلاق)
        $table->decimal('total_expected_cash', 18, 3)->default(0); // المفروض وجوده
        $table->decimal('total_actual_cash', 18, 3)->default(0);   // الموجود فعلياً
        $table->decimal('difference', 18, 3)->default(0);          // العجز أو الزيادة

        $table->text('handover_notes')->nullable(); // ملاحظات التسليم للوردية التالية

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
