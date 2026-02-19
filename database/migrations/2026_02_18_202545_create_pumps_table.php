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
    Schema::create('pumps', function (Blueprint $table) {
        $table->id();

        // المضخة تتبع جزيرة معينة
        $table->foreignId('island_id')->constrained('islands')->cascadeOnDelete();

        $table->string('name'); // مثال: مضخة 1، مضخة 2
        $table->string('code')->nullable(); // كود تعريفي
        $table->string('model')->nullable(); // موديل الماكينة (اختياري للصيانة)

        $table->boolean('is_active')->default(true); // حالة المضخة
        $table->text('notes')->nullable(); // ملاحظات صيانة

        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pumps');
    }
};
