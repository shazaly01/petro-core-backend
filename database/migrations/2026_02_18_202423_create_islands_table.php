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
    Schema::create('islands', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // مثال: الجزيرة A، الجزيرة الشرقية
        $table->string('code')->nullable(); // كود إداري للجزيرة إن وجد
        $table->boolean('is_active')->default(true); // حالة الجزيرة (تعمل/صيانة)
        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('islands');
    }
};
