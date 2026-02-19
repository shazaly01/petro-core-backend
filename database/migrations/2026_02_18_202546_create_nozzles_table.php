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
    Schema::create('nozzles', function (Blueprint $table) {
        $table->id();

        // المسدس يتبع مضخة
        $table->foreignId('pump_id')->constrained('pumps')->cascadeOnDelete();

        // المسدس يسحب من خزان معين (وهذا يحدد نوع الوقود تلقائياً)
        // ملاحظة: ربطنا بالخزان وليس نوع الوقود مباشرة لنعرف من أين نخصم الكمية
        $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();

        $table->string('code'); // رقم المسدس (1, 2, 3...)

        // قراءة العداد الحالية (تتحدث بعد كل إغلاق وردية)
        // Decimal(18, 2) لأن العداد قد يكون فيه كسور لترات
        $table->decimal('current_counter', 18, 2)->default(0);

        $table->boolean('is_active')->default(true);

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nozzles');
    }
};
