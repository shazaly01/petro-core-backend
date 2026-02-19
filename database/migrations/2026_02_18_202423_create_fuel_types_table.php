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
    Schema::create('fuel_types', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // مثال: بنزين، ديزل
        // السعر قد يحتوي على 3 خانات عشرية (مثلاً 0.150 دينار)
        $table->decimal('current_price', 18, 3)->default(0);
        $table->text('description')->nullable();
        $table->softDeletes(); // تفعيل الحذف الناعم
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_types');
    }
};
