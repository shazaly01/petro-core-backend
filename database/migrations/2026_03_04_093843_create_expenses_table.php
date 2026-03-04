<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // ربط المصروف بالوردية الكلية (التي حالتها open)
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');

            // المستخدم الذي سجل المصروف
            $table->foreignId('user_id')->constrained('users');

            // المبلغ المالي
            $table->decimal('amount', 15, 3);

            // تاريخ ووقت الصرف
            $table->timestamp('spent_at');

            // طريقة الدفع (نقدى / مصرف)
            $table->string('payment_method'); // 'cash' or 'bank'

            // البيان
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes(); // لإتاحة الحذف الناعم كما في باقي جداولك
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
