<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safe_transactions', function (Blueprint $table) {
            $table->id();

            // رقم الحركة (يُستخدم للبحث والتوثيق)
            $table->decimal('transaction_no', 18, 0)->unique();

            $table->foreignId('safe_id')->constrained('safes');
            $table->foreignId('user_id')->constrained('users'); // المستخدم الذي قام بالحركة

            $table->enum('type', ['in', 'out']); // in = إيراد تكليف، out = مصروف أو تصفير
            $table->decimal('amount', 18, 3);
            $table->decimal('balance_after', 18, 3); // الرصيد بعد الحركة للمراجعة

            // ربط الحركة بمصدرها (مثلاً: Assignment, Expense)
            $table->nullableMorphs('transactionable');

            $table->string('description')->nullable(); // مثال: "إيراد تكليف رقم 5", "سحب لتصفير الخزينة"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_transactions');
    }
};
