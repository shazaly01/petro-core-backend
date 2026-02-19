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
    Schema::create('supply_logs', function (Blueprint $table) {
        $table->id();

        // أي خزان تمت تعبئته؟
        $table->foreignId('tank_id')->constrained('tanks');

        // المشرف الذي سجل العملية
        $table->foreignId('supervisor_id')->constrained('users');

        // الكمية المفرغة باللتر
        $table->decimal('quantity', 18, 2);

        // (اختياري) سعر الشراء/التكلفة للتر الواحد
        $table->decimal('cost_price', 18, 3)->nullable();

        // بيانات الشاحنة والسائق
        $table->string('driver_name')->nullable();
        $table->string('truck_plate_number')->nullable();
        $table->string('invoice_number')->nullable(); // رقم فاتورة المورد إن وجد

        // القراءة قبل وبعد (للتأكد من الكمية) - اختياري
        $table->decimal('stock_before', 18, 2)->nullable();
        $table->decimal('stock_after', 18, 2)->nullable();

        $table->softDeletes();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_logs');
    }
};
