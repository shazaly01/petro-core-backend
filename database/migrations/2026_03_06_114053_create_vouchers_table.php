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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            // تطبيق قاعدتك: رقم السند كـ DECIMAL(18, 0)
            $table->decimal('voucher_no', 18, 0)->unique()->comment('رقم السند المالي');

            // ربط السند بالوردية (يقبل الفراغ في حال تم عمل سند خارج أوقات الورديات)
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // من قام بإنشاء السند (المشرف/المدير)
            $table->foreignId('user_id')->constrained('users');

            // نوع السند (سيتم ربطه بـ Enum الذي أنشأناه)
            $table->string('type')->comment('deposit, expense, withdrawal, settlement');

            // القيمة المالية
            $table->decimal('amount', 18, 3);

            // طريقة الدفع (الافتراضي كاش لأننا نتعامل مع خزينة المحطة)
            $table->string('payment_method')->default('cash');

            // البيان أو الشرح
            $table->string('description')->nullable();

            // تاريخ ووقت السند
            $table->timestamp('date')->useCurrent();

            $table->timestamps();
            $table->softDeletes(); // للحفاظ على السجلات في حال الحذف (أمان محاسبي)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
