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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();

        // نربط الدفع بـ "التكليف" وليس الموظف مباشرة
        // لنعرف أن هذه الدفعة تمت أثناء وقوف العامل فلان على المسدس فلان
        $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();

        // المبلغ المدفوع
        $table->decimal('amount', 18, 3);

        // نوع الدفع: cash, visa, sadad, tadawul, etc.
        $table->string('payment_method');

        // رقم مرجعي (رقم إيصال التحويل أو البطاقة)
        $table->string('reference_number')->nullable();

        // ملاحظات إضافية
        $table->text('notes')->nullable();

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
